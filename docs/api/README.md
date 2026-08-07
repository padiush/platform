# Companion API — machine-readable spec

The `/api/v1` field-capture API for the mobile companion apps.

| File | What it is |
|---|---|
| [openapi.yaml](openapi.yaml) | OpenAPI 3.0 spec — the authoritative machine-readable contract. Import into Swagger UI / Redoc, or generate a client. |
| [padiush-companion.postman_collection.json](padiush-companion.postman_collection.json) | Postman collection covering every endpoint. |

The narrative contract (conventions, rationale, what's out of scope) lives in
[../contracts/companion-api.md](../contracts/companion-api.md); the offline
choreography in [../contracts/sync-protocol.md](../contracts/sync-protocol.md).

## Using the Postman collection

1. Import the collection. It ships with variables: `base_url`
   (default `http://localhost:8000/api/v1`), `token`, `project_id`, `instance_id`.
2. Run **Auth → Create token** with a real email/password. Its test script saves
   the bearer token into `{{token}}`, so every other request is authenticated.
3. Set `project_id` / `instance_id` as needed and run the Pull, Push and Media
   requests.

Keep this spec in step with the routes: when an `/api/v1` endpoint changes,
update `openapi.yaml` (and the collection if the shape changed) in the same
change — same rule as the rest of the docs layer.
