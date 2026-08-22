# Data model

The domain map for Padiush as it stands today. This is a *living* document:
when the schema changes in a way that matters to the pipeline (a new entity, a
new link, a semantic role on a field), update this file in the same change.

It exists because the code is the source of truth for *fields*, but not for
*relationships and intent* — that's what's expensive to reconstruct later.

## The pipeline in one line

A **project** scopes a **team** and an **interview instrument** (form). Field
workers record **interviews** (instances) of that form; each **answer** captures
what an informant said. Answers on a designated field are **linked** to a
**catalog species** (folk name → scientific taxon). Linked answers are the
"use reports" that feed analysis and export.

```
Project ─┬─ ProjectAccess ── ProjectCapability      (who can do what)
         ├─ ProjectInvite                            (pending access grants)
         ├─ InterviewForm ── InterviewSection ── InterviewItem
         │        └─ InterviewInstance ── InstanceAnswer ──┐
         └─ CatalogSpecies ───────────────────────────────┘  (the link)
                    └─ CatalogSpeciesPhoto
```

## Entity reference

| Entity | Purpose | Key fields | PK |
|---|---|---|---|
| `User` | Account | `name`, `email`, `system_admin` | int |
| `Project` | Study container | `name`, `author`, `institution`, `author_email`, `country`, `finished`, `published`, `shared`, `user_id` (owner) | int |
| `ProjectCapability` | A role = a set of permission flags | `name` + 8 booleans (below) | int |
| `ProjectAccess` | A user's role on a project | `user_id`, `project_id`, `project_capability_id` — **unique** `(user_id, project_id)` | int |
| `ProjectInvite` | Pending access grant, by email | `project_id`, `inviting_user_id`, `invited_user_id?`, `invited_name`, `invited_email`, `project_capability_id`, `expires_at` | int |
| `InterviewForm` | An interview instrument | `project_id`, `name`, `description`, `is_active` | int |
| `InterviewSection` | A group of items; may repeat | `interview_form_id`, `name`, `order`, `repeatable` | int |
| `InterviewItem` | A question | `interview_section_id`, `label`, `name`, `type`, `required`, `options[]`, `link_to_species`, `is_use_category`, `order` | int |
| `InterviewInstance` | **One completed interview** | `interview_form_id`, `user_id` (recorder), `captured_at?`, `location_lat?`/`location_lng?`/`location_accuracy_m?`/`location_captured_at?` (GPS) | **uuid** |
| `InstanceAnswer` | One informant response to one item | `interview_instance_id` (uuid), `interview_section_id`, `interview_item_id`, `repeatable_index`, `answer` (**encrypted**), `catalog_species_id?`, `client_id?` (device uuid), `edited_at?` (LWW key) | int |
| `InstanceAnswerRevision` | Overwrite audit trail for last-writer-wins | `instance_answer_id`, `answer` (**encrypted**), `catalog_species_id?`, `edited_at?`, `source` — immutable | int |
| `InstanceMedia` | Audio/photo capture artifact | `interview_instance_id` (uuid), `client_id` (device uuid), `kind` (audio·photo), `storage_disk`/`storage_key`, `content_type`, `byte_size?`, `duration_s?`, `status`, `transcription_status?`, `transcription_text?` (**encrypted**), `captured_at?` | int |
| `CatalogSpecies` | A scientific taxon in the project catalog | `project_id`, `family`, `genus`, `name`, `authority`, optional `metadata` | int |
| `CatalogSpeciesPhoto` | Reference image for a taxon | `catalog_species_id`, … | int |
| `ChartPreference` | Persisted per-field chart choice (data viewer) | field key, chart type | int |

### Item types

`InterviewItem.type` ∈ `text` · `number` · `date` · `multi` · `select`.
`multi`/`select` carry `options[]`. `link_to_species = true` marks the item
whose answer is a **folk/vernacular name** to be linked to a `CatalogSpecies`.

### The link (the heart of the platform)

