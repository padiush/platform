# The public site and its legal documents

Padiush ships without a public-facing site. A fresh installation serves the
application and nothing else: visiting `/` sends you to the sign-in page, and
`/acerca`, `/contacto`, `/privacidad` and `/terminos` answer 404.

That is deliberate. Those pages describe **one specific deployment** — who
operates it, who the data controller is, where to write with a complaint. If
they shipped in the repository, every installation would republish another
operator's legal claims as its own, which is a false statement about a service
that operator does not run.

## Turning the site on

```dotenv
PUBLIC_SITE_ENABLED=true
```

That alone enables the landing, about and contact pages. The privacy and terms
routes stay 404 until you publish documents for them, because a legal page that
renders its chrome around an empty body is worse than an honest 404.

## Publishing your legal documents

Write one JSON file per language into `public/locales/legal/`:

```
public/locales/legal/es.json
public/locales/legal/en.json
public/locales/legal/pt.json
```

The directory is gitignored, so your documents are yours and stay out of any
fork you push.

Each file holds both documents, structured rather than as raw HTML — the
document owns its words, the application owns the markup, which keeps the
heading hierarchy correct however the text is written:

```json
{
  "updated_on": "Last updated: {{date}}",
  "privacy": {
    "title": "Privacy policy",
    "updated": "2026-08-06",
    "summary": "One paragraph, shown before the sections as a lead.",
    "sections": [
      {
        "heading": "Who we are",
        "blocks": [
          { "type": "p", "text": "…" },
          {
            "type": "ul",
            "items": ["a plain point", { "term": "Bolded", "text": "then this" }]
          },
          { "type": "links", "items": [{ "label": "…", "href": "…" }] }
        ]
      }
    ]
  },
  "terms": { "title": "Terms", "updated": "…", "summary": "…", "sections": [] }
}
```

Block types are `p`, `ul` and `links`; note that `links` takes `items`, like
`ul` does. A `ul` item is either a plain string or `{ "term", "text" }`, which
renders the term in bold. Any other `type` falls through to a paragraph, so a
typo shows up as prose rather than vanishing.

Only the languages you write are served; the site falls back to Spanish, so at
minimum provide `es.json`.

### What the documents have to cover

This is not legal advice, and the list is not exhaustive. It is what this
software does, so that your documents can describe it accurately:

- **Interview content is the researcher's, not yours.** Answers, audio,
  photographs and locations are collected by the researcher running a study.
  They are the controller for that material; you operate the processor.
- **Where the data lives** — your database, and your object storage for media.
  Name the region if it is outside your users' jurisdiction.
- **The mobile companion stores captures encrypted on the device** with
  SQLCipher until they sync, and reports a small fixed set of integrity codes
  (no message, no payload) when a local store is lost.
- **Analytics only run if you configure them.** `UMAMI_SRC` and
  `UMAMI_WEBSITE_ID` are both unset by default and the tracker is not rendered.
- **Third-party lookups** the catalog performs on request: World Flora Online,
  GBIF, and iNaturalist reference photographs, which are proxied through your
  origin and never stored.
- **Retention and deletion**, including how someone asks for an account or a
  study to be removed and how long backups keep it after that.

## The software notice is not optional

`/software` is outside all of this. It is the offer of source that section 13
of the AGPL requires, so it answers whether or not the public site is enabled,
and it is linked from every signed-in page.

**If you modify Padiush and run it as a service, point it at your own source:**

```dotenv
PADIUSH_SOURCE_URL="https://example.org/your/fork"
```

Leaving it aimed at the upstream repository while running modified code does not
satisfy the licence — the people using your instance are entitled to the source
of *the version they are using*.
