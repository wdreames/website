from datetime import datetime, timedelta
from typing import Optional
from jose import JWTError, jwt
from passlib.context import CryptContext
from fastapi import Depends, HTTPException, status
from fastapi.security import OAuth2PasswordBearer
from config import settings
import bcrypt
import hashlib

oauth2_scheme = OAuth2PasswordBearer(tokenUrl="api/auth/login")

# Simple in-memory rate limiting (TODO: for demo; use Redis in production)
failed_attempts = {}

def get_prehashed_password(password: str) -> bytes:
    """Pre-hashes the password using SHA-256 to bypass bcrypt's 72-character limit."""
    # Convert password to bytes and hash it
    sha256_hash = hashlib.sha256(password.encode('utf-8')).digest()
    return sha256_hash

def verify_password(plain_password: str, hashed_password: str) -> bool:
    pre_hashed = get_prehashed_password(plain_password)
    return bcrypt.checkpw(pre_hashed, hashed_password)
    # return pwd_context.verify(pre_hashed, hashed_password)

def get_password_hash(plain_password: str) -> str:
    pre_hashed = get_prehashed_password(plain_password)
    return bcrypt.hashpw(pre_hashed, bcrypt.gensalt())
    # return pwd_context.hash(pre_hashed)

def authenticate_user(username: str, password: str) -> Optional[str]:
    # TODO: For simplicity, username is ignored, password is checked against token file
    if username != "wreames":  # Fixed username
        return None
    try:
        # TODO: Usernames and passwords should eventually be stored in a database
        with open(settings.token_file_path, "r") as f:
            hashed_token = f.read().strip().encode('utf-8')
        if verify_password(password, hashed_token):
            return username
    except FileNotFoundError:
        pass
    return None

def create_access_token(data: dict, expires_delta: Optional[timedelta] = None):
    to_encode = data.copy()
    if expires_delta:
        expire = datetime.utcnow() + expires_delta  # TODO: Fix deprecated usage
    else:
        expire = datetime.utcnow() + timedelta(hours=settings.jwt_expiration_hours)
    to_encode.update({"exp": expire})
    encoded_jwt = jwt.encode(to_encode, settings.jwt_secret_key, algorithm=settings.jwt_algorithm)
    return encoded_jwt

def get_current_user(token: str = Depends(oauth2_scheme)):
    credentials_exception = HTTPException(
        status_code=status.HTTP_401_UNAUTHORIZED,
        detail="Could not validate credentials",
        headers={"WWW-Authenticate": "Bearer"},
    )
    try:
        payload = jwt.decode(token, settings.jwt_secret_key, algorithms=[settings.jwt_algorithm])
        username: str = payload.get("sub")
        if username is None:
            raise credentials_exception
    except JWTError:
        raise credentials_exception
    return username

# Rate limiting helper
def check_rate_limit(username: str) -> bool:
    if username in failed_attempts:
        if failed_attempts[username] >= 3:
            return False
    return True

def increment_failed_attempts(username: str):
    if username not in failed_attempts:
        failed_attempts[username] = 0
    failed_attempts[username] += 1
