import customtkinter as ctk
from PIL import ImageTk

from engine.preview_engine import PreviewEngine


class PreviewPanel(ctk.CTkFrame):
    def __init__(self, master):
        super().__init__(master)
        self.engine = PreviewEngine()
        self.label = ctk.CTkLabel(self, text="Preview")
        self.label.pack(fill="both", expand=True, padx=8, pady=8)
        self.image_ref = None

    def refresh(self, project):
        subtitle = project.subtitles[0].text if project.subtitles else "Subtitle Preview"
        image = self.engine.render_preview(project.background_image, subtitle, project.theme)
        self.image_ref = ImageTk.PhotoImage(image)
        self.label.configure(image=self.image_ref, text="")
