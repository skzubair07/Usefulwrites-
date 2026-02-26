import customtkinter as ctk


class DebugPanel(ctk.CTkToplevel):
    def __init__(self, master):
        super().__init__(master)
        self.title("Mufti Editor Pro - Debug Window")
        self.geometry("900x300")
        self.text = ctk.CTkTextbox(self)
        self.text.pack(fill="both", expand=True, padx=10, pady=10)

    def push(self, message: str):
        self.text.insert("end", message + "\n")
        self.text.see("end")
