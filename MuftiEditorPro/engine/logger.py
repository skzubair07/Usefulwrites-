from __future__ import annotations

from datetime import datetime
from pathlib import Path
from typing import Callable, List

from config import LOGS_DIR


class DebugLogger:
    def __init__(self) -> None:
        self.subscribers: List[Callable[[str], None]] = []
        self.log_file = LOGS_DIR / f"app_{datetime.now().strftime('%Y%m%d_%H%M%S')}.log"

    def subscribe(self, callback: Callable[[str], None]) -> None:
        self.subscribers.append(callback)

    def log(self, message: str) -> None:
        stamped = f"[{datetime.now().strftime('%H:%M:%S')}] {message}"
        self.log_file.parent.mkdir(parents=True, exist_ok=True)
        with self.log_file.open("a", encoding="utf-8") as file:
            file.write(stamped + "\n")
        for subscriber in self.subscribers:
            subscriber(stamped)
