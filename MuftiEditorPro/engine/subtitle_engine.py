from __future__ import annotations

from moviepy.editor import TextClip

from ui.theme import ISLAMIC_KEYWORDS, THEMES


class SubtitleEngine:
    def __init__(self, logger) -> None:
        self.logger = logger

    def highlight_keywords(self, text: str) -> str:
        marked = text
        for word in ISLAMIC_KEYWORDS:
            marked = marked.replace(word, f"[{word}]")
        return marked

    def _split_lines(self, text: str, max_chars=38) -> str:
        words = text.split()
        lines, current = [], []
        for word in words:
            trial = " ".join(current + [word])
            if len(trial) <= max_chars:
                current.append(word)
            else:
                if current:
                    lines.append(" ".join(current))
                current = [word]
        if current:
            lines.append(" ".join(current))
        return "\n".join(lines)

    def create_clip(self, segment, video_size, theme_name: str, word_level=False, animate=True):
        style = THEMES[theme_name]
        source_text = segment.text
        if word_level and segment.words:
            source_text = " ".join(w.text for w in segment.words)

        use_clean = video_size[1] <= 1080
        font_name = "Arial" if use_clean else "Jameel Noori Nastaleeq"
        txt = self._split_lines(self.highlight_keywords(source_text), 32 if use_clean else 38)
        self.logger.log(f"Subtitle clip: {segment.start:.2f}s - {segment.end:.2f}s")

        clip = (
            TextClip(
                txt=txt,
                fontsize=52 if use_clean else 56,
                font=font_name,
                color=style["subtitle"],
                method="caption",
                size=(video_size[0] - 140, None),
                align="center",
            )
            .set_start(segment.start)
            .set_end(segment.end)
            .set_position(("center", video_size[1] * 0.74))
        )
        if animate:
            return clip.crossfadein(0.2).crossfadeout(0.2)
        return clip
