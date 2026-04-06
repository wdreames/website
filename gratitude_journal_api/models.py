from pydantic import BaseModel
from typing import Optional
from datetime import date

class LoginRequest(BaseModel):
    username: str
    password: str

class TokenResponse(BaseModel):
    access_token: str
    token_type: str = "bearer"

class RandomEntryRequest(BaseModel):
    keywords: Optional[str] = None
    start_date: Optional[date] = None
    end_date: Optional[date] = None
    previous_text: str

class DateSelectionRequest(BaseModel):
    date: date
    previous_text: str

class UndoRedoRequest(BaseModel):
    previous_text: str

class JournalResponse(BaseModel):
    journal_text: str
    undo_stack_empty: bool
    redo_stack_empty: bool