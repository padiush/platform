# 0004 — Offline sync: client-owned records, UUIDs, last-writer-wins

- **Status:** Proposed
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
  change).
- **Idempotent upserts** keyed on those UUIDs; retrying a sync batch is a no-op.
- **Last-writer-wins on `updated_at`** for the rare post-sync collision. **No
  CRDTs.**
- **Form-version skew** is handled by snapshot-at-capture (preferred) built on the
  existing `FormStructureService` answer-detach guard.

Full mechanics: [../contracts/sync-protocol.md](../contracts/sync-protocol.md).

## Consequences

- The sync engine stays comprehensible and testable — the failure modes are
  enumerable (dropped batch, partial success, form skew, tombstone) rather than
  emergent.
- Retry-safety over flaky field connectivity is structural, not bolted on.
- Cost: last-writer-wins can silently lose a field in the rare true-concurrent
  case. Accepted given how rare that is here; revisit only if multi-device shared
  accounts become common.
- Requires a schema addition (`client_id`) and a decision on form-version skew
  strategy before building.

## Alternatives considered

- **CRDTs / OT.** Rejected: solves a concurrency problem this domain doesn't have,
  at large complexity cost.
- **Server-authoritative with online-only writes.** Rejected: defeats the entire
  purpose (offline field capture).
- **Bidirectional editable sync everywhere.** Rejected with
  [0003](0003-capture-only-companion-scope.md) — capture-only removes the need.
