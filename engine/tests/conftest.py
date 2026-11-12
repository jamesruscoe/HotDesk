import os

import pytest
from fastapi.testclient import TestClient

os.environ["ENGINE_TOKEN"] = "test-token"


@pytest.fixture
def client() -> TestClient:
    from ridge_engine.main import app

    return TestClient(app, headers={"Authorization": "Bearer test-token"})
