from __future__ import annotations

import threading
from tkinter import messagebox

import customtkinter as ctk

from engine.errors import APIMissingError, FFmpegMissingError
from engine.folder_manager import ensure_directories
from engine.logger import DebugLogger
from engine.queue_manager import RenderQueueManager
from engine.render_engine import RenderEngine
from engine.transcription import TranscriptionEngine
from ui.debug_panel import DebugPanel
from ui.preview_panel import PreviewPanel
from ui.project_panel import ProjectPanel
from ui.queue_panel import QueuePanel


class MuftiEditorApp(ctk.CTk):
    def __init__(self):
        super().__init__()
        self.title("Mufti Editor Pro")
        self.geometry("1440x900")

        ensure_directories()
        self.logger = DebugLogger()
        self.transcriber = TranscriptionEngine(self.logger)

        self.grid_columnconfigure(1, weight=1)
        self.grid_rowconfigure(0, weight=1)
        self.grid_rowconfigure(1, weight=0)

        self.project_panel = ProjectPanel(self, self.on_project_change, self.preview_project, self.enqueue_project)
        self.project_panel.grid(row=0, column=0, sticky="nsew", padx=8, pady=8)

        self.preview_panel = PreviewPanel(self)
        self.preview_panel.grid(row=0, column=1, sticky="nsew", padx=8, pady=8)

        self.queue_panel = QueuePanel(self)
        self.queue_panel.grid(row=0, column=2, sticky="nsew", padx=8, pady=8)

        self.console = ctk.CTkTextbox(self, height=150)
        self.console.grid(row=1, column=0, columnspan=3, sticky="nsew", padx=8, pady=8)

        self.debug_window = DebugPanel(self)
        self.logger.subscribe(self.push_log)

        self.current_project = None
        self.renderer = None
        self.queue_manager = None
        self._init_render_system()

    def _init_render_system(self):
        try:
            self.renderer = RenderEngine(self.logger)
            self.queue_manager = RenderQueueManager(self.renderer, self.logger, self.refresh_queue)
        except FFmpegMissingError as exc:
            msg = (
                f"FFmpeg Missing: {exc}\n\n"
                "Install FFmpeg and add to PATH, then restart Mufti Editor Pro."
            )
            self.logger.log(msg)
            messagebox.showerror("FFmpeg Missing", msg)

    def push_log(self, message: str):
        self.console.insert("end", message + "\n")
        self.console.see("end")
        self.debug_window.push(message)

    def on_project_change(self, project):
        self.current_project = project
        self.logger.log(f"Project selected: {project.name}")

    def preview_project(self):
        if not self.current_project:
            return
        self.preview_panel.refresh(self.current_project)
        self.logger.log("Preview refreshed")

    def enqueue_project(self, project):
        if not self.queue_manager:
            self.logger.log("Render system unavailable due to FFmpeg setup issue")
            return
        if not project.audio_path:
            self.logger.log("Missing audio file")
            return

        def work():
            try:
                project.subtitles = self.transcriber.transcribe(project.audio_path)
            except APIMissingError as exc:
                self.logger.log(f"API Missing: {exc}")
                return
            except Exception as exc:
                self.logger.log(f"Transcription error: {exc}")
                return
            self.queue_manager.add(project)
            self.logger.log(f"Added to queue: {project.name}")

        threading.Thread(target=work, daemon=True).start()

    def refresh_queue(self):
        if self.queue_manager:
            self.after(0, lambda: self.queue_panel.update_items(self.queue_manager.items))
