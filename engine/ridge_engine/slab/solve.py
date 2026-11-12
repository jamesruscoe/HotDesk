from math import pi, sin

from ridge_engine.slab.contracts import SlabInput, SlabResult
from ridge_engine.slab.geometry import RectangularSlab
from ridge_engine.slab.mesh import grid

# Odd terms up to 49 put the centre deflection well within 0.1% of the exact series.
SERIES_TERMS = range(1, 50, 2)


def navier_deflection(slab: RectangularSlab, q: float, d: float, x: float, y: float) -> float:
    """Navier series for a simply supported rectangular plate under uniform load q (Pa)."""
    total = 0.0
    for m in SERIES_TERMS:
        for n in SERIES_TERMS:
            total += (
                sin(m * pi * x / slab.a) * sin(n * pi * y / slab.b)
                / (m * n * ((m / slab.a) ** 2 + (n / slab.b) ** 2) ** 2)
            )
    return 16 * q / (pi**6 * d) * total


def solve(slab_input: SlabInput) -> SlabResult:
    slab = RectangularSlab(slab_input.length_x_m, slab_input.length_y_m, slab_input.thickness_mm / 1e3)
    d = slab.flexural_rigidity(slab_input.e_gpa * 1e9, slab_input.poisson)
    q = slab_input.load_kpa * 1e3

    nodes = grid(slab, slab_input.mesh_divisions)
    deflection, at = max((navier_deflection(slab, q, d, x, y), (x, y)) for x, y in nodes)

    return SlabResult(
        flexural_rigidity_knm=d / 1e3,
        max_deflection_mm=deflection * 1e3,
        max_deflection_at_m=at,
        node_count=len(nodes),
    )
