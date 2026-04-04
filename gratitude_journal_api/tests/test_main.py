import pytest
from fastapi.testclient import TestClient
from main import app

client = TestClient(app)

def test_login():
    response = client.post("/api/auth/login", json={"username": "user", "password": "invalid"})
    assert response.status_code == 401

# Add more tests as needed