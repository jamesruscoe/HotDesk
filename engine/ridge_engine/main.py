from fastapi import FastAPI

from ridge_engine import beam
from ridge_engine.slab import api as slab

app = FastAPI(title="Ridge engine")
app.include_router(beam.router)
app.include_router(slab.router)


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}
