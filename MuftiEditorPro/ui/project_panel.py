from __future__ import annotations

from pathlib import Path
from tkinter import filedialog

import customtkinter as ctk

from config import DEFAULT_AUTO_VOICE_ENHANCE, DEFAULT_MUSIC, DEFAULT_THEME, DEFAULT_WORD_LEVEL
from engine.models import Project


class ProjectPanel(ctk.CTkFrame):
    def __init__(self, master, on_project_change, on_preview, on_queue):
        super().__init__(master)
        self.projects: list[Project] = []
        self.on_project_change = on_project_change
        self.on_preview = on_preview
        self.on_queue = on_queue

        self.grid_rowconfigure(20, weight=1)

        self.name_entry = ctk.CTkEntry(self, placeholder_text="Project name")
        self.name_entry.grid(row=0, column=0, padx=8, pady=6, sticky="ew")
        ctk.CTkButton(self, text="Add Project", command=self.add_project).grid(row=1, column=0, padx=8, pady=6, sticky="ew")

        self.project_box = ctk.CTkComboBox(self, values=["No Project"], command=self.select_project)
        self.project_box.grid(row=2, column=0, padx=8, pady=6, sticky="ew")

        ctk.CTkButton(self, text="Select Audio", command=self.select_audio).grid(row=3, column=0, padx=8, pady=6, sticky="ew")
        ctk.CTkButton(self, text="Select Facecam/Image", command=self.select_image).grid(row=4, column=0, padx=8, pady=6, sticky="ew")

        self.theme = ctk.CTkComboBox(self, values=["Dark Spiritual", "Golden Elegant", "Minimal Clean"], command=self.update_current)
        self.theme.set(DEFAULT_THEME)
        self.theme.grid(row=5, column=0, padx=8, pady=6, sticky="ew")

        self.music = ctk.CTkComboBox(self, values=["music1.mp3", "music2.mp3", "music3.mp3"], command=self.update_current)
        self.music.set(DEFAULT_MUSIC)
        self.music.grid(row=6, column=0, padx=8, pady=6, sticky="ew")

        self.resolution = ctk.CTkSegmentedButton(self, values=["1080x1920", "1920x1080"], command=self.update_current)
        self.resolution.set("1080x1920")
        self.resolution.grid(row=7, column=0, padx=8, pady=6, sticky="ew")

        self.switches = {}
        labels = [
            ("voice", "Auto Voice Enhance", "auto_voice_enhance", DEFAULT_AUTO_VOICE_ENHANCE),
            ("word", "Word-Level Subs", "word_level_subtitles", DEFAULT_WORD_LEVEL),
            ("beat", "Beat Cut Engine", "beat_cut_enabled", True),
            ("bw", "B&W Hook", "bw_hook_enabled", True),
            ("semantic", "Semantic Mapping", "semantic_map_enabled", True),
            ("lut", "Random LUT", "lut_enabled", True),
            ("facecam", "Facecam Smart", "facecam_enabled", True),
            ("music_auto", "Intelligent Music", "intelligent_music_enabled", True),
            ("ken", "Micro Ken Burns", "micro_ken_burns_enabled", True),
            ("sub_anim", "Subtitle Animation", "subtitle_animation_enabled", True),
        ]
        row = 8
        for key, text, attr, default in labels:
            sw = ctk.CTkSwitch(self, text=text, command=lambda a=attr: self._switch_update(a))
            if default:
                sw.select()
            sw.grid(row=row, column=0, padx=8, pady=2, sticky="w")
            self.switches[attr] = sw
            row += 1

        ctk.CTkButton(self, text="Preview", command=self.on_preview).grid(row=19, column=0, padx=8, pady=6, sticky="ew")
        ctk.CTkButton(self, text="Add to Render Queue", command=self.queue_project).grid(row=21, column=0, padx=8, pady=6, sticky="ew")

    def _switch_update(self, _attr):
        self.update_current(None)

    def add_project(self):
        name = self.name_entry.get().strip() or f"Project {len(self.projects)+1}"
        project = Project(name=name)
        self.projects.append(project)
        self.project_box.configure(values=[p.name for p in self.projects])
        self.project_box.set(project.name)
        self.update_current(None)
        self.on_project_change(project)

    def get_current(self):
        value = self.project_box.get()
        return next((p for p in self.projects if p.name == value), None)

    def select_project(self, _):
        p = self.get_current()
        if not p:
            return
        self.theme.set(p.theme)
        self.music.set(p.music_track)
        self.resolution.set(f"{p.resolution[0]}x{p.resolution[1]}")
        for attr, sw in self.switches.items():
            sw.select() if getattr(p, attr) else sw.deselect()
        self.on_project_change(p)

    def select_audio(self):
        p = self.get_current()
        if not p:
            return
        file = filedialog.askopenfilename(filetypes=[("Audio", "*.mp3 *.wav *.m4a")])
        if file:
            p.audio_path = Path(file)
            self.on_project_change(p)

    def select_image(self):
        p = self.get_current()
        if not p:
            return
        file = filedialog.askopenfilename(filetypes=[("Image", "*.png *.jpg *.jpeg")])
        if file:
            p.background_image = Path(file)
            self.on_project_change(p)

    def update_current(self, _):
        p = self.get_current()
        if not p:
            return
        p.theme = self.theme.get()
        p.music_track = self.music.get()
        p.resolution = tuple(map(int, self.resolution.get().split("x")))
        for attr, sw in self.switches.items():
            setattr(p, attr, bool(sw.get()))
        self.on_project_change(p)

    def queue_project(self):
        p = self.get_current()
        if p:
            self.on_queue(p)
