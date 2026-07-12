# 0005 — Transcribe interview audio with self-hosted Whisper

- **Status:** Accepted
- **Deciders:** Project owner

## Context

The companion apps let researchers record interview audio to review later. Turning
that audio into searchable, structured text needs speech-to-text. The obvious
options are a hosted API (e.g. OpenAI Whisper API) or a self-hosted model
(`whisper.cpp`, `faster-whisper`).

The decisive factor is **data sensitivity**. Interview audio is exactly the
traditional-knowledge, consented, informant-identifying data the platform already
bothers to encrypt at rest (`InstanceAnswer.answer`). Shipping it to a third-party
API would quietly contradict that posture and may breach consent terms, IRB
conditions, or Nagoya-Protocol obligations.

## Decision

Transcribe with a **self-hosted Whisper** model on the platform's own
infrastructure. Audio never leaves systems the project controls.

## Consequences

- Consistent with the platform's encrypt-at-rest ethos and defensible to ethics
  boards and informant communities — a values decision, not only a cost one.
- No per-minute API fees; cost is compute the project already runs.
- Requires operational work: a **real queue driver** (the app is on
  `QUEUE_CONNECTION=sync` today — a hard prerequisite), a transcription worker,
  and GPU or accepting slower CPU inference.
- Transcription is **asynchronous**: audio uploads out-of-band to object storage,
  a queued job transcribes, and the transcript attaches to the instance on a later
  pull ([../contracts/companion-api.md](../contracts/companion-api.md)). Nothing in
  capture or sync blocks on it.
- Language: models handle Spanish/Portuguese/English; field recordings may include
  Indigenous languages Whisper handles poorly — set expectations (transcript as a
  *draft aid*, not ground truth; the audio remains the record).

## Alternatives considered

- **Hosted Whisper / other STT API.** Rejected on privacy/consent grounds despite
  lower ops effort.
- **No transcription (audio only).** Rejected: leaves the audio unsearchable and
  unlinked to structured data, wasting much of its value.
