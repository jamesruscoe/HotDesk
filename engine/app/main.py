from fastapi import FastAPI

from app import beam
from app.slab import api as slab

app = FastAPI(title="Engine")
app.include_router(beam.router)
app.include_router(slab.router)


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}
