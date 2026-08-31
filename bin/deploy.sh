#!/usr/bin/env bash
#
# Usage: bin/deploy.sh [patch|minor|major|<explicit-version>]
#
# Defaults to a patch bump.

set -e

VERSION="${1:-patch}"

LATEST_TAG=$(git tag --list 'v*' --sort=-v:refname | head -n1)

if [[ "$VERSION" = 'patch' ]]; then
  VERSION="v$(./bin/semver bump "$VERSION" "$LATEST_TAG")"
fi

if [[ "$(./bin/semver validate "$VERSION")" != "valid" ]]; then
  echo "Unrecognized version argument)"
  exit 1
fi

pnpm -r build

if [[ -n "$(git status --porcelain -- 'packages/*/resources/dist/**')" ]]; then
  git add packages/*/resources/dist
  git commit -m "Compile assets"
fi

./bin/split.sh
./bin/release.sh "$VERSION"
