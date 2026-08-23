# Padiush documentation

The documentation layer for the platform's roadmap. Its scope is deliberate: it
documents **contracts** (interfaces multiple parties depend on) and **decisions**
(reasoning that's expensive to reconstruct) — not the code, which is its own
source of truth and changes weekly. Docs that merely restate volatile
implementation are intentionally absent so nothing here rots into a lie.

For coding standards, commits, and workflow, see
[`../CONTRIBUTING.md`](../CONTRIBUTING.md).

## Contents

| Doc | What it is | Kind |
|---|---|---|
| [data-model.md](data-model.md) | The domain map — entities, links, the folk-name → taxon pipeline, and pencilled-in roadmap slots | Living reference |
| [analysis/ethnobotany-indices.md](analysis/ethnobotany-indices.md) | Quantitative-ethnobiology index formulas, edge cases, citations, and worked examples that double as test fixtures | Scientific contract |
| [contracts/companion-api.md](contracts/companion-api.md) | The `/api/v1` HTTP contract for the mobile capture apps | Contract (v1, built) |
| [api/openapi.yaml](api/openapi.yaml) · [Postman](api/padiush-companion.postman_collection.json) | Machine-readable `/api/v1` spec and a runnable request collection | Generated contract |
| [contracts/sync-protocol.md](contracts/sync-protocol.md) | How offline capture reconciles with the server | Contract (v1, built) |
| [decisions/](decisions/) | Architecture Decision Records — the *why* behind the roadmap | Decisions |
| [deployment/public-site.md](deployment/public-site.md) | Enabling the public pages and publishing your own legal documents, for anyone running their own instance | Operator guide |

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
report page + export. The **companion capture API** (`/api/v1`) is built — token
auth, offline pull (`me`, project `bundle`), idempotent interview sync with
last-writer-wins, and audio/photo media with transcription plumbing (gated on
ADR 0005). The **mobile companion app** is built too, in its own repository
(`padiush-companion`): Expo / React Native, an encrypted offline store, capture
of answers, GPS, audio and photos, and the full sync loop against this API. The
photographs and audio it syncs are viewable on the web as well, read-only — the
device owns that lifecycle.

That completes the ethnobotany vertical's moving parts. One further piece of
surface has been added since, and it is built too: **field records**
([ADR 0008](decisions/0008-specimens-and-determinations.md),
[0009](decisions/0009-collecting-permits.md),
[0010](decisions/0010-field-records-and-basis.md)). The catalog models a *taxon*,
which is the end of identification; what fieldwork produces is a documented
encounter, which carries many determinations over time and is what a voucher
number identifies. `basis_of_record` keeps a pressed specimen and something only
seen in one table without pretending they are the same thing — much of what a
study documents is never collected, and a record that could never carry a
voucher is not a gap in the evidence. Built: the two tables and per-project
accession numbering, a records list that follows the field order (recorded
first, identified later, deposited later still), collecting permits managed
under the catalog and chosen when a record is made, photographs and audio on a
record as well as on an interview, a `Voucher No.` and permit column on the
species-indices export, a Darwin-Core-termed export of the records themselves,
and voucher, permit and observation coverage stated on the report page beside
the unlinked-citation figure.

Field records were built on the web first, which was a sequencing choice rather
than a judgment about where a record belongs. **The companion is where the next
piece goes** ([ADR 0011](decisions/0011-companion-field-records.md)): recording
is a field act, and one of its moments — an informant naming a plant during an
interview — the web cannot reach at all. Decided and **not yet built**; the
device will author the recorded stage only, with identification and deposit
staying here.

What remains before field deployment for sensitive studies is hardening rather
than new surface — tracked as: **resumable media upload** (single PUT today, so
a long recording restarts from the beginning on a lost connection),
**transcription** (null-bound plumbing until a real queue and a self-hosted
Whisper are provisioned, per
[ADR 0005](decisions/0005-interview-transcription-whisper.md)), and testing on
physical devices.

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
- **Form-version skew strategy** *(confirmed 2026-07-12, wording corrected
  2026-08-06)* — answers are validated against the form's **current** structure
  and a departed item is refused with an actionable reason; the versioned-forms
  alternative is rejected. Rests on the existing `FormStructureService`
  answer-detach guard: deleting a field already discards its answers behind a
  confirmation, so a late arrival for it should not resurrect what a researcher
  chose to remove
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

- **Companion scope and sync direction** *(confirmed 2026-08-14)* — the companion
  captures only, and `instances:sync` is **push-only**; forms travel the other way
  as a read-only pull. Built and released on this basis, which flips
  [ADR 0003](decisions/0003-capture-only-companion-scope.md) to **Accepted**
  ([companion API](contracts/companion-api.md#settled-decisions)).
- **Field records on the companion** *(accepted 2026-08-23)* — recording a
  field record is a field act, so the device authors it: basis, vernacular name,
  collection number, coordinates, photographs and the permit, including from
  inside an interview. Identification and deposit stay on the web, which keeps
  `records:sync` push-only and extends
  [ADR 0003](decisions/0003-capture-only-companion-scope.md) rather than
  reversing it ([ADR 0011](decisions/0011-companion-field-records.md)).
- **Field records and basis** *(accepted 2026-08-22, media added the same day)*
  — `specimens` becomes `field_records`, and `basis_of_record` distinguishes a
  pressed specimen from something only seen. Much of what a study documents is
  never collected, and a record that could never carry a voucher is not a gap in
  the evidence, so observations are counted beside voucher coverage rather than
  inside it. The vernacular name is encrypted, as an interview answer is
  ([ADR 0010](decisions/0010-field-records-and-basis.md)).
- **Collecting permits** *(accepted 2026-08-22)* — a `collecting_permits` table
  per project; a specimen carries either a permit or a stated reason none was
  required, never both, so coverage can tell a lawful exemption from a missing
  record. A reference record only — nothing validates that a permit is genuine,
  current, or covers what was collected
  ([ADR 0009](decisions/0009-collecting-permits.md)).
- **Specimen as its own entity** *(accepted 2026-08-22)* — `specimens` and
  `determinations` become their own tables rather than fields on
  `catalog_species`, with a nullable taxon so `indet.` is representable, and
  vouchers optional but reported as coverage. A project issues its own accession
  numbers, which is what a herbarium with no curator needs
  ([ADR 0008](decisions/0008-specimens-and-determinations.md)).

### ⏳ Still open
- *Nothing.* Every decision recorded here is settled; what remains is
  implementation, listed under **Progress** above.

## Maintaining these docs

- Change a contract or a decision → update its doc **in the same change**.
- Reverse a decision → add a superseding ADR; don't rewrite history.
- When a proposed default is confirmed, strike it from *Open decisions* and flip
  the relevant ADR/status to Accepted.
