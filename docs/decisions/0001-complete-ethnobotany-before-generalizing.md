# 0001 — Complete the ethnobotany vertical before generalizing to other subfields

- **Status:** Accepted
- **Deciders:** Project owner

## Context

Padiush is architecturally general enough to serve ethnobiology broadly (see
[0006](0006-multi-subfield-architecture.md)), and there is real demand from
adjacent subfields (ethnomycology, ethno-ornithology). At the same time, the
ethnobotany experience is not yet *complete*: the two biggest gaps are the
absence of built-in quantitative indices and the absence of field capture
(offline, GPS, audio). There is a pull to start adding subfields now.

## Decision

Finish the ethnobotany vertical **end to end** — the analysis indices
([../analysis/ethnobotany-indices.md](../analysis/ethnobotany-indices.md)) and the
companion capture apps ([../contracts/companion-api.md](../contracts/companion-api.md))
— **before** implementing any second subfield.

## Consequences

- The first subfield becomes a **complete reference implementation** with a clear
  definition of "done," against which the second can be measured.
- Every sharp edge of the full pipeline is hit once, on the subfield the team
  knows best and can validate — instead of debugging the pipeline and the
  generalization simultaneously.
- Both milestones (indices, capture) are themselves subfield-agnostic, so the work
  is not wasted on generalization — it is *inherited* by every later subfield.
- Cost: adjacent-subfield collaborators wait. Mitigated by keeping the
  generalization *seams* open cheaply now (see Consequences of
  [0006](0006-multi-subfield-architecture.md)) without building the abstraction.

## Alternatives considered

- **Generalize first, then deepen.** Rejected: multiplies an unfinished vertical,
  and you can't extract the right abstraction from a half-built case.
- **Both in parallel.** Rejected: splits focus on a small team and couples two
  hard efforts whose failures would be hard to isolate.
