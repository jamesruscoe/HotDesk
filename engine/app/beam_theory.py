"""Closed-form results for a simply supported beam under a uniformly distributed load. SI units."""


def max_moment(w: float, length: float) -> float:
    return w * length**2 / 8


def max_shear(w: float, length: float) -> float:
    return w * length / 2


def max_deflection(w: float, length: float, ei: float) -> float:
    return 5 * w * length**4 / (384 * ei)
