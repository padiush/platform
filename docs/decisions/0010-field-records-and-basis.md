# 0010 — Record what was observed, not only what was collected

- **Status:** Accepted
- **Date:** 2026-08-22
- **Deciders:** Project owner
- **Extends:** [0008](0008-specimens-and-determinations.md) — its substance stands;
  what changes is the name and the assumption that every record is a collection

## Context

[0008](0008-specimens-and-determinations.md) gave the platform a specimen: the
thing that was picked, pressed, tagged and deposited. That is one way fieldwork
produces a record, and not the most common one.

A great deal of what a study documents is never collected. Sometimes it cannot
be — no permit covers it, the taxon is protected or culturally sensitive, the
plant is not in a collectable state, or it is a tree. Sometimes there is no
reason to: a key informant walks the researcher around and names what grows
there, and what is wanted is the knowledge, not the material. And a study often
begins by walking the area to inventory what is present, before a single
interview is recorded, so that there is something concrete to ask about later.

In every one of those cases there is a real record — a place, a date, an
observer, photographs, a description, often a vernacular name — and something
to identify later. What is absent is only the physical material.

**The distinction is not ours to invent.** Darwin Core, the vocabulary this
platform's export already speaks, models it as a single field on a single
record type: `basisOfRecord`, taking `PreservedSpecimen`, `HumanObservation`,
`LivingSpecimen`, `MaterialSample` and others. A pressed voucher and a plant
pointed out on a walk are both occurrences — a taxon, or an as-yet-unnamed
thing, at a place, on a date, evidenced somehow. What differs is the evidence.

Two further consequences follow from treating them as one thing.

**A record can change basis.** You see something on a tour, return in the right
season, and collect it. That is one record gaining a voucher, not an
observation being converted into a different kind of object.

**An absent voucher means a third thing.** 0009 already separated *not
recorded* from *not required* for permits. Observations add *cannot have one*:
a record with no physical material is not a gap in the evidence, it is a
different kind of evidence. Counted as a gap it would drag an inventory walk of
forty plants into the coverage figure and teach the researcher to ignore it.

## Decision

**Rename `specimens` to `field_records`**, and give each one a
**`basis_of_record`**.

- **`basis_of_record`** — `preserved_specimen` (the default, and what 0008
  built) · `human_observation` · `living_specimen` · `material_sample`. The
  values are Darwin Core's, so the export needs no translation layer.
- **`vernacular_name`** — the name an informant gave for it, **encrypted at
  rest**, because it is the same category of data as an interview answer and
  the platform already encrypts those.
- Collection-specific fields — accession number, repository, collecting permit
  — stay, and are simply absent on a record that was never collected.

The name changes because "specimen" stops being true. A record of something
nobody collected is a *registro de campo*, not an *ejemplar*, and an interface
that calls it one reads as wrong to the people who use it. 0008's substance is
untouched: a record is still not a taxon, still carries a determination history
with a nullable taxon, and still may or may not carry a voucher.

**Coverage counts against what can be vouchered.** A voucher figure over
records that could never have one is not a data-quality signal, so observations
are reported alongside rather than inside it.

## Consequences

- Serves the workflow a study actually has: walk first, ask later, collect only
  some of it — instead of assuming collection is where documentation begins.
- **Requires media on a field record, which does not exist yet.** For a
  collection a photograph is useful; for an observation it *is* the record,
  since there is no material to re-examine. `InstanceMedia` attaches only to an
  interview today. Until that is closed this decision is half-built, and the
  observation case does not really work.
- Encrypting `vernacular_name` keeps informant-supplied knowledge under the
  same posture wherever it is captured, rather than depending on which screen
  it was typed into.
- Generalises with the rest of the model: an observation is as real in
  ethnozoology — a bird heard and not caught — as in botany
  ([0006](0006-multi-subfield-architecture.md)).
- A rename across roughly fifty files, a migration, and an ADR that extends
  rather than supersedes. Cheapest now, while the only data is a demonstration
  study.

## Scope — what this deliberately is not

**Not a consent mechanism.** A guided tour that is not an interview still
produces attributable traditional knowledge. Encrypting the vernacular name
treats the data consistently; it does not answer who agreed to what, which
remains the open gap named in 0009.

**Not interview capture.** Showing an inventory's photographs to an informant
so they can point at a species is a capture-side feature for the companion, and
a different piece of work
([0003](0003-capture-only-companion-scope.md)).

**Not occurrence publication.** Speaking Darwin Core here is a naming choice, as
it was for the export. Publishing to GBIF stays deferred
([0009](0009-collecting-permits.md)).

## Alternatives considered

- **A separate `field_observations` table.** Rejected: it duplicates locality,
  coordinates, date, observer, determination history and media, forces two
  queries wherever records are listed or counted, and makes the ordinary case —
  seeing something, then collecting it later — a migration between tables
  rather than a field changing.
- **Keep the name, add the basis.** Rejected: the interface would call
  something an *ejemplar* that was never collected. The cost of renaming only
  grows, and the reason to pay it now is that nothing but the demonstration
  study exists.
- **Put observations in the catalog instead.** Rejected: `catalog_species` is a
  taxon and requires a genus and a name, so it cannot hold the thing an
  inventory walk actually produces — an unidentified plant, photographed, at a
  known place on a known date.
