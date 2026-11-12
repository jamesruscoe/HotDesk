from app.slab.geometry import RectangularSlab


def grid(slab: RectangularSlab, divisions: int) -> list[tuple[float, float]]:
    """Nodes of a regular (divisions + 1)² grid over the slab, edges included."""
    return [
        (slab.a * i / divisions, slab.b * j / divisions)
        for i in range(divisions + 1)
        for j in range(divisions + 1)
    ]
