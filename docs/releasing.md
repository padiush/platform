# Releasing

Padiush ships in releases, not commit by commit. A release is one version
number that everything carries: the changelog, the notes users see, the
citation, and the tag that Zenodo archives.

## Between releases

Every pull request that changes something a user or an operator would notice
adds a line under **Unreleased** in [CHANGELOG.md](../CHANGELOG.md). It goes in
the same change as the code, like the rest of the docs.

Release notes for users are drafted as the release takes shape. They live in
`public/locales/whatsnew/{es,en,pt}.json`, newest release first:

```json
{ "version": "1.1.0", "date": null, "items": ["…", "…"] }
```

- **Write for researchers, not developers.** Say what they can do now, in their
  words. Leave out what only an operator would notice; that belongs in the
  changelog.
- **Spanish first**, then English and Portuguese, with the same points in the
  same order. A test fails if a language has a different number of points.
- **Keep `date` null until the release ships.** Notes for a version above the
  one in `package.json` are hidden, so drafting them early is safe.

## Cutting a release

1. Pick the number. Use a minor version for new features, a patch for fixes
   only, and a major version for a change that breaks the companion API or
   needs operators to act beyond running migrations.
2. Set it in `package.json` (the app reads its version from there) and in
   `CITATION.cff` (`version`).
3. Finish the user notes: set the release's `date`. A test fails if a release
   at or below `package.json` has no date, or one above it has a date.
4. In `CHANGELOG.md`, rename **Unreleased** to `[x.y.z] — YYYY-MM-DD`, start a
   new empty **Unreleased**, and update the comparison links at the bottom.
5. Merge, then tag `vx.y.z` on `main` and publish a GitHub release with the
   changelog section. Zenodo archives it and mints a version DOI. Add that DOI
   to `CITATION.cff` and to the citation section of the README in a follow-up.

## What users see

- **After an update.** The first time someone signs in to a new release, a
  "What's new?" dialog lists the notes of every release since the last one they
  saw. Closing it in any way records the release as seen. A release with no
  notes is recorded as seen silently.
- **New accounts** start on the release they were created under, so they are
  not shown notes for things they never knew were otherwise.
- **The full history** is on the What's new page, linked beside the version
  number in the footer of every signed-in page.
