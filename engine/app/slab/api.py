from fastapi import APIRouter, Depends

from app.security import require_token
from app.slab.contracts import SlabInput, SlabResult
from app.slab.solve import solve

router = APIRouter(prefix="/slab", dependencies=[Depends(require_token)])


@router.post("/calculate")
def calculate(slab_input: SlabInput) -> SlabResult:
    return solve(slab_input)
