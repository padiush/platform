# 0006 — Generalize to ethnobiology via subfield configuration

- **Status:** Accepted in principle; implementation deferred (after
  [0001](0001-complete-ethnobotany-before-generalizing.md))
- **Deciders:** Project owner

## Context

The problems Padiush solves — structured capture, folk-name → taxon
reconciliation, quantitative indices, export — are shared across *all* of
ethnobiology, not just botany. There is concrete demand and expert support from
ethnomycology and ethno-ornithology, with other zoology subfields likely.

The key realization: the core pipeline is identical across subfields. What
genuinely varies is **narrow** — chiefly the *taxonomic authority* a project
validates names against. The analysis indices are field-agnostic (the same UV,
ICF, FL math), and so is capture. So "one product, parameterized" — not "N
products."

## Decision

Model **subfield as configuration**, not as forked code. A subfield selects:

1. a **taxonomic-authority provider** (the real variant),
2. a small set of catalog fields (e.g. conservation status for animals),
3. a **vocabulary / terminology** layer (subfields name the same slots
   differently), and
4. default index and report presets.

Everything else stays identical.

Implementation is **deferred** until the ethnobotany vertical is complete
([0001](0001-complete-ethnobotany-before-generalizing.md)), then done **one
subfield at a time**, deriving the `TaxonomicAuthority` abstraction from the
*second* real implementation rather than guessing it from botany alone.

## Consequences

- Repositions Padiush as *the ethnobiology research platform* (ethnobotany as
  flagship) — a genuine market gap and a stronger story to funders and journals.
- The indices and companion apps built for ethnobotany are inherited free by every
  subfield.
- **Cheap seams to leave open now** (do these; they cost little and save a
  refactor): add `Project.subfield` as a first-class attribute, and keep the WFO
  call behind a thin boundary instead of inline. Design for the future's
  *existence*, not its details.
- **Do not** build the provider abstraction speculatively from one case — abstractions
  guessed from a single example are usually wrong.

## Domain constraints surfaced by the first target subfields

To bake into the abstraction when it's built (each stresses a different axis):

- **Ornithology → multiple competing authorities.** IOC, Clements/eBird, and
  BirdLife/HBW disagree on splits and names; researchers care which one they
  resolve against. The provider abstraction must be "a subfield offers **one or
  more** authorities; the project picks," not one-authority-per-subfield.
- **Mycology → unstable taxonomy, fuzzy species.** Constant revisions,
  morphospecies, species complexes. Linking must tolerate genus-level /
  complex-level / provisional links with a confidence marker — which also benefits
  botany.
- **Zoology broadly → conservation status** (IUCN) becomes a first-class catalog
  field, with an ethics dimension (documenting use of threatened taxa) that fits
  the platform's encrypt-and-consent posture.
- **GBIF** spans all kingdoms and can be the near-universal default provider, with
  specialist authorities (WFO, Index Fungorum, IOC) layered where precision matters.

## Alternatives considered

- **A separate product per subfield.** Rejected: they share ~95% of the solution;
  forking multiplies maintenance for no benefit.
- **Build the plugin architecture now.** Rejected: premature; derive it from two
  real subfields (see [0001](0001-complete-ethnobotany-before-generalizing.md)).
