# Offline sync protocol

**Status: built (v1).** The choreography behind the companion apps' offline
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
  protocol requires** *(✅ built 2026-07-12)*. Server keeps its own PK; `client_id`
  is the idempotency key and the device's stable reference.

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
- **After sync**, if the same answer is somehow edited from two places (e.g. two
  devices on one account, or a web correction racing a device re-sync), resolve
  **per answer row** by **last-writer-wins**. The winner is the latest **device
  edit-time** — carried per-answer in the sync payload and server-clamped against
  implausible/future values — **not** server-receipt time: receipt time would let a
  late-syncing offline edit clobber a newer web correction, the exact field
  scenario this system exists for. Overwritten values are retained in a
  **lightweight audit trail**, so a clobbered field is recoverable, never silently
  lost. This is adequate precisely because the event is rare and the data is
  additive; do **not** reach for CRDTs.
- Web-side edits to captured interviews are possible but expected to be light
  (corrections). They win only if newer (by edit-time).

## Form-version skew — the case that bites

A device caches form **v1**, goes offline, and captures against it. Meanwhile the
web edits the form to **v2** (renames an item, deletes a section). The device's
answers reference v1 items. Two ways to keep those answers valid:

**Built — validate against the current structure, and explain what is refused.**
Answers are checked against the form as it stands now. One whose item no longer
exists is `rejected` with a reason (`api.sync.item_not_in_form`), never silently
dropped, and the capture client surfaces it against the field so the recorder can
correct it or discard that one answer and let the rest of the interview through.

The device stamps each interview with the `form_version_cursor` it was recording
against, and the server stores it. That is diagnostic, not permissive: it does
not widen what is accepted, it records which structure the device was holding so
a refusal can be explained rather than merely reported.

**Why this is the right behaviour, not a shortfall.** Deleting a field on the web
already deletes its answers, behind an explicit confirmation
(`FormStructureService`'s answer-detach guard). An item that is gone therefore
means a researcher deliberately gave that data up. Accepting late arrivals for it
would resurrect exactly what they chose to remove — so refusing them is the
correct outcome, and the useful work is making the refusal legible.

**Rejected alternative — versioned forms.** Give `InterviewForm` explicit versions
and pin each instance to the version it was captured under. It is the only way to
truly accept an answer against a structure that no longer exists, and it was
rejected as too heavy for the benefit ([ADR 0004](../decisions/0004-offline-sync-model.md)).
Nothing here is waiting on it.

> **History (2026-08-06).** This section previously described
> *snapshot-at-capture*: the server accepting answers validated against the
> structure as of the cursor. That was never built, and could not be without the
> historical structures the versioned-forms alternative was rejected for. The
> cursor was sent, validated, and discarded unstored; the device never even read
> it from its own cache, so every interview arrived claiming none. The contract
> now describes what the code does, the cursor is stored, and the device sends a
> real one.

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

None — all settled; [ADR 0004](../decisions/0004-offline-sync-model.md) is
**Accepted**.

## Settled decisions

1. ✅ **Form-version skew strategy** *(2026-07-12, corrected 2026-08-06)* —
   **validate against the current structure and explain refusals**; versioned
   forms rejected. Deleting a field on the web already discards its answers
   behind a confirmation (`FormStructureService`'s answer-detach guard), so an
   item that is gone means the data was deliberately given up and late arrivals
   for it should not be resurrected. Recorded as *snapshot-at-capture* until
   2026-08-06, which described a guarantee the code never made; see
   [Form-version skew](#form-version-skew--the-case-that-bites).
2. ✅ **Answer `client_id`** *(2026-07-12)* — add a uuid column to
   `instance_answers`; server keeps its integer PK.
3. ✅ **Conflict policy** *(2026-07-12)* — **last-writer-wins per answer row** on
   **device edit-time** (server-clamped), with overwritten values kept in an audit
   trail. See [Conflict resolution](#conflict-resolution--deliberately-simple).
