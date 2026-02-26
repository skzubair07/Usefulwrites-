from __future__ import annotations

import random
from datetime import datetime
from pathlib import Path

from moviepy.editor import AudioFileClip, ColorClip, CompositeAudioClip, CompositeVideoClip, ImageClip
from moviepy.video.fx.all import blackwhite, colorx, lum_contrast
from proglog import ProgressBarLogger

from config import EXPORTS_DIR, FPS
from engine.audio_enhancer import AudioEnhancer
from engine.errors import PathValidationError, RenderFailureError
from engine.ffmpeg_utils import detect_ffmpeg
from engine.footage_engine import FootageEngine
from engine.music_engine import MusicEngine
from engine.semantic_mapper import SemanticMapper
from engine.subtitle_engine import SubtitleEngine
from engine.transition_engine import TransitionEngine


class MoviepyUILogger(ProgressBarLogger):
    def __init__(self, log_fn, progress_callback=None):
        super().__init__()
        self.log_fn = log_fn
        self.progress_callback = progress_callback

    def bars_callback(self, bar, attr, value, old_value=None):
        if bar == "t" and attr == "index":
            total = self.bars[bar]["total"] or 1
            pct = int((value / total) * 100)
            if self.progress_callback:
                self.progress_callback(min(99, max(0, pct)))

    def callback(self, **changes):
        if "message" in changes and changes["message"]:
            self.log_fn(f"FFmpeg: {changes['message']}")


