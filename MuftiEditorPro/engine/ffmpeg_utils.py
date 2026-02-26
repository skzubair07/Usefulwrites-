from __future__ import annotations

import shutil

import imageio_ffmpeg

from engine.errors import FFmpegMissingError


def detect_ffmpeg() -> str:
    system_ffmpeg = shutil.which("ffmpeg")
    if system_ffmpeg:
        return system_ffmpeg
    try:
        bundled = imageio_ffmpeg.get_ffmpeg_exe()
        if bundled:
            return bundled
    except Exception as exc:
        raise FFmpegMissingError(f"FFmpeg auto-detection failed: {exc}") from exc
    raise FFmpegMissingError(
        "FFmpeg is missing. Install FFmpeg and add it to PATH, or reinstall dependencies for imageio-ffmpeg."
    )
