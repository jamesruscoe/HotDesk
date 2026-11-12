from fastapi import APIRouter, Depends
from pydantic import BaseModel, Field

from ridge_engine import beam_theory
from ridge_engine.security import require_token

router = APIRouter(prefix="/beam", dependencies=[Depends(require_token)])


class BeamInput(BaseModel):
    span_m: float = Field(gt=0)
    load_kn_per_m: float = Field(ge=0)
    e_gpa: float = Field(gt=0)
    i_cm4: float = Field(gt=0)


@router.post("/calculate")
def calculate(beam: BeamInput) -> dict[str, float]:
    w = beam.load_kn_per_m * 1e3  # N/m
    ei = beam.e_gpa * 1e9 * beam.i_cm4 * 1e-8  # N·m²

    return {
        "max_moment_knm": beam_theory.max_moment(w, beam.span_m) / 1e3,
        "max_shear_kn": beam_theory.max_shear(w, beam.span_m) / 1e3,
        "max_deflection_mm": beam_theory.max_deflection(w, beam.span_m, ei) * 1e3,
    }