class RenderEngine:
    def __init__(self, logger) -> None:
        self.logger = logger
        self.ffmpeg_path = detect_ffmpeg()
        self.logger.log(f"FFmpeg detected: {self.ffmpeg_path}")
        self.footage = FootageEngine(logger)
        self.subtitles = SubtitleEngine(logger)
        self.music = MusicEngine(logger)
        self.transitions = TransitionEngine(logger)
        self.audio_enhancer = AudioEnhancer(logger)
        self.semantic = SemanticMapper(logger)

    def _safe(self, name, enabled, fn):
        if not enabled:
            return None
        try:
            return fn()
        except Exception as exc:
            self.logger.log(f"{name} failed, disabling module: {exc}")
            return None

    def _build_facecam_base(self, project, voice):
        duration = voice.duration
        if project.background_image and project.background_image.exists():
            facecam = ImageClip(str(project.background_image)).set_duration(duration)
            facecam = facecam.resize(newsize=project.resolution)
        else:
            facecam = ColorClip(project.resolution, color=(25, 25, 25), duration=duration)

        if not project.facecam_enabled:
            return facecam

        peaks = self._safe("facecam_audio_peaks", True, lambda: voice.to_soundarray(fps=4000))
        if peaks is None:
            return facecam

        def zoom_at(t):
            idx = min(int(t * 4000), len(peaks) - 1)
            amp = abs(peaks[idx]).max() if peaks.ndim > 1 else abs(peaks[idx])
            cycle = 12 + int((hash(project.name) % 4))
            if int(t) % cycle == 0:
                return 1.0
            return 1.10 if amp > 0.23 else 1.0

        return facecam.resize(zoom_at)

    def _apply_bw_hook(self, clip, emphasis_time, enabled):
        if not enabled:
            return clip
        hook_end = min(2.0, clip.duration)
        bw_part = blackwhite(clip.subclip(0, hook_end))
        color_start = min(max(emphasis_time, hook_end), clip.duration)
        clips = [bw_part.set_start(0)]
        if color_start < clip.duration:
            clips.append(clip.subclip(color_start, clip.duration).set_start(color_start))
        return CompositeVideoClip(clips, size=clip.size).set_duration(clip.duration)

    def _apply_lut(self, clip, enabled):
        if not enabled:
            return clip
        lut = random.choice(["warm", "cool", "moody"])
        self.logger.log(f"LUT selected: {lut}")
        if lut == "warm":
            return colorx(clip, 1.05)
        if lut == "cool":
            return lum_contrast(colorx(clip, 0.98), lum=0, contrast=5)
        return lum_contrast(colorx(clip, 0.95), lum=-5, contrast=8)

    def _emphasis_time(self, project):
        strong = {"allah", "rasool", "akhirat", "jannat", "jahannam"}
        for segment in project.subtitles:
            for word in segment.words:
                if word.text.lower().strip(".,!?") in strong:
                    return word.start
        return 2.0

    def _semantic_cutaways(self, project):
        cutaways = []
        if not project.semantic_map_enabled:
            return cutaways
        for segment in project.subtitles:
            category = self.semantic.detect_category(segment.text)
            if not category:
                continue
            matches = self.semantic.find_matching_footage(category)
            if not matches:
                continue
            selected = random.choice(matches)
            duration = min(3.0, max(1.5, segment.end - segment.start))
            cut = self._safe(
                "semantic_cutaway",
                True,
                lambda s=selected, st=segment.start, d=duration: self.footage.build_semantic_cutaway(
                    s, st, d, project.resolution
                ),
            )
            if cut:
                cutaways.append(cut)
        return cutaways

    def render_project(self, project, progress_callback=None) -> Path:
        if not project.audio_path:
            raise PathValidationError("Audio path is missing")
        if not project.audio_path.exists():
            raise PathValidationError(f"Audio file not found: {project.audio_path}")

        voice_path = project.audio_path
        enhanced = self._safe(
            "audio_enhance",
            project.auto_voice_enhance,
            lambda: self.audio_enhancer.enhance(project.audio_path, project.name),
        )
        if enhanced:
            voice_path = enhanced

        try:
            voice = AudioFileClip(str(voice_path))
            self.logger.log(f"Render start: {project.name}")

            base_facecam = self._build_facecam_base(project, voice)
            emphasis = self._emphasis_time(project)

            footage_clips = self._safe(
                "beat_cut_footage",
                project.beat_cut_enabled,
                lambda: self.footage.build_sequence(
                    voice.duration,
                    project.resolution,
                    beat_cut_enabled=project.beat_cut_enabled,
                    micro_ken_burns_enabled=project.micro_ken_burns_enabled,
                ),
            ) or []
            footage_clips = [self.transitions.apply(c) for c in footage_clips]

            overlay_clips = self._semantic_cutaways(project)

            subtitle_clips = [
                self.subtitles.create_clip(
                    segment,
                    project.resolution,
                    project.theme,
                    project.word_level_subtitles,
                    project.subtitle_animation_enabled,
                )
                for segment in project.subtitles
            ]

            layers = [base_facecam, *footage_clips, *overlay_clips]
            video = CompositeVideoClip(layers, size=project.resolution).set_duration(voice.duration)
            video = self._apply_bw_hook(video, emphasis, project.bw_hook_enabled)
            video = self._apply_lut(video, project.lut_enabled)

            voice_audio, bg_audio = self.music.mix(voice, project.music_track, project.intelligent_music_enabled)
            final_audio = CompositeAudioClip([voice_audio, bg_audio])
            final_video = CompositeVideoClip([video, *subtitle_clips], size=project.resolution).set_audio(final_audio)

            file_name = f"{project.name.replace(' ', '_')}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.mp4"
            output = EXPORTS_DIR / file_name
            self.logger.log(f"Writing output: {output.name}")

            mp_logger = MoviepyUILogger(self.logger.log, progress_callback)
            final_video.write_videofile(
                str(output),
                fps=FPS,
                codec="libx264",
                audio_codec="aac",
                threads=4,
                preset="medium",
                ffmpeg_params=["-crf", "20"],
                logger=mp_logger,
            )
            for clip in [voice, final_video, video, base_facecam, *footage_clips, *overlay_clips, *subtitle_clips]:
                try:
                    clip.close()
                except Exception:
                    pass
            if progress_callback:
                progress_callback(100)
            self.logger.log(f"Render complete: {project.name}")
            return output
        except Exception as exc:
            raise RenderFailureError(f"Render failed for {project.name}: {exc}") from exc
