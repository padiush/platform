# Architecture Decision Records

Short records of the significant, hard-to-reverse choices behind Padiush's
direction — the *why* that git history and code can't preserve. Read these before
re-litigating a decision; if you do change one, add a new ADR that supersedes it
rather than editing the old one.

These capture reasoning from the product/architecture discussions that shaped the
current roadmap. All are **Accepted**; 0006 alone is accepted in principle, with
its implementation deferred. The status line on each is accurate, and the
decisions register in [../README.md](../README.md) is where anything still open
is surfaced — including work that follows from an accepted decision rather than
qualifying it.

## Index

| # | Decision | Status |
|---|---|---|
| [0001](0001-complete-ethnobotany-before-generalizing.md) | Complete the ethnobotany vertical before generalizing to other subfields | Accepted |
| [0002](0002-mobile-companion-stack.md) | Build companion apps on Expo / React Native | Accepted |
| [0003](0003-capture-only-companion-scope.md) | Scope the companion apps to field capture only | Accepted |
| [0004](0004-offline-sync-model.md) | Offline sync: client-owned records, UUIDs, last-writer-wins | Accepted |
| [0005](0005-interview-transcription-whisper.md) | Transcribe interview audio with self-hosted Whisper | Accepted |
| [0006](0006-multi-subfield-architecture.md) | Generalize to ethnobiology via subfield configuration | Accepted (impl. deferred) |
| [0007](0007-use-category-as-item-role.md) | Model use-category as a form-item role | Accepted |
| [0008](0008-specimens-and-determinations.md) | Model the specimen as its own entity, with a determination history | Accepted |

## Template

```markdown
# NNNN — <short imperative title>

- **Status:** Proposed | Accepted | Superseded by NNNN | Deprecated
- **Date:** YYYY-MM-DD
- **Deciders:** <who>

## Context
What forces are at play — technical, product, domain — that make a decision
necessary. State constraints and the problem, not the answer.

## Decision
The choice, in one or two sentences, plus the essential specifics.

## Consequences
What becomes easier and what becomes harder. Include the costs, not just the wins.

## Alternatives considered
The options rejected and the one-line reason each lost.
```

> ADR dates are intentionally omitted from the seed records below (they capture
> decisions made across a discussion, not on a single stampable day). Add a date
> when a decision moves to **Accepted** and is acted on.
