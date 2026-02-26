from pathlib import Path

APP_NAME = "Mufti Editor Pro"
BASE_DIR = Path(__file__).resolve().parent
ASSETS_DIR = BASE_DIR / "assets"
FOOTAGE_DIR = BASE_DIR / "footage"
MUSIC_DIR = BASE_DIR / "music"
EXPORTS_DIR = BASE_DIR / "exports"
DRAFTS_DIR = BASE_DIR / "drafts"
LOGS_DIR = BASE_DIR / "logs"
VISUAL_MAP_FILE = ASSETS_DIR / "visual_map.json"

REQUIRED_DIRS = [
    ASSETS_DIR,
    FOOTAGE_DIR,
    MUSIC_DIR,
    EXPORTS_DIR,
    DRAFTS_DIR,
    LOGS_DIR,
    ASSETS_DIR / "fonts",
]

WHISPER_API_KEY = "PUT_OPENAI_API_KEY_HERE"
WHISPER_MODEL = "whisper-1"
DEFAULT_THEME = "Dark Spiritual"
DEFAULT_MUSIC = "music1.mp3"
DEFAULT_AUTO_VOICE_ENHANCE = True
DEFAULT_WORD_LEVEL = False
FPS = 30
VIDEO_QUALITY = "medium"
VOICE_TARGET_PEAK_DBFS = -1.0
