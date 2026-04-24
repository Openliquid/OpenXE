#!/usr/bin/env bash
set -euo pipefail

check_cmd() {
  local cmd="$1"
  if command -v "$cmd" >/dev/null 2>&1; then
    echo "[OK] $cmd: $(command -v "$cmd")"
  else
    echo "[MISS] $cmd"
  fi
}

echo "== OpenXE RTS environment check =="
check_cmd bash
check_cmd git
check_cmd python3
check_cmd curl
check_cmd unzip
check_cmd godot4
check_cmd cmake
check_cmd clang
check_cmd dotnet

echo
if [[ -x "tools/bin/godot4" ]]; then
  echo "[OFFLINE] tools/bin/godot4 vorhanden"
else
  echo "[OFFLINE] tools/bin/godot4 fehlt"
fi

echo "Hint: run ./scripts/setup_container_rts.sh to attempt auto-setup."
echo "Hint: source scripts/use_local_tools.sh for offline fallback PATH."
