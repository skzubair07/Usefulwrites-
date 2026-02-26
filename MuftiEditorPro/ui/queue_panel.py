import customtkinter as ctk


class QueuePanel(ctk.CTkFrame):
    def __init__(self, master):
        super().__init__(master)
        self.box = ctk.CTkTextbox(self)
        self.box.pack(fill="both", expand=True, padx=8, pady=8)

    def update_items(self, items):
        self.box.delete("1.0", "end")
        for index, item in enumerate(items, start=1):
            self.box.insert(
                "end",
                f"{index}. {item.project.name} | {item.status} | {item.progress}% | ETA: {item.eta}\n",
            )
