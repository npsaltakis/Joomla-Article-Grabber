#!/usr/bin/env bash
#
# Build the installable Joomla packages.
#
# Usage: ./build.sh <version>
#
# Produces in dist/:
#   com_content_api_grabber-<version>.zip   (component, standalone)
#   plg_content_apigrabber-<version>.zip    (source companion plugin, standalone)
#   pkg_articlegrabber-<version>.zip        (package: installs both at once)
#
set -euo pipefail

VERSION="${1:?usage: build.sh <version>}"
ROOT="$(cd "$(dirname "$0")" && pwd)"
DIST="$ROOT/dist"
WORK="$DIST/.work"

rm -rf "$DIST"
mkdir -p "$WORK/packages"

# --- 1) Component: manifest at root + admin/ tree ---
COMP="$WORK/com_content_api_grabber"
mkdir -p "$COMP"
cp "$ROOT/content_api_grabber.xml" "$COMP/"
cp -r "$ROOT/admin" "$COMP/"
( cd "$COMP" && zip -rq -X "$DIST/com_content_api_grabber-$VERSION.zip" . )

# --- 2) Plugin (source companion) ---
( cd "$ROOT/plg_content_apigrabber" && zip -rq -X "$DIST/plg_content_apigrabber-$VERSION.zip" . )

# --- 3) Package: manifest + the two extension zips under packages/ ---
cp "$DIST/com_content_api_grabber-$VERSION.zip" "$WORK/packages/com_content_api_grabber.zip"
cp "$DIST/plg_content_apigrabber-$VERSION.zip"  "$WORK/packages/plg_content_apigrabber.zip"
cp "$ROOT/pkg_articlegrabber.xml" "$WORK/"
( cd "$WORK" && zip -rq -X "$DIST/pkg_articlegrabber-$VERSION.zip" pkg_articlegrabber.xml packages )

rm -rf "$WORK"

echo "Built:"
ls -1 "$DIST"
