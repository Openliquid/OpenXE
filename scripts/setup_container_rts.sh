#!/usr/bin/env bash
set -euo pipefail

# Best-effort setup for this container.
# Works only when outbound network/proxy rules allow downloads.
# Includes offline fallback support via ./tools.

GODOT_VERSION="4.2.2-stable"
GODOT_ZIP="Godot_v${GODOT_VERSION}_linux.x86_64.zip"
GODOT_URL="https://github.com/godotengine/godot/releases/download/${GODOT_VERSION}/${GODOT_ZIP}"
INSTALL_DIR="/opt/godot"
BIN_PATH="/usr/local/bin/godot4"
REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"

LOCAL_BIN="${REPO_ROOT}/tools/bin/godot4"
LOCAL_GODOT_DIR="${REPO_ROOT}/tools/godot"

echo "== OpenXE RTS setup (best effort) =="

mkdir -p "$INSTALL_DIR"

if command -v godot4 >/dev/null 2>&1; then
  echo "godot4 already installed: $(godot4 --version || true)"
else
  echo "Downloading Godot from: $GODOT_URL"
  if curl -fL "$GODOT_URL" -o "$INSTALL_DIR/godot.zip"; then
    unzip -o "$INSTALL_DIR/godot.zip" -d "$INSTALL_DIR"
    ln -sf "$INSTALL_DIR/Godot_v${GODOT_VERSION}_linux.x86_64" "$BIN_PATH"
    chmod +x "$BIN_PATH"
    echo "Installed godot4 -> $BIN_PATH"
    godot4 --version || true
  else
    echo "[WARN] Godot download failed (likely network/proxy restriction)."
    echo "       Trying offline fallback from repository tools/ ..."

    if [[ -x "$LOCAL_BIN" ]]; then
      ln -sf "$LOCAL_BIN" "$BIN_PATH"
      echo "Linked offline fallback: $LOCAL_BIN -> $BIN_PATH"
      godot4 --version || true
    elif [[ -d "$LOCAL_GODOT_DIR" ]]; then
      CANDIDATE="$(find "$LOCAL_GODOT_DIR" -maxdepth 2 -type f -name 'Godot*linux*x86_64*' | head -n 1 || true)"
      if [[ -n "$CANDIDATE" ]]; then
        chmod +x "$CANDIDATE"
        ln -sf "$CANDIDATE" "$BIN_PATH"
        echo "Linked offline fallback: $CANDIDATE -> $BIN_PATH"
        godot4 --version || true
      else
        echo "[WARN] No executable Godot binary found in tools/godot/."
      fi
    else
      echo "[WARN] No offline tools found."
      echo "       Put godot binary into tools/bin/godot4 and rerun setup."
    fi
  fi
fi

echo
echo "Running environment check..."
"$(dirname "$0")/rts_env_check.sh"
