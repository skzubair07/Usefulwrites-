from __future__ import annotations

import json
from pathlib import Path
from typing import List

from openai import OpenAI

from config import WHISPER_API_KEY, WHISPER_MODEL
from engine.errors import APIMissingError
from engine.models import SubtitleSegment, SubtitleWord


class TranscriptionEngine:
    def __init__(self, logger) -> None:
        self.logger = logger

    def transcribe(self, audio_path: Path) -> List[SubtitleSegment]:
        if WHISPER_API_KEY == "PUT_OPENAI_API_KEY_HERE":
            raise APIMissingError("Missing OpenAI API key in config.py")

        self.logger.log(f"Transcribing: {audio_path.name}")
        client = OpenAI(api_key=WHISPER_API_KEY)
        with audio_path.open("rb") as file:
            response = client.audio.transcriptions.create(
                model=WHISPER_MODEL,
                file=file,
                response_format="verbose_json",
                language="ur",
                timestamp_granularities=["segment", "word"],
            )

        data = json.loads(response.model_dump_json())
        words = data.get("words", [])
        segments = []
        for seg in data.get("segments", []):
            text = " ".join(seg.get("text", "").split())
            if not text:
                continue
            in_segment_words = [
                SubtitleWord(text=w["word"], start=float(w["start"]), end=float(w["end"]))
                for w in words
                if float(w["start"]) >= float(seg["start"]) and float(w["end"]) <= float(seg["end"])
            ]
            segments.append(
                SubtitleSegment(
                    text=text,
                    start=float(seg["start"]),
                    end=float(seg["end"]),
                    words=in_segment_words,
                )
            )
        self.logger.log(f"Transcription complete: {len(segments)} segments")
        return segments
