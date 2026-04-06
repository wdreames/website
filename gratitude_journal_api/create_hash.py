"""Generate a hashed token using passlib's bcrypt_sha256.

Usage: python create_hash.py <token>
Run once and save the output to ../../.gratitude-token
"""

from auth import get_password_hash
import sys

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Usage: python create_hash.py <token>")
        sys.exit(1)

    token = sys.argv[1]
    hashed = get_password_hash(token).decode()
    print(hashed, end='')
