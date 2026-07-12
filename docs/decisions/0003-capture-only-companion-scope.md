# 0003 — Scope the companion apps to field capture only

- **Status:** Proposed
- **Deciders:** Project owner

## Context

It is tempting to port the whole web experience to mobile. But the platform has
distinct modes: instrument *design*, field *capture*, folk-name → taxon *linking*,
*analysis*, and *export*. Only capture is inherently a field, offline, on-a-phone
activity; the rest are screen-and-keyboard, connected, deliberate tasks.

## Decision

The companion apps do **capture only**: record interviews, audio, GPS, photos,
fully offline, syncing when connectivity allows. Form design, species linking,
analysis, and export stay on the **web**.

## Consequences

- **Sync becomes push-dominant and far simpler.** The device authors capture data
  and owns it until synced; forms are pulled read-only. This asymmetry is what
  lets the sync design avoid three-way merges and CRDTs
  ([0004](0004-offline-sync-model.md)).
- The mobile app stays small and focused — less surface to build, test, and keep
  in sync with the web.
- Each platform plays to its strength: phone for capture in the field, web for the
  deliberate reconciliation and analysis work that benefits from a big screen.
- Cost: linking can't happen in the field. Acceptable — the device captures the
  raw folk name, and reconciliation is a considered task better done later on the
  web with the full catalog visible.

## Alternatives considered

- **Full-parity mobile app.** Rejected: massively larger build, forces bidirectional
  sync of editable records everywhere, and duplicates flows that are better on the
  web — for little field value.
- **Capture + on-device linking.** Rejected for v1: requires shipping and syncing
  the catalog to the device and doing careful reconciliation on a small screen;
  revisit only if field linking proves genuinely needed.
