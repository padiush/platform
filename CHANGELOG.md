# Changelog

Notable changes to the Padiush platform, one section per release. The format
follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and versions
follow [Semantic Versioning](https://semver.org/).

This is the record for people who run or build on Padiush. Researchers read the
same releases as "What's new" inside the app, written for them and in their
language (`public/locales/whatsnew/`). How a release is cut is in
[docs/releasing.md](docs/releasing.md).

## [Unreleased]

### Added

- A collapsible sidebar with a project switcher. Every project page lives under
  `/projects/{project}/…`, and each project opens on an overview of its
  interviews, records, species and pending work. Old URLs redirect.
- Field records and collecting permits in a section of their own. A record can
  be an observation as well as a collected specimen (`basis_of_record`), with a
  determination history and project-issued accession numbers
  ([ADR 0008](docs/decisions/0008-specimens-and-determinations.md),
  [0009](docs/decisions/0009-collecting-permits.md),
  [0010](docs/decisions/0010-field-records-and-basis.md)).
- The companion API accepts field records (`records:sync`) and their
  photographs and audio, and the web shows which interview answer a record came
  out of ([ADR 0011](docs/decisions/0011-companion-field-records.md)).
- Mi cuenta: the browsers an account is signed in on and its companion
  devices, each of which can be signed out or revoked.
- Resumable media upload: a device can send a large file as an S3 multipart
  upload through the new `media/parts` endpoints, and a scheduled
  `media:abort-stale-uploads` job clears abandoned ones
  ([ADR 0012](docs/decisions/0012-resumable-media-upload.md)).
- "What's new": a dialog after each release with its notes, a page with every
  release, and this changelog.
- Guided tours (driver.js): a welcome tour, then one per section on its first
  visit (Proyectos included), leaving out what the user's role does not open.
  A user who starts with no project is shown the project switcher and sections
  the first time they have one. Finished or skipped
  tours are remembered per user (`users.completed_tours`). Each page has a ?
  button to replay its tour, and Mi cuenta can offer them all again.
- A system panel in three tabs, showing figures and names but never what is
  inside a project. **Summary**: users, active people, projects, storage by
  kind, the version and pending migrations, whether the scheduler, unfinished
  uploads and the orphaned-file check are in order, who stores the most, and
  a record of what administrators did (`admin_actions`). **Users**: search,
  filters, the projects each person owns or belongs to, their storage and
  last activity (from web sessions and device tokens), and invitations that
  can be resent or withdrawn. **Storage**: totals, use by owner, and the
  largest projects by size. Deleting an account first shows what goes with it
  and what stays, can transfer its projects to someone else, and is confirmed
  by typing the account's email. A scheduler heartbeat and the last
  `media:prune-orphans` run are kept in `system_runs`.
- An example project: anyone can open a private, writable copy of the invented
  demonstration study from the dashboard or Proyectos, built in the language
  they are using (`lang/*/example_study.php`), and remove it in one step. It is marked as an example wherever it appears, left out of the system
  project count (`projects.is_example`), and syncs to the companion like any
  other project. `DemoProjectSeeder` now builds the same study.
- A demo image that can be run to try Padiush.

### Changed

- Sessions are kept in the database (`SESSION_DRIVER=database`), which is what
  lets Mi cuenta list them. Switching an existing installation signs everyone
  out once.
- The development stack uses SeaweedFS for object storage instead of the
  archived MinIO.
- The system panel no longer deletes several accounts at once: each deletion
  goes through its own preview of what goes with the account.

### Fixed

- Presigned upload headers are sent as plain strings, which the companion's
  HTTP client requires. Uploads from the field app failed without this.
- Open dependency advisories are patched.
- Deleting a project takes its catalog species, their photos and its forms'
  questions with it. Those tables have no foreign key to cascade from, so they
  were left behind.
- Deleting a field record, an interview, a form, a project or an account
  deletes its recordings and photographs from storage, not only their rows.
  Until now only removing a single photo from a record did, so deleted audio
  and photographs stayed in the bucket. A new `media:prune-orphans` command
  lists the objects left behind before this, and removes them with `--delete`.

### Upgrading

- Run the migrations.
- Run the scheduler (`php artisan schedule:run` every minute).
- Set `SESSION_DRIVER=database`.
- Run `php artisan media:prune-orphans` to see the stored files that earlier
  deletions left behind, then `php artisan media:prune-orphans --delete` to
  remove them. It reads `--disk=s3` by default; run it for any other disk media
  was stored on.

## [1.0.0] — 2026-08-15

First public release: interview form design, offline capture with the companion
app, species linking against taxonomic backbones, the five ethnobotanical
indices, and export.

[Unreleased]: https://github.com/padiush/platform/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/padiush/platform/releases/tag/v1.0.0
