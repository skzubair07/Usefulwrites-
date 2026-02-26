from __future__ import annotations

from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter, ImageFont

from ui.theme import THEMES


class PreviewEngine:
    def render_preview(self, background: Path | None, subtitle: str, theme_name: str, resolution=(540, 960)):
        if background and background.exists():
            image = Image.open(background).convert("RGB").resize(resolution)
        else:
            image = Image.new("RGB", resolution, THEMES[theme_name]["bg"])

        image = image.filter(ImageFilter.GaussianBlur(2.8))
        overlay_alpha = int(255 * THEMES[theme_name]["overlay"])
        overlay = Image.new("RGBA", resolution, (0, 0, 0, overlay_alpha))
        image = Image.alpha_composite(image.convert("RGBA"), overlay)

        draw = ImageDraw.Draw(image)
        try:
            font = ImageFont.truetype("Jameel Noori Nastaleeq", 42)
        except OSError:
            font = ImageFont.load_default()
        draw.text((30, resolution[1] - 220), subtitle, fill=THEMES[theme_name]["subtitle"], font=font)
        return image.convert("RGB")
