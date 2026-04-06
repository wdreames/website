import os
from pydantic_settings import BaseSettings

class Settings(BaseSettings):
    jwt_secret_key: str = os.getenv("JWT_SECRET_KEY", "your-secret-key-here")
    jwt_algorithm: str = "HS256"
    jwt_expiration_hours: int = 1
    token_file_path: str = "../../.gratitude-token"
    redis_url: str = os.getenv("REDIS_URL", "redis://localhost:6379")

settings = Settings()