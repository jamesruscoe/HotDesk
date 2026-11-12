from dataclasses import dataclass


@dataclass(frozen=True)
class RectangularSlab:
    """Plan dimensions a (x) and b (y) and thickness t, all in metres."""

    a: float
    b: float
    t: float

    def flexural_rigidity(self, e: float, poisson: float) -> float:
        """D = E t³ / 12(1 - ν²), in N·m."""
        return e * self.t**3 / (12 * (1 - poisson**2))