`InstanceAnswer.catalog_species_id` is the folk-name → taxon reconciliation. It
is set by the species-linking flow (`app/Services/SpeciesLinkingList.php` +
`InterviewDataController::handleLinkRequest` / `handleBulkLinkRequest`), never at
capture time. An answer with a non-null `catalog_species_id` is a **linked use
record**; the triple `(instance, answer, species)` is the unit that analysis
counts. See [analysis/ethnobotany-indices.md](analysis/ethnobotany-indices.md).

### Repeatable sections

A `repeatable` section yields many answer sets per instance, distinguished by
`InstanceAnswer.repeatable_index`. This is how one interview produces many
plant-use records — the natural data shape for ethnobiology.

## Authorization

Per-project RBAC. A user's `ProjectAccess` points at a `ProjectCapability` whose
boolean flags are checked in `app/Policies/ProjectPolicy.php` via `can()`:

`manage_project` · `manage_users` · `manage_forms` · `record_data` ·
`manage_data` · `generate_reports` · `view_catalog` · `edit_catalog`

Seeded roles: **Administrador del proyecto** (all), **Usuario técnico**
(`record_data`, `generate_reports`, `view_catalog`, `edit_catalog`), **Usuario
de consulta** (`generate_reports`, `view_catalog`). Because the policy is the
single authorization surface, it applies identically to web (Inertia) and to any
future token-authenticated API — see
[decisions/0002-mobile-companion-stack.md](decisions/0002-mobile-companion-stack.md).

## Where the roadmap touches the model

These were pencilled-in slots so the model could absorb the roadmap without a
rewrite. Most have since been built. Each is marked below: ✅ built, 📐 decided
in an ADR but not yet built, and unmarked for what is still only pencilled in.

- **`Project.subfield`** — a first-class attribute selecting the ethnobiology
  subfield (ethnobotany, ethnomycology, ethno-ornithology, …). Cheap to add now,
  expensive to retrofit. Drives which taxonomic authority the catalog validates
  against and the analysis/vocabulary defaults. See
  [decisions/0006-multi-subfield-architecture.md](decisions/0006-multi-subfield-architecture.md).
- **A use-category role on items** — ✅ **built.** `InterviewItem.is_use_category`
  marks the field whose answer is a use category, mirroring `link_to_species`
  (mutually exclusive with it). This supplies the *u* the indices need
  ([decisions/0007-use-category-as-item-role.md](decisions/0007-use-category-as-item-role.md)).
  The computation that consumes it (`EthnobiologyIndices`), the report page and
  the export are built too.
- **`InstanceAnswer` client UUID** — ✅ **built (2026-07-12).**
  `instance_answers.client_id` (uuid, nullable, unique) is the device-minted
  idempotency key; the server keeps its own integer PK. Offline capture mints it
  on-device. See [contracts/sync-protocol.md](contracts/sync-protocol.md).
- **Conflict-policy fields** — ✅ **built (2026-07-12).** `instance_answers.edited_at`
  is the device edit-time (the last-writer-wins key), and `instance_answer_revisions`
  is the overwrite audit trail retaining clobbered values
  ([decisions/0004-offline-sync-model.md](decisions/0004-offline-sync-model.md)).
  Web answer saves stamp `edited_at` too, so corrections take part in the policy.
- **Capture artifacts** — ✅ **audio + photo built (2026-07-12)** as `instance_media`
  (kind, storage key, upload status, and audio transcription status/text);
  `interview_instances` gained `captured_at` and a GPS location. See
  [contracts/companion-api.md](contracts/companion-api.md).
- **Specimens and determinations** — 📐 **decided, not yet built.** The
  voucher/specimen slot pencilled in here is now
  [decisions/0008-specimens-and-determinations.md](decisions/0008-specimens-and-determinations.md):
  `specimens` and `determinations` become their own tables, because a specimen is
  not a taxon and one specimen carries many determinations over time. Vouchers
  stay optional and are reported as coverage. Still pencilled in beside it: (for
  zoology subfields) conservation status.
