# Companion API contract (proposed)

**Status: proposed.** `routes/api.php` today holds only Sanctum's default
`/user` route — this document is the contract to *build against*, not one that
exists. It is the interface between the web platform and the mobile companion
apps; both sides depend on it, so it is versioned and written down. When
implemented, formalize this as an OpenAPI document generated from the routes.

Scope follows [0003 — capture-only companion scope](../decisions/0003-capture-only-companion-scope.md):
the API serves **field capture** (record interviews, audio, GPS, photos) and the
sync that backs it. Form design, species linking, analysis, and export stay on
the web and are **out of scope** for this API.

## Conventions

- Base path **`/api/v1`**. Breaking changes bump the version.
- **Auth:** Laravel Sanctum bearer tokens (`Authorization: Bearer <token>`),
  `auth:sanctum` on every route. Package already installed.
- **Authorization:** the same `ProjectPolicy` capability checks as the web —
  capture requires `record_data` on the project. No new authz surface; see
  [../data-model.md](../data-model.md).
- **JSON envelope** on error: `{ "message": "<i18n-key-or-text>",
  "message_type": "error", "errors": { field: [..] } }`, matching the web's
  flash convention. Status codes: `401` unauth, `403` policy denial, `404`
  not-found/out-of-scope, `409` conflict, `422` validation.
- **Idempotency:** all writes are keyed on **client-generated UUIDs** and are
  safe to retry. See [sync-protocol.md](sync-protocol.md).
- **Rate limiting:** reuse the existing `throttle:api` middleware; token issuance
  is throttled harder.

## Authentication

Mobile can't use the web session cookie, so devices hold a personal access token.

```
POST /api/v1/tokens
  body: { email, password, device_name }
  → 200 { token, user: { id, name, email } }
  → 422 invalid credentials
```

- `device_name` names the token so a user can revoke a lost device.
- **Token abilities (proposed):** issue with a single `capture` ability; the
  per-project gate is still enforced by the policy on every request, so the token
  is user-scoped, not project-scoped.
- **Prohibited handling:** the app must never store the password; only the
  returned token, in the platform secure store (Keychain / Keystore).
- Revocation: `DELETE /api/v1/tokens/current` (this device) — web manages the
  rest via Sanctum.

## Pull — cache what the device needs offline

```
GET /api/v1/me
  → { user, projects: [ { id, name, subfield?, capabilities, updated_at } ] }
```
Only projects where the user has `record_data`. `capabilities` lets the app hide
what the user can't do.

```
GET /api/v1/projects/{project}/bundle?since=<iso8601>
  → { form_version_cursor, forms: [ Form ], server_time }
```
The offline capture bundle: every **active** form's full structure. `?since`
makes it incremental (return only forms changed after the cursor). `Form` =
`{ id, name, description, is_active, updated_at, sections: [ { id, name, order,
repeatable, items: [ Item ] } ] }`; `Item` = `{ id, label, name, type, required,
options, link_to_species, order }`.

> The device does **not** pull the species catalog — linking is a web-side task
> ([0003](../decisions/0003-capture-only-companion-scope.md)). It captures the
> raw folk name; reconciliation happens later on the web.

## Push — sync captured interviews

One idempotent, batched upsert. Instances are already UUID-keyed server-side;
answers get a **client UUID** (`client_id`) for offline creation — the one schema
change this requires (`instance_answers` is integer-PK today, see
[../data-model.md](../data-model.md)).

```
POST /api/v1/projects/{project}/instances:sync
  headers: Idempotency-Key: <uuid>            (optional, for the whole batch)
  body: {
    instances: [
      {
        id: <client-uuid>,                    // instance UUID, minted on device
        interview_form_id, captured_at,
        location?: { lat, lng, accuracy_m, captured_at },
        answers: [
          { client_id: <uuid>, interview_section_id, interview_item_id,
            repeatable_index, value }
        ]
      }
    ]
  }
  → 200 {
      results: [ { id, status: "created"|"updated"|"unchanged"|"rejected",
                   errors? } ]
    }
```

- **Upsert semantics:** match on `id` (instance) / `client_id` (answer); create if
  absent, update if present and the device is the owner of the record. Retrying
  the same batch is a no-op (`unchanged`).
- `value` is plaintext over the wire (TLS); the server encrypts it into
  `InstanceAnswer.answer` on receipt. The device is responsible for **encryption
  at rest** locally (informant data is sensitive).
- Answers referencing an item the form no longer has are `rejected` with a clear
  error rather than silently dropped — see form-version skew in
  [sync-protocol.md](sync-protocol.md).
- Partial success is normal: each element carries its own status.

## Media — audio & photos (offload to object storage)

Large files over field connectivity should not stream through the app server.
Use a presigned direct-to-storage flow against the existing S3/MinIO bucket.

```
POST /api/v1/instances/{instance}/media/intent
  body: { kind: "audio"|"photo", content_type, byte_size, client_id }
  → { upload_url, storage_key, expires_at }        // presigned PUT

  (device PUTs the file directly to upload_url, resumable/chunked)

POST /api/v1/instances/{instance}/media/complete
  body: { client_id, storage_key, kind, duration_s? }
  → { id, status: "stored", transcription?: "queued" }
```

- On `complete` for `kind=audio`, the server enqueues a **transcription job**
  (self-hosted Whisper — [0005](../decisions/0005-interview-transcription-whisper.md)).
  This requires a real queue driver; the app is on `QUEUE_CONNECTION=sync` today,
  which is a prerequisite to change.
- Transcript delivery: the device learns of it on the next pull —
  `GET /api/v1/instances/{instance}` returns
  `{ …, transcription: { status: "queued"|"processing"|"done"|"failed", text? } }`.

## Not in this API

Form design, species linking, index computation, export, user/role management —
all web-only. If a future milestone needs programmatic access to those, it gets
its own contract; it does not bend the capture API.

## Open decisions

- Token model: single `capture` ability + policy gate (proposed) vs. per-project
  token scoping.
- Whether `instances:sync` should also *pull* server-side changes to the same
  instances (two-way) or stay push-only (recommended: push-only; the device owns
  its captures until synced — [sync-protocol.md](sync-protocol.md)).
