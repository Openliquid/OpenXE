#!/usr/bin/env bash
set -euo pipefail

# Load local/offline tools from repo without network installs.
# Usage:
#   source scripts/use_local_tools.sh

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TOOLS_DIR="${ROOT_DIR}/tools"
BIN_DIR="${TOOLS_DIR}/bin"

if [[ -d "$BIN_DIR" ]]; then
  export PATH="$BIN_DIR:$PATH"
fi

# Common optional folders for manually extracted tools
if [[ -d "${TOOLS_DIR}/godot" ]]; then
  export PATH="${TOOLS_DIR}/godot:$PATH"
fi

if [[ -f "${TOOLS_DIR}/tools.env" ]]; then
  # shellcheck disable=SC1090
  source "${TOOLS_DIR}/tools.env"
fi

echo "Local tools loaded. PATH prefixed with:"
echo "  - ${BIN_DIR}"
echo "  - ${TOOLS_DIR}/godot (if present)"
