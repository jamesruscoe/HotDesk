# Ridge engine

FastAPI service for structural calculations, called by the Laravel app over HTTP.

| Endpoint | Auth | What it does |
|----------|------|--------------|
| `GET /health` | none | Liveness check |
| `POST /beam/calculate` | Bearer token | Simply supported beam, uniform load |
| `POST /slab/calculate` | Bearer token | Simply supported rectangular slab, uniform load (Navier series on a mesh) |

Every endpoint except `/health` needs `Authorization: Bearer $ENGINE_TOKEN`. If `ENGINE_TOKEN` is not set, they all return 401.

## Run locally

```sh
cd engine
uv sync
ENGINE_TOKEN=local-token uv run uvicorn ridge_engine.main:app --port 8001 --reload
uv run pytest
```

In the Laravel `.env`, set `ENGINE_URL=http://127.0.0.1:8001` and `ENGINE_TOKEN=local-token`. Then log in and open `/engine-test`.

## Deploy on Laravel Cloud

The engine is its own Cloud application, deployed from the same repository as the Laravel app.

1. **New application.** Pick this repository. When Cloud detects the monorepo, choose `engine` as the root directory.
2. **Start command.** Cloud detects FastAPI from `uv.lock` and suggests a uvicorn command. Change it to:
   ```
   uvicorn ridge_engine.main:app --host :: --port $PORT
   ```
   The pre-filled command usually says `main:app` and `--host ''`. Both are wrong here.
3. **Engine environment variables.** Set `ENGINE_TOKEN` to a long random secret.
4. **Laravel app environment variables.** Set `ENGINE_URL` to the engine app's URL (`https://….laravel.cloud`) and `ENGINE_TOKEN` to the same secret. Then redeploy the Laravel app.
5. **Check it.** Log in to the Laravel app and open `/engine-test`. All three calls should report status 200.

Notes:

- `.python-version` pins Python to 3.12, and `uv.lock` pins the dependencies.
- If the engine is on a Flex size with hibernation, the first request after it sleeps is slower while it wakes up.
