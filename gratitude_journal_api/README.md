# Gratitude Journal API

This is a REST API for retrieving gratitude journal entries.

## Setup

See details outlined in [setup-instructions.md](../setup-instructions.md).

## Endpoints

- `POST /api/auth/login`: Login with username and password (password is the token).
- `POST /api/journal/random-entry`: Get random entry.
- `POST /api/journal/date-selection`: Get entry by date.
- `POST /api/journal/undo`: Undo last action.
- `POST /api/journal/redo`: Redo last action.

All journal endpoints require `Authorization: Bearer <token>` header.