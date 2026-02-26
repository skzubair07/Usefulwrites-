from __future__ import annotations

import json
from pathlib import Path

from config import FOOTAGE_DIR, VISUAL_MAP_FILE


class SemanticMapper:
    def __init__(self, logger) -> None:
        self.logger = logger
        self.mapping = self._load_map()

    def _load_map(self):
        if not VISUAL_MAP_FILE.exists():
            self.logger.log("Semantic map missing, semantic engine disabled")
            return {}
        return json.loads(VISUAL_MAP_FILE.read_text(encoding="utf-8"))

    def detect_category(self, text: str) -> str | None:
        lower = text.lower()
        for keyword, category in self.mapping.items():
            if keyword in lower:
                return category
        return None

    def find_matching_footage(self, category: str) -> list[Path]:
        return [
            p
            for p in FOOTAGE_DIR.iterdir()
            if p.suffix.lower() in {".mp4", ".mov", ".mkv"} and category.lower() in p.stem.lower()
        ]
