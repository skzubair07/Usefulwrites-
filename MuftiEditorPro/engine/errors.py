class MuftiEditorError(Exception):
    """Base app error."""


class FFmpegMissingError(MuftiEditorError):
    pass


class APIMissingError(MuftiEditorError):
    pass


class MusicMissingError(MuftiEditorError):
    pass


class FootageMissingError(MuftiEditorError):
    pass


class PathValidationError(MuftiEditorError):
    pass


class RenderFailureError(MuftiEditorError):
    pass
