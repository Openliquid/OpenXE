# Offline Tools Fallback

Wenn der Container keine externen Downloads zulässt (z. B. Proxy 403), kannst du Tools lokal in dieses Verzeichnis legen.

## Schnellstart

1. Lege Binaries oder Symlinks in `tools/bin/` ab.
2. Optional: entpacke Godot nach `tools/godot/`.
3. Optional: nutze `tools/tools.env` für zusätzliche Pfade.
4. Lade die Umgebung:

```bash
source scripts/use_local_tools.sh
```

## Erwartete Struktur

- `tools/bin/godot4` (oder Symlink)
- `tools/godot/` (optional)
- `tools/tools.env` (optional)

Beispiel `tools/tools.env`:

```bash
export PATH="/custom/path/to/tools:$PATH"
```
