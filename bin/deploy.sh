#!/usr/bin/env bash
#
# Usage: bin/deploy.sh [patch|minor|major|<explicit-version>]

set -e
set -x

RELEASE_BRANCH="1.x"
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
BUMP="${1:-patch}"

PACKAGES="support users table widgets health music settings"

if [[ "$RELEASE_BRANCH" != "$CURRENT_BRANCH" ]]; then
    echo "Release branch ($RELEASE_BRANCH) does not match the current active branch ($CURRENT_BRANCH)."
    exit 1
fi

if [[ -n "$(git status --porcelain)" ]]; then
    echo "Your working directory is dirty. Did you forget to commit your changes?"
    exit 1
fi

git fetch origin

if [[ "$(git rev-parse HEAD)" != "$(git rev-parse "origin/$RELEASE_BRANCH")" ]]; then
    echo "Your branch is out of date with its upstream. Did you forget to pull or push any changes before releasing?"
    exit 1
fi

LATEST_TAG=$(git tag --list 'v*' --sort=-v:refname | head -n1)
LATEST_TAG=${LATEST_TAG:-v0.0.0}

case "$BUMP" in
    patch|minor|major)
        VERSION="v$(./bin/semver bump "$BUMP" "$LATEST_TAG")"
        ;;
    *)
        if [[ "$(./bin/semver validate "$BUMP")" != "valid" ]]; then
            echo "Unrecognized version argument: $BUMP (expected patch, minor, major, or an explicit x.y.z version)"
            exit 1
        fi
        VERSION="$BUMP"
        [[ "$VERSION" != v* ]] && VERSION="v$VERSION"
        ;;
esac

echo "Releasing $VERSION"

git tag "$VERSION"
git push origin --tags

for PACKAGE in $PACKAGES; do
    REMOTE_URL="git@github.com:coleusapp/$PACKAGE.git"
    SHA1=$(./bin/splitsh-lite --prefix="packages/$PACKAGE")

    git push "$REMOTE_URL" "$SHA1:refs/heads/$RELEASE_BRANCH" -f
    git push "$REMOTE_URL" "$SHA1:refs/tags/$VERSION"
done

echo "Released $VERSION"
