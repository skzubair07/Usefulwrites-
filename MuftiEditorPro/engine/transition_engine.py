import random


class TransitionEngine:
    def __init__(self, logger) -> None:
        self.logger = logger

    def apply(self, clip):
        transition = random.choice(["crossfade", "blur_dissolve", "flash", "zoom", "slide_fade"])
        self.logger.log(f"Transition applied: {transition}")
        if transition == "crossfade":
            return clip.crossfadein(0.3)
        if transition == "blur_dissolve":
            return clip.fadein(0.2).fadeout(0.2)
        if transition == "flash":
            return clip.fadein(0.05)
        if transition == "zoom":
            return clip.resize(lambda t: 1 + (0.04 * t))
        return clip.set_position(lambda t: (int(-20 + t * 20), 0)).fadein(0.2)
