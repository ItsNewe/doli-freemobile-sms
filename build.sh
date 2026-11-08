#!/bin/sh
# Build build/module_freemobilesms-<version>.zip, installable with Setup > Modules > Deploy external module.
# Packs the committed files of HEAD; files marked export-ignore in .gitattributes are left out.
set -e
cd "$(dirname "$0")"
VERSION=$(sed -n "s/.*\$this->version = '\([^']*\)'.*/\1/p" core/modules/modFreeMobileSms.class.php | head -n 1)
[ -n "$VERSION" ] || { echo "Version not found in core/modules/modFreeMobileSms.class.php" >&2; exit 1; }
OUT="build/module_freemobilesms-$VERSION.zip"
mkdir -p build
rm -f "$OUT"
git archive --format=zip --worktree-attributes --prefix=freemobilesms/ -o "$OUT" HEAD
echo "$OUT"
