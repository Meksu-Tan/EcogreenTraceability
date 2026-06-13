# services/ -- Data Access Layer

Per the project architecture chain (`View -> Store -> Service -> Axios`), this directory holds repository-style data-access classes.

Classes here follow the Repository pattern used throughout the codebase: they wrap API calls from `modules/*/services/` and normalize responses.

**Existing repositories** live at `resources/js/repositories/` (backward compat). New data-access classes should be placed here in `services/`.
