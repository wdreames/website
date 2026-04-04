# Gratitude Journal API

This is a REST API for retrieving gratitude journal entries, converted from the original PHP implementation.

## Setup

1. Install dependencies: `pip install -r requirements.txt`
2. Set environment variables: `JWT_SECRET_KEY`, `REDIS_URL` (optional, defaults provided)
3. Run the server: `uvicorn main:app --reload`

## Endpoints

- `POST /api/auth/login`: Login with username and password (password is the token).
- `POST /api/journal/random-entry`: Get random entry.
- `POST /api/journal/date-selection`: Get entry by date.
- `POST /api/journal/undo`: Undo last action.
- `POST /api/journal/redo`: Redo last action.

All journal endpoints require `Authorization: Bearer <token>` header.

## Migration

Update the frontend to use these endpoints instead of the PHP file.