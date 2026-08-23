# 0011 — Record in the field, identify on the web

- **Status:** Accepted
- **Date:** 2026-08-23
- **Deciders:** Project owner
- **Extends:** [0003](0003-capture-only-companion-scope.md) — its boundary stands;
  what changes is that capture is no longer only an interview

## Context

[0003](0003-capture-only-companion-scope.md) scoped the companion to capture,
and named what capture meant at the time: interviews, audio, GPS, photographs.
Field records did not exist then. [0008](0008-specimens-and-determinations.md),
[0009](0009-collecting-permits.md) and
[0010](0010-field-records-and-basis.md) built them on the web, and said so
plainly — server-side this version, the companion keeps its interview scope.
That was a sequencing choice made when no field study was near, not a judgment
that the web is where a record belongs.

It is not. Almost everything a field record holds is known only while standing
in front of the thing being recorded, and known nowhere else afterwards. The
coordinates come from the device on the researcher's body. The photographs come
from the camera in their hand — and for an observation the photograph *is* the
record, because no material survives to re-examine. The collection number is
being written on the tag at that moment. The vernacular name is being said out
loud by the person standing beside them.

Entering that on the web later means it was first written on paper and then
transcribed. Transcription from a field notebook is precisely the failure the
companion was built to remove for interviews: it happens days later, it happens
tired, it silently drops the things nobody thought to write down, and the
coordinates it recovers are the ones someone copied by hand rather than the ones
the device recorded.

**And one moment on the web cannot be reached at all.** During an interview an
informant names a plant and points at it. The answer and the record are the same
event, seen from two sides. The model already anticipates this — a field record
carries `instance_answer_id`, *the answer this record came out of* — and nothing
on the web uses it. That is not an omission. It is a seam waiting for the only
device that can be present when the two things happen together.

The obstacle was never conceptual. It was that a record seemed to need things a
disconnected device cannot safely produce: a unique accession number, a taxon
determination, a permit issued by an authority. Each of those turns out to
belong to a later stage that is not a field stage at all.

## Decision

**The companion captures field records.** The three-stage lifecycle
[0008](0008-specimens-and-determinations.md) already describes — recorded first,
identified later, deposited later still — is also the boundary, because only the
first stage happens in the field.

| Stage | Authored by | What it sets |
|---|---|---|
| Recorded | **device** | `basis_of_record`, `vernacular_name`, `collection_number`, `collector`, `collected_on`, `locality`, coordinates, `notes`, permit or exemption, photographs and audio |
| Identified | web | the determination, and its revisions |
| Deposited | web | `accession_number`, `repository` |

- **The two numbers are what make this safe.** 0008 gave a record a
  `collection_number` *and* an `accession_number`, which reads as redundant
  until a device is offline. The collection number is the collector's own — they
  write it on the tag, it need coordinate with nobody, and the device can author
  it freely. The accession number is *issued*: `AccessionNumbers` takes it from
  a per-project sequence inside a locked transaction, so two researchers cannot
  be handed the same one. A disconnected device cannot take a number from that
  sequence without risking a collision, and it never needs to, because deposit
  is not a field act.
- **A record may be created from an interview answer**, setting
  `instance_answer_id` — the informant names it, the researcher records it, in
  one session without leaving the interview.
- **Permits travel in the project bundle, read-only.** A permit is held before
  fieldwork begins; nobody issues one in a forest. The device chooses among the
  permits the project already has, exactly as it renders forms it did not
  design.
- **0009's exclusive rule is enforced on the device**, not only on the server. A
  record carries a permit or a stated exemption and never both, and a rule that
  only the server knows is a form filled out in the field and refused on return.
- **Sync is push-only**, as `instances:sync` is
  ([0004](0004-offline-sync-model.md)). The device owns a record until it lands;
  once it lands, the web owns it and the device's copy is read-only.

## Consequences

- **The companion gains one more thing to capture, not a second mode.** It still
  does not design, link, analyse or export. 0003's boundary is unmoved; what
  moved is the recognition that a field record was always on the capture side of
  it.
- **Push-only survives, and is on firmer ground here than for interviews.** The
  device authors only the recorded stage; the web edits only the later ones. The
  two never write the same fields, so a field record cannot produce the conflict
  that last-writer-wins exists to settle. The asymmetry is structural rather than
  a policy applied after the fact.
- **A synced record is no longer editable on the device.** A collection number
  mistyped in the field is corrected on the web. This is the same bargain
  interviews already make, and the alternative is bidirectional editing of
  records the web is simultaneously determining and depositing.
- **A cached permit can be stale.** One revoked on the web is still selectable by
  a device that has not pulled since. A permit is a reference record and nothing
  validates it ([0009](0009-collecting-permits.md)) — this is the staleness a
  cached form already has, not a new class of problem.
- Costs: a local schema version, a second sync resource, a capture screen, and
  widening media ownership on the device to mirror the server's — the companion's
  local `media` still requires an interview, as the server's did before
  [0010](0010-field-records-and-basis.md).
- Generalises with the rest of the model: a bird heard and not caught is captured
  by the same screen ([0006](0006-multi-subfield-architecture.md)).

## Scope — what this deliberately is not

**Not on-device identification.** 0003 judged that linking needs the catalog and
a considered look at a real screen, and accepted that it cannot happen in the
field. That judgment is unchanged. The device captures the vernacular name, as
it already does inside an interview, and reconciliation stays a web task.

**Not on-device deposit.** Accession numbers are issued against a project
sequence under a lock. Nothing about that is improved by attempting it offline.

**Not editing what has synced.** Retaining device-side edits after a record
lands would reintroduce exactly the bidirectional sync 0003 and 0004 were shaped
to avoid.

**Not a consent mechanism.** A record made during a guided walk carries
attributable knowledge, and encrypting the vernacular name treats it
consistently without answering who agreed to what — still the open gap named in
[0009](0009-collecting-permits.md).

**Not occurrence publication.** Unchanged from
[0009](0009-collecting-permits.md) and [0010](0010-field-records-and-basis.md).

## Alternatives considered

- **Leave it on the web, as built.** Rejected: it makes a paper notebook the
  real instrument and the platform a place to type it up afterwards, losing the
  coordinates, the photographs and the detail that only exist at the moment of
  recording.
- **Full parity — record, determine and deposit on the device.** Rejected for
  the reasons 0003 gave and this decision does not disturb: it requires shipping
  and syncing the taxon catalog offline, puts careful reconciliation on a small
  screen, and reintroduces bidirectional editing of records the web is working on
  at the same time.
- **A second, separate app for collecting.** Rejected: same authentication, same
  encrypted store, same sync engine, same media pipeline — all of it duplicated,
  and a field session split across two icons at the moment when the interview and
  the collection are one event.
- **Model a field record as a special interview form.** Tempting, because the
  form designer and its capture screen already exist. Rejected: a record has no
  informant and no consent posture of its own, and its fields are fixed by Darwin
  Core rather than designed per study. Expressing it as a form would push
  occurrence semantics into the form designer, where every future study would
  have to be trusted to reproduce them correctly.
