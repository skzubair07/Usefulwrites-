from __future__ import annotations

import random
from collections import deque
from pathlib import Path
from typing import List

from moviepy.editor import VideoFileClip
from moviepy.video.fx.all import speedx

from config import FOOTAGE_DIR
from engine.errors import FootageMissingError


class FootageEngine:
    def __init__(self, logger) -> None:
        self.logger = logger
        self._recent = deque(maxlen=3)

    def _available_files(self) -> List[Path]:
        return [p for p in FOOTAGE_DIR.iterdir() if p.suffix.lower() in {".mp4", ".mov", ".mkv"}]

    def _pick_file(self, available: List[Path]) -> Path:
        candidates = [p for p in available if p.name not in self._recent] or available
        chosen = random.choice(candidates)
        self._recent.append(chosen.name)
        return chosen

    def beat_pattern(self, remaining: float) -> float:
        cycle = [3.0, 3.0, random.uniform(6.0, 8.0)]
        return min(cycle[random.randint(0, 2)], remaining)

    def build_sequence(self, total_duration: float, resolution, beat_cut_enabled=True, micro_ken_burns_enabled=True):
        available = self._available_files()
        if not available:
            raise FootageMissingError("No stock footage found in /footage")

        clips = []
        current = 0.0
        while current < total_duration:
            file = self._pick_file(available)
            source = VideoFileClip(str(file))
            duration = self.beat_pattern(total_duration - current) if beat_cut_enabled else min(
                random.uniform(2.0, 5.0), total_duration - current
            )
            speed = random.uniform(0.95, 1.05)
            source = source.fx(speedx, factor=speed)
            start = random.uniform(0, max(0.0, source.duration - duration))
            clip = source.subclip(start, start + duration)
            zoom = random.uniform(1.02, 1.10)
            x_center = clip.w * random.uniform(0.45, 0.55)
            y_center = clip.h * random.uniform(0.45, 0.55)
            clip = clip.resize(zoom).crop(width=resolution[0], height=resolution[1], x_center=x_center, y_center=y_center)
            if micro_ken_burns_enabled:
                rotation = random.uniform(0.3, 0.5) * random.choice([-1, 1])
                clip = clip.rotate(lambda t: rotation * (t / max(duration, 0.1)))
            clip = clip.set_start(current)
            clips.append(clip)
            current += duration
            self.logger.log(
                f"Footage: {file.name} start={start:.2f} dur={duration:.2f} speed={speed:.2f} beat={beat_cut_enabled}"
            )
        return clips

    def build_semantic_cutaway(self, file: Path, start_at: float, duration: float, resolution):
        source = VideoFileClip(str(file))
        start = random.uniform(0, max(0.0, source.duration - duration))
        return (
            source.subclip(start, start + duration)
            .resize(1.05)
            .crop(width=resolution[0], height=resolution[1], x_center=source.w / 2, y_center=source.h / 2)
            .set_start(start_at)
        )
