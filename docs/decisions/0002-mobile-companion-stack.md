# 0002 — Build companion apps on Expo / React Native

- **Status:** Accepted
- **Deciders:** Project owner

## Context

The companion apps close the field-capture gap. The team is a React 19 shop on
the web. The owner's hesitation is that native apps *feel* snappier, and the
state of React Native's performance is unclear to them.

Relevant facts: RN's historical jank came from the old async "bridge," which the
**New Architecture** (JSI + Fabric + TurboModules, default since late 2024)
removed; Hermes, Reanimated (UI-thread animations), and FlashList address the rest.
The capture app's workload is forms + local SQLite + audio + GPS + HTTP sync — the
performance-critical paths (recording, GPS, file I/O) run in native/OS code
regardless of framework; the JS layer only orchestrates light UI.

## Decision

Build the companion apps with **Expo / React Native** — committed.

## Consequences

- **One codebase, one language**, reusing the team's React/TypeScript skills;
  types, validation, and the API client can be shared with the web. The hard part
  (offline sync) is written **once**, not twice.
- Expo gives first-class native modules for exactly what's needed: `expo-sqlite`
  (or op-sqlite), `expo-av` (audio), `expo-location` (GPS), secure storage,
  background tasks. Prebuild/config-plugins mean arbitrary native code is
  reachable — the old "you'll have to eject" fear no longer applies.
- Native cold-start and memory are marginally worse than a true-native app, but
  not on any path this app's users will notice.
- **De-risking spike (advisory, not a gate):** an early capture-screen spike
  (a form + record button + SQLite write) on a physical device is still worth
  doing first — to shake out the native module wiring and confirm the feel — but
  the stack decision no longer waits on it.

## Alternatives considered

- **True native (Swift + Kotlin).** Rejected for this workload: doubles the
  hardest work (offline sync) and forks the team permanently, for a perf edge that
  doesn't matter to a forms-and-sync app.
- **Flutter.** The smoothest cross-platform option, but Dart discards all React
  reuse. The marginal smoothness doesn't justify the lost leverage for a forms app.
