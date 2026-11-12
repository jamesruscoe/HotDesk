from pydantic import BaseModel, Field


class SlabInput(BaseModel):
    length_x_m: float = Field(gt=0)
    length_y_m: float = Field(gt=0)
    thickness_mm: float = Field(gt=0)
    load_kpa: float = Field(ge=0)
    e_gpa: float = Field(gt=0)
    poisson: float = Field(default=0.2, ge=0, lt=0.5)
    mesh_divisions: int = Field(default=20, ge=2, le=100)


class SlabResult(BaseModel):
    flexural_rigidity_knm: float
    max_deflection_mm: float
    max_deflection_at_m: tuple[float, float]
    node_count: int
