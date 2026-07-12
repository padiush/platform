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

## Decisions register

### ✅ Settled
- **Use-category modeling** — built as `InterviewItem.is_use_category`
  ([ADR 0007](decisions/0007-use-category-as-item-role.md)).
- **Index scope** — all five (RFC, UV, CI, ICF, FL) ship together; the
  use-category role is a prerequisite.
- **Mobile stack** — Expo / React Native, committed
  ([ADR 0002](decisions/0002-mobile-companion-stack.md)).
- **Transcription** — self-hosted Whisper; requires provisioning a real queue
  driver ([ADR 0005](decisions/0005-interview-transcription-whisper.md)).

### ⏳ Still open (documented with a proposed default; settle before the work)
- **Use Value variant** — "mean use-reports per informant" (used in the spec) vs.
  a Phillips-&-Gentry uses-based count. Low stakes; lock with the fixture.
- **Form-version skew strategy** — snapshot-at-capture (*proposed*) vs. explicit
  form versioning ([sync protocol](contracts/sync-protocol.md#open-decisions)).
- **`instance_answers.client_id`** — confirm adding a uuid for offline-created
  answers (implied by the sync model).
- **Sanctum token model** — single `capture` ability + policy gate (*proposed*)
  vs. per-project token scoping.

## Maintaining these docs

- Change a contract or a decision → update its doc **in the same change**.
- Reverse a decision → add a superseding ADR; don't rewrite history.
- When a proposed default is confirmed, strike it from *Open decisions* and flip
  the relevant ADR/status to Accepted.
