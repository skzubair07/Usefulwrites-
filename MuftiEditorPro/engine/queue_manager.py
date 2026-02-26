from __future__ import annotations

import queue
import threading
import time
from dataclasses import dataclass

from engine.errors import (
    APIMissingError,
    FFmpegMissingError,
    FootageMissingError,
    MusicMissingError,
    PathValidationError,
    RenderFailureError,
)


@dataclass
class QueueItem:
    project: object
    status: str = "Pending"
    progress: int = 0
    eta: str = "--"


class RenderQueueManager:
    def __init__(self, render_engine, logger, on_update):
        self.render_engine = render_engine
        self.logger = logger
        self.on_update = on_update
        self.items = []
        self._queue: queue.Queue[QueueItem] = queue.Queue()
        self.worker = threading.Thread(target=self._loop, daemon=True)
        self.worker.start()

    def add(self, project):
        item = QueueItem(project=project)
        self.items.append(item)
        self._queue.put(item)
        self.on_update()

    def _loop(self):
        while True:
            item = self._queue.get()
            start = time.time()
            item.status = "Rendering"
            self.on_update()
            try:
                self.render_engine.render_project(item.project, lambda p: self._progress(item, p, start))
                item.progress = 100
                item.status = "Done"
            except FFmpegMissingError as exc:
                item.status = f"FFmpeg Missing: {exc}"
                self.logger.log(item.status)
            except MusicMissingError as exc:
                item.status = f"Music Missing: {exc}"
                self.logger.log(item.status)
            except FootageMissingError as exc:
                item.status = f"Footage Missing: {exc}"
                self.logger.log(item.status)
            except APIMissingError as exc:
                item.status = f"API Missing: {exc}"
                self.logger.log(item.status)
            except PathValidationError as exc:
                item.status = f"Path Error: {exc}"
                self.logger.log(item.status)
            except RenderFailureError as exc:
                item.status = f"Render Failure: {exc}"
                self.logger.log(item.status)
            except Exception as exc:
                item.status = f"Unexpected Error: {exc}"
                self.logger.log(item.status)
            self.on_update()
            self._queue.task_done()

    def _progress(self, item, progress, start):
        item.progress = progress
        elapsed = time.time() - start
        if progress > 0:
            total = elapsed / (progress / 100)
            remaining = max(0, total - elapsed)
            item.eta = f"{int(remaining)}s"
        self.on_update()
