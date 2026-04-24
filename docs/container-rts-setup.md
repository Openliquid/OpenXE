# Container RTS Setup

Dieses Repo enthält Setup-Helfer für Container-Readiness:

- `scripts/setup_container_rts.sh`  
  Best-Effort Setup (Godot Download) + **Offline-Fallback aus `tools/`**.
- `scripts/rts_env_check.sh`  
  Prüft verfügbare Tools (`godot4`, `clang`, `cmake`, usw.).
- `scripts/use_local_tools.sh`  
  Lädt lokale/offline Tools in den `PATH` (`source` verwenden).

## Online Setup

```bash
./scripts/setup_container_rts.sh
./scripts/rts_env_check.sh
```

## Offline Fallback (wenn Downloads blockiert sind)

1. Lege lokale Binaries in `tools/bin/` (z. B. `tools/bin/godot4`).
2. Optional: entpacke Godot nach `tools/godot/`.
3. Lade lokale Tools:

```bash
source scripts/use_local_tools.sh
./scripts/rts_env_check.sh
```

## Wichtiger Hinweis

In dieser Container-Umgebung können externe Paket-/Download-Quellen per Proxy blockiert sein (HTTP 403).
Die Skripte sind daher **graceful** implementiert (Warnung statt Hard-Fail) und unterstützen jetzt explizit Offline-Fallback.

## Erwartetes Ergebnis

- Online verfügbar: `godot4 --version` über Auto-Install.
- Online blockiert: Setup-Warnung + nutzbarer Offline-Pfad via `tools/`.
