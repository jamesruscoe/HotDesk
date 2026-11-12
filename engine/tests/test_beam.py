beam = {"span_m": 6, "load_kn_per_m": 10, "e_gpa": 210, "i_cm4": 8356}


def test_health_is_public(client):
    assert client.get("/health", headers={"Authorization": ""}).json() == {"status": "ok"}


def test_calculate_requires_token(client):
    assert client.post("/beam/calculate", json=beam, headers={"Authorization": "Bearer wrong"}).status_code == 401


def test_calculate_beam(client):
    result = client.post("/beam/calculate", json=beam).json()

    assert result["max_moment_knm"] == 45.0  # 10 * 6² / 8
    assert result["max_shear_kn"] == 30.0
    assert round(result["max_deflection_mm"], 2) == 9.62
