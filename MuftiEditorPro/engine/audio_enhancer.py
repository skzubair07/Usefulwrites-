from __future__ import annotations

from pathlib import Path

from pydub import AudioSegment
from pydub.effects import compress_dynamic_range

from config import DRAFTS_DIR, VOICE_TARGET_PEAK_DBFS


class AudioEnhancer:
    def __init__(self, logger) -> None:
        self.logger = logger

    def _speech_presence_boost(self, audio: AudioSegment) -> AudioSegment:
        mid = audio.high_pass_filter(2000).low_pass_filter(4000) + 2.5
        return audio.overlay(mid)

    def enhance(self, source_path: Path, project_name: str) -> Path:
        audio = AudioSegment.from_file(source_path)
        self.logger.log(f"Voice enhance: input peak={audio.max_dBFS:.2f} dBFS")

        cleaned = audio.high_pass_filter(80)
        compressed = compress_dynamic_range(cleaned, threshold=-24.0, ratio=2.5, attack=5.0, release=120.0)
        eq = self._speech_presence_boost(compressed)

        gain_to_target = VOICE_TARGET_PEAK_DBFS - eq.max_dBFS
        safe_gain = max(-18.0, min(18.0, gain_to_target))
        normalized = eq.apply_gain(safe_gain)

        out_path = DRAFTS_DIR / f"{project_name.replace(' ', '_')}_enhanced.wav"
        normalized.export(out_path, format="wav")
        self.logger.log(f"Voice enhance: output peak={normalized.max_dBFS:.2f} dBFS")
        return out_path
