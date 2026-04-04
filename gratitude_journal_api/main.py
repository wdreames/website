from fastapi import FastAPI, Depends, HTTPException, status
from auth import authenticate_user, create_access_token, get_current_user, check_rate_limit, increment_failed_attempts
from journal import JournalService, build_parameters
from models import LoginRequest, TokenResponse, RandomEntryRequest, DateSelectionRequest, UndoRedoRequest, JournalResponse
from datetime import timedelta

app = FastAPI()

@app.post("/api/auth/login", response_model=TokenResponse)
async def login(request: LoginRequest):
    user = authenticate_user(request.username, request.password)
    if not user:
        increment_failed_attempts(request.username)
        if not check_rate_limit(request.username):
            raise HTTPException(status_code=429, detail="Too many failed attempts")
        raise HTTPException(status_code=401, detail="Invalid credentials")
    access_token = create_access_token(data={"sub": user}, expires_delta=timedelta(hours=1))
    return TokenResponse(access_token=access_token)

@app.post("/api/journal/random-entry", response_model=JournalResponse)
async def random_entry(request: RandomEntryRequest, current_user: str = Depends(get_current_user)):
    service = JournalService(current_user)
    params = build_parameters("random_entry", keywords=request.keywords, start_date=request.start_date, end_date=request.end_date)
    try:
        journal_text = service.execute_command(params, request.previous_text)
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
    undo_empty, redo_empty = service.get_stacks_status()
    return JournalResponse(journal_text=journal_text, undo_stack_empty=undo_empty, redo_stack_empty=redo_empty)

@app.post("/api/journal/date-selection", response_model=JournalResponse)
async def date_selection(request: DateSelectionRequest, current_user: str = Depends(get_current_user)):
    service = JournalService(current_user)
    params = build_parameters("date_selection", date=request.date)
    try:
        journal_text = service.execute_command(params, request.previous_text)
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
    undo_empty, redo_empty = service.get_stacks_status()
    return JournalResponse(journal_text=journal_text, undo_stack_empty=undo_empty, redo_stack_empty=redo_empty)

@app.post("/api/journal/undo", response_model=JournalResponse)
async def undo(request: UndoRedoRequest, current_user: str = Depends(get_current_user)):
    service = JournalService(current_user)
    # For undo, previous_text not used, but assume it's sent
    journal_text = service.undo("")
    undo_empty, redo_empty = service.get_stacks_status()
    return JournalResponse(journal_text=journal_text, undo_stack_empty=undo_empty, redo_stack_empty=redo_empty)

@app.post("/api/journal/redo", response_model=JournalResponse)
async def redo(request: UndoRedoRequest, current_user: str = Depends(get_current_user)):
    service = JournalService(current_user)
    journal_text = service.redo("")
    undo_empty, redo_empty = service.get_stacks_status()
    return JournalResponse(journal_text=journal_text, undo_stack_empty=undo_empty, redo_stack_empty=redo_empty)