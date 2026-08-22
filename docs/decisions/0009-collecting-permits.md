# 0009 — Record the permit a specimen was collected under

- **Status:** Accepted
- **Date:** 2026-08-22
- **Deciders:** Project owner

## Context

Collecting wild biological material is regulated almost everywhere. A national
environmental authority issues a permit naming who may collect, where, which
taxa, for how long, and often how many specimens — El Salvador's MARN,
Guatemala's CONAP, Peru's SERFOR, Brazil's SISBIO, and their equivalents
elsewhere. The authority differs by country; the requirement does not.

The permit is the legal basis for a collection existing at all, and it outlives
the fieldwork: a herbarium asks for it on deposit, a journal may ask for it at
submission, and an authority may ask years later which material was taken under
which authorisation.

[ADR 0008](0008-specimens-and-determinations.md) gave the platform a specimen
but no way to say under what authority it was taken. Today that fact lives in a
folder somewhere, which is the same shape as the gaps this platform already
answers: no connectivity → offline capture, no institutional IT →
self-hostable, no institutional data governance → researcher-as-controller, no
herbarium curation → project-issued accession numbers. A university has a
research office that tracks permits and their expiry dates. An independent
researcher, or a community project, has the folder.

**One permit covers many collections.** Authority, reference number and validity
period are properties of the permit, not of each specimen taken under it, and
the question a researcher actually asks — *which specimens did I collect under
this permit?* — cannot be answered by a string repeated on every row.

**Not every lawful collection needs one.** Material from private land with the
owner's agreement, cultivated plants, and specimens bought in a market are
ordinarily outside the permit regime. An absent permit is therefore ambiguous in
a way an absent voucher is not: it may mean *not recorded*, or it may mean *not
required*.

## Decision

Add a **`collecting_permits`** table, scoped to a project, and let a specimen
point at one — or state why none applies.

- **`collecting_permits`** — issuing authority, reference number, issued and
  expiry dates, and notes for scope (taxa, area, quantity) as the permit words
  it. A **reference record, not a document store**: the permit itself stays
  wherever the researcher keeps it.
- **`specimens.collecting_permit_id`** — nullable, and
  **`specimens.permit_exemption`** — nullable, from a small vocabulary
  (`private_land`, `cultivated`, `market`, `other`), with the specimen's notes
  carrying the detail. A specimen has one or the other or neither; never both.

Three rules, following the ones 0008 already sets:

**Recording is not validating.** Padiush stores what the researcher states. It
does not check that a permit is genuine, current, or covers what was collected,
and must not present itself as having done so. An expiry date is displayed
because it is on the permit, not as a compliance verdict.

**A permit is never required.** Capture does not block anywhere else in this
platform, and a specimen recorded before its paperwork is found is better than
one not recorded.

**Coverage distinguishes the two absences.** "38 under a permit, 3 exempt, 0
unrecorded" is a figure worth printing. "38 of 41" would flag three lawful
collections as gaps and teach the researcher to ignore the number.

## Consequences

- Answers *which specimens were collected under this permit* directly, which is
  the question an authority, a herbarium or a reviewer asks.
- Puts the permit reference where the specimens are, so a deposit or a
  submission can cite it without reconstructing it from memory.
- Generalises with the rest of the model: permits are as real in ethnozoology
  and ethnomycology as in botany ([0006](0006-multi-subfield-architecture.md)),
  and the issuing authority is already a per-country variable.
- Adjacent to, and deliberately **not**, consent. A collecting permit is
  permission from a state to take material. Prior informed consent from a
  community, and the benefit-sharing terms the Nagoya Protocol governs, are
  different instruments with different holders. Conflating them would be worse
  than modelling neither.
- Requires a migration, catalog UI for managing permits, and a column in the
  specimen export.

## Scope — what this deliberately is not

**Not a compliance system.** No validity checking, no reminders, no assertion
that a collection was lawful. The platform records a reference the way it
records a determiner.

**Not document storage,** at least here. Holding permit scans raises retention,
access-control and jurisdiction questions worth their own decision, and the
reference alone answers the question that is asked most often.

**Not consent or ethics artefacts.** Named above as a separate instrument; that
gap stays open rather than being quietly half-filled.

## Alternatives considered

- **A free-text `permit` string on the specimen.** Rejected: repeats the
  authority and number on every row, cannot answer "which specimens under this
  permit", and loses the validity period entirely — the same conflation 0008
  corrected between a specimen and a taxon.
- **Leave the permit blank when none is required.** Rejected: it makes the
  coverage figure dishonest, since a lawful market survey would read the same as
  an undocumented collection.
- **Share permits across projects.** Rejected for now: a real permit may well
  span studies, but every other record here is project-scoped and per-project
  access control is what the platform enforces. Cross-project ownership is a
  later decision if it is ever asked for.
