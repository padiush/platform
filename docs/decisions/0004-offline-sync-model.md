# 0004 — Offline sync: client-owned records, UUIDs, last-writer-wins

- **Status:** Accepted (2026-07-12)
- **Deciders:** Project owner

## Context

Offline capture with later sync is the hard, easy-to-underestimate part of the
companion milestone. The design space runs from trivial (last-write-wins) to
elaborate (CRDTs, operational transforms). The right point depends on the domain:
here, capture is **one recorder per interview**, and — per
[0003](0003-capture-only-companion-scope.md) — the device is the sole author of
its captures until they sync. Genuine concurrent edits to the same answer are rare.

## Decision

- **Records are client-owned until synced.** No server state exists to conflict
  with during capture.
- **Client-generated UUIDs** identify offline-created records. Instances are
  already UUID-keyed; `instance_answers` gains a `client_id` uuid (the one schema
  change). ✅ *Confirmed 2026-07-12.*
- **Idempotent upserts** keyed on those UUIDs; retrying a sync batch is a no-op.
- **Last-writer-wins per answer row** for the rare post-sync collision, keyed on
  the latest **device edit-time** — carried per-answer in the sync payload and
  server-clamped against implausible/future values — **not** server-receipt time,
  which would let a late-syncing offline edit clobber a newer web correction.
  Overwritten values are kept in a **lightweight audit trail**, so a clobbered
  field is recoverable, never silently lost. **No CRDTs.** ✅ *Confirmed 2026-07-12.*
- **Form-version skew** is handled by snapshot-at-capture (preferred) built on the
  existing `FormStructureService` answer-detach guard. ✅ *Confirmed 2026-07-12.*

Full mechanics: [../contracts/sync-protocol.md](../contracts/sync-protocol.md).

## Consequences

- The sync engine stays comprehensible and testable — the failure modes are
  enumerable (dropped batch, partial success, form skew, tombstone) rather than
  emergent.
- Retry-safety over flaky field connectivity is structural, not bolted on.
- Cost: last-writer-wins can lose a field in the rare true-concurrent case — but
  the audit trail retains the overwritten value, so the loss is recoverable, not
  silent. Accepted given how rare the collision is here; revisit (e.g. conflict
  flagging) only if multi-device shared accounts become common.
- Schema touch for the companion milestone (all **confirmed 2026-07-12**): a
  `client_id` uuid on `instance_answers`, a per-answer **edit timestamp** (the LWW
  key) and an **overwrite audit trail** for the conflict policy, plus
  snapshot-at-capture for form-version skew. None built yet.

## Alternatives considered

- **CRDTs / OT.** Rejected: solves a concurrency problem this domain doesn't have,
  at large complexity cost.
- **Server-authoritative with online-only writes.** Rejected: defeats the entire
  purpose (offline field capture).
- **Bidirectional editable sync everywhere.** Rejected with
  [0003](0003-capture-only-companion-scope.md) — capture-only removes the need.
