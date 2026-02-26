from dataclasses import dataclass, field
from pathlib import Path
from typing import List, Optional, Tuple


@dataclass
class SubtitleWord:
    text: str
    start: float
    end: float


@dataclass
class SubtitleSegment:
    text: str
    start: float
    end: float
    words: List[SubtitleWord] = field(default_factory=list)


@dataclass
class Project:
    name: str
    audio_path: Optional[Path] = None
    background_image: Optional[Path] = None
    theme: str = "Dark Spiritual"
    music_track: str = "music1.mp3"
    resolution: Tuple[int, int] = (1080, 1920)
    auto_voice_enhance: bool = True
    word_level_subtitles: bool = False
    beat_cut_enabled: bool = True
    bw_hook_enabled: bool = True
    semantic_map_enabled: bool = True
    lut_enabled: bool = True
    facecam_enabled: bool = True
    intelligent_music_enabled: bool = True
    micro_ken_burns_enabled: bool = True
    subtitle_animation_enabled: bool = True
    subtitles: List[SubtitleSegment] = field(default_factory=list)

    @property
    def valid(self) -> bool:
        return bool(self.audio_path and self.audio_path.exists())
