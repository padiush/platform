# Offline sync protocol (proposed)

**Status: proposed.** The choreography behind the companion apps' offline
capability. The [companion API](companion-api.md) is the HTTP surface; this
document is how the device and server dance over it so a field worker with no
signal can record all day and reconcile later without losing or duplicating data.

This is the part of the companion milestone most likely to be underestimated, so
the tricky cases are spelled out rather than left to discover in the field.

## The model: capture-only makes sync push-dominant

Because the mobile app only *captures* ([0003](../decisions/0003-capture-only-companion-scope.md)),
data flows almost entirely one way:

- **Pull (read-only on device):** form structures. The device never edits a form,
  so there is no conflict to resolve on the pulled side — it's a cache refresh.
- **Push (device-owned):** interviews, answers, media, GPS. The device is the
  sole author of these until they land on the server.

This asymmetry is deliberate and is what lets us avoid CRDTs or three-way merges.
See [0004 — offline sync model](../decisions/0004-offline-sync-model.md).

## Identity: client-generated UUIDs

Offline-created records need identifiers before the server ever sees them.

- **Instances** are already UUID-keyed server-side (`InterviewInstance` uses
  `HasUuids`) — the device mints the instance `id`.
- **Answers** are integer-PK today (`instance_answers`). Offline creation needs a
  device-minted `client_id` (uuid) on each answer — **the one schema change this
  protocol requires**. Server keeps its own PK; `client_id` is the idempotency
  key and the device's stable reference.

## The loop

```
1. AUTH        obtain/refresh Sanctum token (online, once)
2. PULL        GET /projects/{p}/bundle?since=<cursor>   → cache forms locally
3. CAPTURE     record interviews offline into local store (SQLite)
               each instance + answer gets a client uuid at creation
4. PUSH        when online: POST /projects/{p}/instances:sync  (idempotent batch)
5. RECONCILE   apply per-item results; mark local records synced; advance cursor
6. MEDIA       upload audio/photos via presigned URLs; poll for transcripts
```

Steps 3–4 decouple fully: capture never blocks on connectivity, push never blocks
capture.

## Idempotency & retry

- The push is an **upsert keyed on the client uuid**. Re-sending a batch (after a
  dropped connection, a crash, a timeout with unknown outcome) is safe: already-
  applied records come back `unchanged`.
- Each element in the batch carries its **own** result (`created` / `updated` /
  `unchanged` / `rejected`), so partial success is normal and the device only
  retries the elements it must.
- An optional `Idempotency-Key` header dedupes an entire batch server-side within
  a short window, covering the "did my POST land before the socket died?" case.

## Conflict resolution — deliberately simple

The domain is **one recorder per interview**; genuine concurrent edits to the
same answer are rare. So:

- **An unsynced record is owned by its device.** No server state exists to
  conflict with.
- **After sync**, if the same instance is somehow edited from two places (e.g. two
  devices logged into the same account), resolve per-field by **last-writer-wins
  on `updated_at`**. This is adequate precisely because the event is rare and the
  data is additive; do **not** reach for CRDTs.
- Web-side edits to captured interviews are possible but expected to be light
  (corrections). They win only if newer.

## Form-version skew — the case that bites

A device caches form **v1**, goes offline, and captures against it. Meanwhile the
web edits the form to **v2** (renames an item, deletes a section). The device's
answers reference v1 items. Two ways to keep those answers valid:

**Recommended — snapshot at capture.** When an interview is created on the device,
it captures against the *cached* structure and the pushed instance carries the
`form_version_cursor` it was built from. The server accepts answers validated
against the structure as of that cursor, not only the latest. Answers whose item
truly no longer exists are `rejected` with a clear reason (not silently dropped),
and surfaced to the researcher to resolve on the web.

**Alternative — versioned forms.** Give `InterviewForm` explicit versions and pin
each instance to the version it was captured under. Heavier, but unambiguous, and
it also improves web-side reproducibility.

Either way, the existing **answer-detach guard** in `FormStructureService` (which
already reasons about structure changes vs. existing answers) is the seam to build
on — extend that logic to the sync path rather than inventing a parallel one.

> Decide snapshot-vs-versioning before building offline capture; retrofitting the
> other is expensive. Listed in [Open decisions](#open-decisions).

## Deletions

Rare in capture (you delete a mistaken draft). Use a **soft-delete tombstone**:
the device marks a local record deleted with its uuid and syncs the tombstone, so
the server removes the corresponding row idempotently. Avoids the "deleted locally,
resurrected by a re-pull" bug.

## Media sync

Out-of-band from the instance push (files are large, connections flaky):

1. Register intent → presigned direct-to-storage PUT (S3/MinIO).
2. Upload the file directly (resumable), independent of the JSON sync.
3. `complete` registers it and, for audio, enqueues transcription.
4. Transcripts arrive on a later pull — never block capture or push on them.

A media upload can lag its instance by hours (until wifi). The instance is valid
without it; the file and transcript attach when they arrive.

## Security

- **In transit:** TLS only; `value`s are plaintext over the wire and encrypted
  into `InstanceAnswer.answer` server-side.
- **At rest on device:** the local store holds unencrypted informant responses
  until synced — it **must** be encrypted (SQLCipher / platform secure storage).
  This is a hard requirement, not a nicety, given the sensitivity of the data and
  the platform's existing encrypt-at-rest posture.
- **Token loss:** named per-device tokens are individually revocable from the web.

## Open decisions

1. **Form-version skew strategy** — snapshot-at-capture (recommended) vs. explicit
   form versioning. Blocks offline capture design.
2. **Answer `client_id`** — confirm adding a uuid column to `instance_answers`.
3. **Conflict policy** — confirm last-writer-wins-on-`updated_at` for the rare
   post-sync collision.
