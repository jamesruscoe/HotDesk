from pytest import approx

slab = {"length_x_m": 5, "length_y_m": 5, "thickness_mm": 200, "load_kpa": 10, "e_gpa": 30, "poisson": 0.2}


def test_square_slab_matches_timoshenko_coefficient(client):
    result = client.post("/slab/calculate", json=slab).json()

    # Timoshenko: centre deflection of a simply supported square plate is 0.00406 q a⁴ / D.
    d = result["flexural_rigidity_knm"] * 1e3
    expected_mm = 0.00406 * 10e3 * 5**4 / d * 1e3
    assert result["max_deflection_mm"] == approx(expected_mm, rel=1e-3)
    assert result["max_deflection_at_m"] == [2.5, 2.5]
    assert result["node_count"] == 21 * 21


def test_slab_requires_token(client):
    assert client.post("/slab/calculate", json=slab, headers={"Authorization": ""}).status_code == 401
