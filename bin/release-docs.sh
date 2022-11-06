#!/bin/bash

set -x

if uname | grep Darwin >> /dev/null; then
    SPLITSH=bin/splitsh-lite
else
    SPLITSH=bin/splitsh-lite-linux
fi

git remote add repo-docs git@github.com:kuiper-framework/kuiper-framework.github.io.git 2> /dev/null || true

CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)

SHA1=`$SPLITSH --prefix=docs`
git push repo-docs "$SHA1:refs/heads/$CURRENT_BRANCH" -f
