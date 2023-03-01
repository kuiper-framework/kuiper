#!/usr/bin/env bash
set -e

NOW=$(date +%s)
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
VERSION=$(git tag --points-at HEAD --sort -version:refname | head -1)
if [[ $VERSION = "" ]]; then
    echo "Current HEAD does not tag. Use git tag version"
    exit -1
fi

# Always prepend with "v"
if [[ $VERSION != v*  ]]
then
    VERSION="v$VERSION"
fi

if [ "$1" = "" ]; then
    REPOS="cache db di event helper http-client jsonrpc logger reflection resilience rpc rpc-registry serializer swoole tars web"
else
    REPOS="$@"
fi

TMP_DIR=`realpath $(dirname $0)"/../../kuiper-split"`
if ! [ -d $TMP_DIR ]; then
    mkdir -p $TMP_DIR
fi

for REMOTE in $REPOS
do
    echo "Cloning $REMOTE";
    REMOTE_URL="git@github.com:kuiper-framework/$REMOTE.git"

    (
        cd $TMP_DIR;
        if [ -d $REMOTE ]; then
            cd $REMOTE
            git remote set-url origin "$REMOTE_URL"
            git fetch
        else
            git clone $REMOTE_URL 
            cd $REMOTE
        fi

        git reset --hard "origin/$CURRENT_BRANCH";

        if [[ $(git log --pretty="%d" -n 1 | grep tag --count) -eq 0 ]]; then
            echo "Releasing $REMOTE"
            if git rev-parse -q --verify "refs/tags/$VERSION" > /dev/null; then
                git tag -d $VERSION
                git push origin :refs/tags/$VERSION
            fi
            git tag $VERSION
            git push origin --tags
        fi
    )
done

TIME=$(echo "$(date +%s) - $NOW" | bc)

printf "Execution time: %f seconds" $TIME
