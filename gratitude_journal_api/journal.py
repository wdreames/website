import subprocess
import json
import redis
from config import settings
from typing import Tuple, List

r = redis.from_url(settings.redis_url)

class JournalService:
    def __init__(self, user_id: str):
        self.user_id = user_id
        self.undo_key = f"undo:{user_id}"
        self.redo_key = f"redo:{user_id}"

    def get_stacks_status(self) -> Tuple[bool, bool]:
        undo_empty = r.llen(self.undo_key) == 0
        redo_empty = r.llen(self.redo_key) == 0
        return undo_empty, redo_empty

    def execute_command(self, parameters: str, previous_text: str) -> str:
        # Call the Python script
        cmd = f"../gratitude_journal_analysis/env/bin/python ../gratitude_journal_analysis/src/print_journal_entries.py {parameters}"
        result = subprocess.run(cmd, shell=True, capture_output=True, text=True)
        if result.returncode != 0:
            raise Exception(f"Script failed: {result.stderr}")
        new_text = result.stdout.strip()
        # Push to undo stack
        entry = json.dumps({"prev": previous_text, "new": new_text})
        r.lpush(self.undo_key, entry)
        # Clear redo stack
        r.delete(self.redo_key)
        return new_text

    def undo(self, previous_text: str) -> str:
        if r.llen(self.undo_key) == 0:
            return "Use the fields and buttons at the bottom of the page to begin searching through my gratitude journal :). I hope you're doing well, future self!"
        entry = json.loads(r.lpop(self.undo_key).decode('utf-8'))
        # Push to redo
        r.lpush(self.redo_key, json.dumps(entry))
        return entry["prev"]

    def redo(self, previous_text: str) -> str:
        if r.llen(self.redo_key) == 0:
            return "Use the fields and buttons at the bottom of the page to begin searching through my gratitude journal :). I hope you're doing well, future self!"
        entry = json.loads(r.lpop(self.redo_key).decode('utf-8'))
        # Push to undo
        r.lpush(self.undo_key, json.dumps(entry))
        return entry["new"]

def build_parameters(request_type: str, **kwargs) -> str:
    params = request_type
    if 'keywords' in kwargs and kwargs['keywords']:
        params += f" --query \"{kwargs['keywords']}\""
    if 'start_date' in kwargs and kwargs['start_date']:
        params += f" --start_date {kwargs['start_date']}"
    if 'end_date' in kwargs and kwargs['end_date']:
        params += f" --end_date {kwargs['end_date']}"
    if 'date' in kwargs and kwargs['date']:
        params += f" --date {kwargs['date']}"
    return params