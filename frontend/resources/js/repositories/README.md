# repositories/ -- Backward Compatibility Only

Per the project architecture chain (`View -> Store -> Service -> Axios`), data-access classes belong in `services/`, not `repositories/`.

This directory exists for backward compatibility. New code should place repository-style classes under `resources/js/services/` instead.

**Migration:** When refactoring, rename `repositories/` -> `services/` and update imports throughout the consuming modules.
