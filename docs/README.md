# Padiush documentation

The documentation layer for the platform's roadmap. Its scope is deliberate: it
documents **contracts** (interfaces multiple parties depend on) and **decisions**
(reasoning that's expensive to reconstruct) — not the code, which is its own
source of truth and changes weekly. Docs that merely restate volatile
implementation are intentionally absent so nothing here rots into a lie.

For coding standards, commits, and workflow, see [`../AGENTS.md`](../AGENTS.md).

## Contents

| Doc | What it is | Kind |
|---|---|---|
| [data-model.md](data-model.md) | The domain map — entities, links, the folk-name → taxon pipeline, and pencilled-in roadmap slots | Living reference |
| [analysis/ethnobotany-indices.md](analysis/ethnobotany-indices.md) | Quantitative-ethnobiology index formulas, edge cases, citations, and worked examples that double as test fixtures | Scientific contract |
| [contracts/companion-api.md](contracts/companion-api.md) | The `/api/v1` HTTP contract for the mobile capture apps | Contract (proposed) |
| [contracts/sync-protocol.md](contracts/sync-protocol.md) | How offline capture reconciles with the server | Contract (proposed) |
| [decisions/](decisions/) | Architecture Decision Records — the *why* behind the roadmap | Decisions |

## Roadmap at a glance

Per [ADR 0001](decisions/0001-complete-ethnobotany-before-generalizing.md), the
sequence is: **complete the ethnobotany vertical** (built-in indices + companion
capture apps) → **then** generalize to other ethnobiology subfields
([ADR 0006](decisions/0006-multi-subfield-architecture.md)), one at a time.

This documentation layer covers everything that vertical needs before building:
the index math to implement, the API and sync contracts the companion apps build
against, the data model they extend, and the decisions that frame them.

**Progress:** the built-in indices are complete — the use-category role, the
[computation](analysis/ethnobotany-indices.md) (`EthnobiologyIndices`), and the
report page + export. The **companion capture apps** are the remaining piece of
the ethnobotany vertical.

## Decisions register

### ✅ Settled
- **Use-category modeling** — built as `InterviewItem.is_use_category`
  ([ADR 0007](decisions/0007-use-category-as-item-role.md)).
- **Index scope** — all five (RFC, UV, CI, ICF, FL) ship together; the
  use-category role is a prerequisite.
- **UV variant** — "mean use-reports per informant" (`UV = ΣUR/N`); implemented
  and matching the worked-example fixture
  ([indices spec](analysis/ethnobotany-indices.md#decisions--open-points)).
- **Mobile stack** — Expo / React Native, committed
  ([ADR 0002](decisions/0002-mobile-companion-stack.md)).
- **Transcription** — self-hosted Whisper; requires provisioning a real queue
  driver ([ADR 0005](decisions/0005-interview-transcription-whisper.md)).
- **Form-version skew strategy** *(confirmed 2026-07-12)* — **snapshot-at-capture**;
  the versioned-forms alternative is rejected. Built on the existing
  `FormStructureService` answer-detach guard
  ([sync protocol](contracts/sync-protocol.md#form-version-skew--the-case-that-bites)).
- **`instance_answers.client_id`** *(confirmed 2026-07-12)* — add a `client_id`
  uuid column for offline-created answers (the one schema change the sync model
  needs). Migration lands with the companion milestone.
- **Sanctum token model** *(confirmed 2026-07-12)* — issue a single `capture`
  ability; the per-project gate stays enforced by `ProjectPolicy` on every request,
  so tokens are user-scoped, not project-scoped
  ([companion API](contracts/companion-api.md#authentication)).
- **Post-sync conflict policy** *(confirmed 2026-07-12)* — **last-writer-wins per
  answer row** on **device edit-time** (server-clamped), with overwritten values
  kept in an audit trail so nothing is silently lost. Flips
  [ADR 0004](decisions/0004-offline-sync-model.md) to **Accepted**
  ([sync protocol](contracts/sync-protocol.md#conflict-resolution--deliberately-simple)).

### ⏳ Still open (documented with a proposed default; settle before the work)
- *None blocking the roadmap.* The one remaining open point is contract-level:
  whether `instances:sync` also pulls server-side changes (two-way) or stays
  push-only (recommended: push-only) —
  [companion API](contracts/companion-api.md#open-decisions).

## Maintaining these docs

- Change a contract or a decision → update its doc **in the same change**.
- Reverse a decision → add a superseding ADR; don't rewrite history.
- When a proposed default is confirmed, strike it from *Open decisions* and flip
  the relevant ADR/status to Accepted.
