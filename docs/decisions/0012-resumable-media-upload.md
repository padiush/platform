# 0012 — Resume large media uploads from the parts the server already has

- **Status:** Accepted
- **Date:** 2026-10-05
- **Deciders:** Project owner
- **Builds on:** [0004](0004-offline-sync-model.md) — media stays out of band
  from the JSON sync; what changes is how one file gets there

## Context

A captured file reaches object storage in one presigned `PUT`, sent straight
from the device so that large files never stream through the app server. That
part is right, and it stays. What is wrong is that the `PUT` is one request for
the whole file.

An interview is recorded for as long as the informant keeps talking, and the
file is tens of megabytes for an hour. It is sent over whatever connection the
researcher finds on the way back: a weak mobile signal, a guesthouse network
shared by everyone in it. Any drop in that connection fails the request, and the
next attempt starts again from the first byte. On a connection that drops more
often than the file takes to send, a long recording never arrives at all. The
researcher sees it waiting in *Por enviar* every evening, and the field data
that matters most, the longest interviews, is the data most likely never to
leave the phone.

Sending one request also means holding the whole file. The device keeps media
only inside its encrypted store, in 4 MiB chunks, and reassembles every chunk
into one buffer in memory before the `PUT`. That is the largest allocation the
app makes, and it grows with the length of the interview.

The contracts already called this upload *resumable*. It never was. This record
decides what resumable means here.

Two constraints narrow the answer.

- **Media never leaves the encrypted store as a plaintext file.** The store
  exists because informant recordings are sensitive, and a decrypted copy on
  disk for the length of an upload is that copy on a lost or seized phone.
- **The device should not have to remember more than it does now.** Every
  piece of local state is a schema version, a migration and a way to disagree
  with the server after a reinstall or an account switch.

Object storage already has a mechanism for this. An S3 multipart upload accepts
a file as numbered parts, each its own request. It keeps the parts it received
until it is told to assemble them or to abort, and it can list them.

## Decision

**Large files go up as an S3 multipart upload that the server starts, tracks
and finishes. The device sends only the parts the server says are missing.**
Each failed attempt then costs at most one part, not the whole file.

The handshake keeps its `intent` → upload → `complete` shape, with one step
added. It is the same for an interview's media and a field record's.

1. **`intent` with `resumable: true`.** For a file larger than one part
   (8 MiB), the server starts a multipart upload, stores its upload id and part
   size on the media row, and answers
   `upload: { mode: "multipart", part_size, part_count }` instead of an
   `upload_url`. A smaller file gets `mode: "single"` and the presigned `PUT`
   it gets today. A repeated `intent` for the same `client_id` and the same file
   resumes the same upload. An `intent` that announces a different size aborts
   the old upload and starts again.
2. **`media/parts`, a new endpoint.** The server lists the upload's parts in
   storage and returns presigned URLs for the parts that are absent or the
   wrong size, and only those. The device `PUT`s them one at a time, each a byte
   range read from its encrypted chunks, and calls `parts` again until the list
   comes back empty. The URLs expire after fifteen minutes. A file that takes
   longer than that to send simply asks again.
3. **`complete`.** The server lists the parts once more and refuses an
   incomplete set (`api.media.upload_incomplete`). It assembles the object
   (CompleteMultipartUpload), then runs the checks every upload already gets:
   size and content type, inspected in storage rather than taken on the
   device's word.

The server reads the parts' ETags from its own listing, so the device never
handles one. If the upload is gone, aborted by cleanup or by the store, `parts`
and `complete` answer `api.media.upload_expired` and the server forgets the
upload id. The device's answer is to call `intent` again. That is the one case
where sent parts are lost.

**The server is the record of progress.** The device stores nothing new. Its
media row stays pending until `complete` succeeds, as it does today. What has
been sent is a question it asks the server each time, so a reinstall, a crash
or a second attempt days later picks up exactly where storage is.

**Stale uploads are aborted by a scheduled command.** It lists the bucket's
incomplete multipart uploads and aborts any that started more than seven days
ago, or that belong to no pending media row. It also clears the row's upload id,
so the next `intent` starts cleanly. A bucket lifecycle rule
(*AbortIncompleteMultipartUpload*) is a sensible production backstop with a
longer window. It is not the mechanism: not every S3-compatible store
implements it, and it cannot tell the database the upload is gone.

**Nothing changes for anyone who does not ask.** A client that never sends
`resumable` gets the single `PUT` it gets today. A new client talking to a
server without this sees no `upload` in the response and falls back to the
single `PUT` itself. Small files, which is nearly every photograph, keep the
single request even when resuming is offered, because one part is the whole
file.

## Consequences

- **A dropped connection costs one part, not the file.** Every attempt moves
  the upload forward, so a long recording does arrive over a link that fails
  every few minutes.
- **The device holds one part in memory instead of the whole file.** Memory is
  bounded at 8 MiB per upload whatever the length of the interview, which also
  answers the allocation problem above.
- **More requests per file.** A 100 MB recording is an `intent`, a `parts`, 13
  part uploads and a `complete`, rather than three requests. Each is small, and
  on the connections this is for, many small requests are the point.
- **The server now holds storage state across requests.** An abandoned upload
  keeps its parts, and pays for them, until something aborts it. That is why
  the scheduled command exists. Production must run the scheduler, as it
  already must for the sitemap.
- **A new endpoint for each owner**, `instances/{instance}/media/parts` and
  `records/{record}/media/parts`, in the contract, the OpenAPI document and the
  Postman collection. The storage calls go behind an interface, as the
  presigned URL already does, so tests do not need a live bucket.
- **The content-type check at `complete` becomes mostly a formality for a
  multipart file.** The server sets the type when it starts the upload, not the
  device's request. The size check is what still catches a wrong file.
- **The upload still needs the app open.** This makes an interrupted upload
  resume. It does not make an upload continue while the phone is in a pocket,
  for the reason given under the first alternative.

## Alternatives considered

- **Hand the file to the operating system's background upload.** iOS and
  Android can keep sending a file while the app is suspended, which would be
  the better experience. Rejected: they upload from a file on disk, so the
  recording would sit decrypted outside the encrypted store for as long as the
  upload took. That is exactly the copy the store exists to prevent.
- **Track the parts on the device.** The device keeps the upload id and each
  part's ETag. Rejected: it adds local state, and a schema version, for
  information the server can list from storage whenever it is asked. It also
  makes the device read ETag headers from storage responses. And it leaves two
  records of the same progress that can disagree after a reinstall.
- **List every part's URL in the `intent` response.** Rejected: presigned URLs
  expire, and the file this is for outlives fifteen minutes on a slow link.
  Asking for the missing parts is what lets an upload span hours or days.
- **Upload in chunks through the app server**, with a resumable-upload server
  in front of storage. Rejected: every byte of every recording would then pass
  through PHP, which the direct-to-storage design exists to avoid. It would
  also add a component to deploy for something storage already does.
- **Split long recordings into shorter files on the device.** Rejected: it
  changes what a recording is. Playback, transcription and the web would all
  have to stitch an interview back together, to work around a transport
  problem.
- **A longer expiry on the single `PUT`.** Rejected: it lets a slow upload
  finish, but a dropped connection still restarts it from the first byte.
