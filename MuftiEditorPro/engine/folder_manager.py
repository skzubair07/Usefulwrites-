from config import REQUIRED_DIRS


def ensure_directories() -> None:
    for directory in REQUIRED_DIRS:
        directory.mkdir(parents=True, exist_ok=True)
