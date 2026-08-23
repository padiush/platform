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
         ├─ CatalogSpecies ───────────────────────────────┘  (the link)
         │          └─ CatalogSpeciesPhoto                   (reference imagery)
         ├─ CollectingPermit                                 (authority to collect;
         │                                                    one covers many)
         └─ FieldRecord ─┬─ Determination → CatalogSpecies   (what it was identified
                         │                                    as — nullable, `indet.`)
                         └─ → CollectingPermit               (or a stated reason
                                                              none was required)
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
| `Media` | Audio/photo capture artifact, on an interview **or** a field record (never both) | `interview_instance_id?` (uuid), `field_record_id?`, `client_id` (device uuid), `kind` (audio·photo), `storage_disk`/`storage_key`, `content_type`, `byte_size?`, `duration_s?`, `status`, `transcription_status?`, `transcription_text?` (**encrypted**), `captured_at?` | int |
| `CatalogSpecies` | A scientific taxon in the project catalog | `project_id`, `family`, `genus`, `name`, `authority`, optional `metadata` | int |
| `CatalogSpeciesPhoto` | Reference image for a taxon | `catalog_species_id`, … | int |
| `FieldRecord` | **One documented encounter** — collected, or only seen (`basis_of_record`) | `project_id`, `basis_of_record` (Darwin Core: `preserved_specimen`·`human_observation`·`living_specimen`·`material_sample`), `vernacular_name?` (**encrypted**), `accession_number?` (the voucher, unique per project), `collection_number?` (the collector's field number), `collector?`, `collected_on?`, `locality?`, `location_lat?`/`location_lng?`, `repository?`, `collecting_permit_id?` **or** `permit_exemption?` (never both), `notes?`, `instance_answer_id?` | int |
| `CollectingPermit` | The authorisation material was collected under | `project_id`, `authority`, `reference` (**unique per project**), `issued_on?`, `expires_on?`, `notes?` | int |
| `Determination` | What a record was identified as, by whom, when | `field_record_id`, `catalog_species_id?` (**null = `indet.`**), `determiner?`, `determined_on?`, `qualifier?` (`cf`·`aff`·`sp`), `is_current`, `notes?` | int |
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
- **Capture artifacts** — ✅ **audio + photo built (2026-07-12)** as
  `instance_media`, renamed to `media` on 2026-08-23 when it stopped belonging
  only to interviews
  (kind, storage key, upload status, and audio transcription status/text);
  `interview_instances` gained `captured_at` and a GPS location. See
  [contracts/companion-api.md](contracts/companion-api.md).
- **Field records and determinations** — ✅ **built (2026-08-22)** as
  `field_records` and `determinations`, per
  [decisions/0008-specimens-and-determinations.md](decisions/0008-specimens-and-determinations.md).
  A specimen is not a taxon, and one specimen carries many determinations over
  time; the taxon on a determination is **nullable**, because `indet.` is a real
  state. A voucher is never required — a project mints its own accession numbers
  (`App\Services\AccessionNumbers`, a configurable prefix over a per-project
  sequence) and coverage is reported rather than enforced. The catalog carries a
  project-level list of collections — a specimen is recorded before anything is
  known about its taxonomy, identified later, and deposited later still — with
  a narrower view under each taxon. **A record need not have been collected**
  ([decisions/0010-field-records-and-basis.md](decisions/0010-field-records-and-basis.md)):
  `basis_of_record` distinguishes a pressed specimen from something only seen,
  observations are counted apart from voucher coverage rather than inside it,
  and `vernacular_name` is encrypted like an interview answer. The
  species-indices export carries a `Voucher No.` column, the record list exports
  on its own in Darwin Core terms, and coverage is stated on the report page.
  A record carries **photographs and audio** (`media`, renamed from
  `instance_media` when it stopped belonging only to interviews) — for an
  observation those are the whole of the evidence, since no material survives
  to re-examine. Uploaded from the browser as a plain post rather than the
  companion's presigned handshake, and served through an authorized route so
  losing access to a project loses access to its photographs at the same
  moment.
  Still pencilled in beside it: (for zoology subfields) conservation status.
- **Collecting permits** — ✅ **data layer built (2026-08-22)** as
  `collecting_permits`, per
  [decisions/0009-collecting-permits.md](decisions/0009-collecting-permits.md).
  One permit covers many collections, so it is a table rather than a string
  repeated on each specimen. A specimen carries a permit **or** a stated reason
  none was required (`private_land`·`cultivated`·`market`·`other`), never both —
  the two absences mean different things and coverage counts them separately. A
  reference record only: nothing validates that a permit is genuine, current, or
  covers what was collected. Permits are managed under the catalog and chosen
  when a collection is recorded — a permit is held before the fieldwork, so
  unlike a voucher it is known at that moment. The species-indices export
  carries the permit beside the voucher, and the report page states the three
  permit states.
