import customtkinter as ctk

from ui.app import MuftiEditorApp


if __name__ == "__main__":
    ctk.set_appearance_mode("dark")
    ctk.set_default_color_theme("dark-blue")
    app = MuftiEditorApp()
    app.mainloop()
