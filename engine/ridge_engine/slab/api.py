from fastapi import APIRouter, Depends

from ridge_engine.security import require_token
from ridge_engine.slab.contracts import SlabInput, SlabResult
from ridge_engine.slab.solve import solve

router = APIRouter(prefix="/slab", dependencies=[Depends(require_token)])


@router.post("/calculate")
def calculate(slab_input: SlabInput) -> SlabResult:
    return solve(slab_input)
