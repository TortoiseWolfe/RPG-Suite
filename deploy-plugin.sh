#!/bin/bash
# Build the WordPress.org package: dist/rpg-suite-<version>.zip
# Needs the React build first (run ./build.sh).
set -euo pipefail
cd "$(dirname "$0")"

VERSION=$(grep -m1 '^ \* Version:' rpg-suite.php | sed 's/.*Version:[[:space:]]*//' | tr -d '\r')
if [ ! -f react-app/build/index.js ]; then
    echo "react-app/build/index.js is missing. Run ./build.sh first." >&2
    exit 1
fi

ROOT=$(pwd)
STAGE=$(mktemp -d)
trap 'rm -rf "$STAGE"' EXIT
PKG="$STAGE/rpg-suite"
mkdir -p "$PKG/react-app" dist

cp rpg-suite.php readme.txt "$PKG/"
cp -r includes assets "$PKG/"
# Compiled build plus the readable source (WordPress.org guideline 4).
cp -r react-app/build react-app/src react-app/package.json "$PKG/react-app/"

rm -f "dist/rpg-suite-$VERSION.zip"
if command -v zip >/dev/null 2>&1; then
    (cd "$STAGE" && zip -qr "$ROOT/dist/rpg-suite-$VERSION.zip" rpg-suite)
else
    (cd "$STAGE" && python3 -c "import shutil,sys; shutil.make_archive(sys.argv[1], 'zip', '.', 'rpg-suite')" "$ROOT/dist/rpg-suite-$VERSION")
fi
echo "Built dist/rpg-suite-$VERSION.zip"
