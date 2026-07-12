# 0007 — Model use-category as a form-item role

- **Status:** Accepted
- **Deciders:** Project owner

## Context

The quantitative indices ICF, FL, and CI require knowing, for each linked answer,
which **use-category** it belongs to (food, medicine, construction, …). Today the
only semantic marker on an `InterviewItem` is `link_to_species` (the taxon field);
there is no notion of a use-category. This was the blocking open decision for the
indices milestone ([../analysis/ethnobotany-indices.md](../analysis/ethnobotany-indices.md)).

Options weighed: (a) a semantic role on items, set at instrument-design time;
(b) mapping items → roles at analysis time, per report; (c) treating each option
of a designated `multi` "uses" item as a category.

## Decision

Add a **use-category role to `InterviewItem`**, mirroring `link_to_species`. The
answer to a use-category item — within the same repeatable set as a linked species
— supplies the use-category *u* for the resulting use reports. Categorization
happens once, at instrument design, not per-analysis.

Likely shape: either a boolean `is_use_category` alongside the existing
`link_to_species`, or a small role enum on items (`taxon` | `use_category` |
`plain`) that supersedes both booleans. The enum is cleaner if a third role ever
appears; decide at implementation.

## Consequences

- Unblocks ICF, FL, and CI — the whole five-index milestone
  ([0001](0001-complete-ethnobotany-before-generalizing.md)) — since scope is now
  "all five together." The use-category role must land **before or with** the
  index computation.
- Use-categories are **structured and consistent** across a study because they're
  fixed in the instrument, not reconstructed per report. This makes the indices
  reproducible and the export cleaner.
- Requires a form-designer change (mark an item as use-category), a small schema
  change, and validation (a species-linking form should have exactly one taxon
  item; use-category items pair with it inside a repeatable section).
- Subfield-agnostic: the same role serves ethnomycology, ethno-ornithology, etc.
  ([0006](0006-multi-subfield-architecture.md)).

## Alternatives considered

- **Map at analysis time.** Rejected: fragile and manual per study; the same
  instrument could be categorized inconsistently across reports.
- **`multi` "uses" item = categories.** Rejected as the primary model: couples the
  category vocabulary to one field's options and doesn't compose with repeatable
  per-use detail; may still be offered as a convenience input, resolving to the
  same role underneath.
