from __future__ import annotations

import numpy as np
from moviepy.audio.fx.all import audio_loop, volumex
from moviepy.editor import AudioFileClip

from config import MUSIC_DIR
from engine.errors import MusicMissingError


class MusicEngine:
    def __init__(self, logger) -> None:
        self.logger = logger

    def get_track(self, name: str):
        track = MUSIC_DIR / name
        if not track.exists():
            raise MusicMissingError(f"Music track missing: {name}")
        return track

    def _voice_activity(self, voice_clip, sample_step=0.15):
        samples = voice_clip.to_soundarray(fps=8000)
        rms = np.sqrt(np.mean(samples**2, axis=1)) if samples.ndim > 1 else np.abs(samples)
        window = max(1, int(8000 * sample_step))
        block = rms[: len(rms) - (len(rms) % window)]
        if len(block) == 0:
            return np.array([0.0]), sample_step
        levels = block.reshape(-1, window).mean(axis=1)
        return levels, sample_step

    def _duck_gain(self, voice_clip, intelligent_music_enabled=True):
        levels, sample_step = self._voice_activity(voice_clip)
        max_level = max(levels.max(), 1e-6)
        silence_windows = int(0.5 / sample_step)

        def gain(t):
            idx = min(int(t / sample_step), len(levels) - 1)
            activity = levels[idx] / max_level
            base_gain = max(0.10, 0.28 - 0.18 * activity)
            if intelligent_music_enabled:
                start = max(0, idx - silence_windows)
                silent = np.all(levels[start : idx + 1] < (0.08 * max_level))
                if silent:
                    return float(min(0.5, base_gain * 1.15))
            return float(base_gain)

        return gain

    def mix(self, voice_clip, music_name: str, intelligent_music_enabled=True):
        track = self.get_track(music_name)
        bg = AudioFileClip(str(track))
        bg = audio_loop(bg, duration=voice_clip.duration)
        bg = volumex(bg, self._duck_gain(voice_clip, intelligent_music_enabled))
        self.logger.log(f"Background music applied with ducking: {music_name}")
        return voice_clip, bg
