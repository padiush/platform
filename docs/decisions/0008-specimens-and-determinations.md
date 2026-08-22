# 0008 — Model the specimen as its own entity, with a determination history

- **Status:** Accepted
- **Date:** 2026-08-22
- **Deciders:** Project owner

## Context

`catalog_species` is `project_id · family · genus · name · authority · metadata`.
That is a **taxon** record, and it models the *end* of identification. Fieldwork
starts well before it:

> an informant names a plant → a specimen is collected → photographed in situ →
> pressed and tagged with a collection number → deposited → **identified later,
> often by someone else** → the determination is sometimes revised

Padiush joins that story at the last step. Everything before it happens in a
notebook, and three consequences follow.

**A specimen is not a taxon, but today they are the same object.** One taxon has
many specimens; one specimen has many determinations over time, each with a
determiner, a date and a confidence, of which the current one is merely the
latest. That revision history is what researchers argue about, and a single
`catalog_species` row cannot hold it.

**Vouchers cannot be recorded at all** — the word does not appear anywhere in the
codebase. Reporting conventions in quantitative ethnobotany identify the voucher
specimen behind each taxon and where it was deposited, so that a determination
can be re-examined by someone who was not there. A platform that carries a study
to publishable numbers cannot produce a complete species table without them.

**A herbarium without a curator has no accession numbers.** Specimens deposited
with a community rather than an institution are a normal outcome of fieldwork,
and they arrive with no registry, no accession numbers and therefore no
vouchers. Most ethnobotany tooling assumes a university herbarium down the
corridor; serving the case where there isn't one is not a niche, it is most of
the world outside capital cities. A herbarium with no curator is a
software-shaped problem, and it is the same problem this platform already
answers elsewhere: no connectivity → offline capture, no institutional IT →
self-hostable, no institutional data governance → researcher-as-controller.

Capture is already closer than it looks. `InstanceMedia::KIND_PHOTO` is built and
the companion photographs in the field today. What is missing is a **specimen**
for a photograph to belong to.

## Decision

Add two tables — **`specimens`** and **`determinations`** — and let a project act
as its own issuing authority for accession numbers.

- **`specimens`** — collector, collection number, collection date, locality
  (free text plus the coordinates the companion already captures), the
  repository it was deposited with (an institution *or* a community herbarium),
  notes, and an optional link to the `instance_answer` that produced it.
- **`determinations`** — specimen, taxon (**nullable**: `indet.` is a real and
  common state), determiner, date, qualifier (`cf.` / `aff.` / `sp.`), and a
  current flag. The current determination is the latest, not the only one.

Three rules govern how it behaves:

**A voucher is never required.** Capture never blocks anywhere else in Padiush —
GPS does not block the record button, an incomplete form does not block Done —
and plenty of legitimate work is unvouchered: market surveys, cultivated
species, pure observation. Requiring a voucher would break the rule the rest of
the product holds.

**Coverage is reported instead.** "34 of 41 species voucher-backed" is a figure a
paper can print. This follows the pattern the indices specification already sets
for unlinked answers — *surface it as a data-quality figure so a researcher knows
the denominator* — rather than the two failure modes of requiring vouchers or
leaving them optional and invisible.

**Specimen photographs and taxon photographs are different things.** The dormant
`catalog_species_photos` table carries `author`, `license` and `license_url`,
which is the shape of *reference* imagery for a taxon (the kind
`INaturalistPhoto` fetches). A field photograph of a collection belongs to the
specimen. Keep them separate; do not repurpose one for the other.

## Consequences

- **A complete species table becomes producible** — voucher number and
  determination per taxon, with voucher coverage as a reportable data-quality
  figure rather than a silent omission.
- **Turns "no curator, so no vouchers" into "vouchers exist, issued locally,
  with their determination history attached."** A project mints its own
  accession numbers.
- **Serves [0006](0006-multi-subfield-architecture.md) better than the present
  model.** A *specimen* generalises cleanly to ethnozoology — skins, skulls,
  tissue — whereas *species-registered-by-hand* does not.
- **It is Darwin Core in all but name.** Collector, collection number, event
  date, determiner, determination date, qualifier are `recordedBy`,
  `recordNumber`, `eventDate`, `identifiedBy`, `dateIdentified`,
  `identificationQualifier`. That is the vocabulary GBIF ingests, and Padiush
  already *reads* GBIF for distributions — so this leaves the door open to
  contributing rather than only consuming, without walking through it yet.
- Requires a catalog UI change (specimens under a taxon, determination history),
  export changes, and a migration. No companion change this version.

## Scope — what this deliberately is not

**Not a herbarium management system.** Specify, BRAHMS and Symbiota exist and
there is no reason to compete with them. The minimum that closes the loop is two
tables; loans, curation workflows, and barcode printing are theirs.

**Not GBIF publication.** Occurrence records and use-reports are separable, and
publishing occurrences while the ethnobotanical knowledge stays private is the
only coherent offer — but GBIF requires a registered publisher endorsed by a
node, which is an institutional arrangement rather than a feature. Deferred to
its own ADR, opt-in per project when it comes, for Nagoya, community agreement
and CARE reasons as much as technical ones.

**Not field capture of specimens.** The companion stays capture-only for
interviews this version ([0003](0003-capture-only-companion-scope.md)). The
offline store is encrypted and its schema is migrated on-device, so a shape
committed to there is expensive to change; the web side proves the model first
and companion support follows once it has been used in anger.

## Alternatives considered

- **Add a `voucher_number` string to `catalog_species`.** Rejected: it records
  the number while losing everything that makes it meaningful — which specimen,
  who determined it, when, and what the determination was before. It also
  re-asserts one-specimen-per-taxon, which is the error being corrected.
- **Model determinations as a `metadata` blob on the specimen.** Rejected:
  determination history is queried and reported (current name, coverage,
  revisions), and burying it in JSON forfeits that to save one migration.
- **Wait until a study asks for it.** Rejected: the omission is structural
  rather than situational. Any study that deposits specimens needs it, and the
  longer the catalog conflates specimen with taxon the more data is recorded in
  a shape that cannot express what was actually collected.
