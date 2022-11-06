#!/usr/bin/env bash

# set -e
set -x

CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
BASEPATH=$(cd `dirname $0`; cd ..; pwd)
if uname | grep Darwin >> /dev/null; then
    SPLITSH=bin/splitsh-lite
else
    SPLITSH=bin/splitsh-lite-linux
fi
    

if [ "$1" = "" ]; then
    REPOS="annotations cache db di event helper http-client jsonrpc logger reflection resilience rpc rpc-registry serializer swoole tars web"
else
    REPOS="$@"
fi

function split()
{
    SHA1=`$SPLITSH --prefix=$1`
    git push $2 "$SHA1:refs/heads/$CURRENT_BRANCH" -f
}

function remote()
{
    git remote add $1 $2 2> /dev/null || true
}

# git pull origin $CURRENT_BRANCH

for REPO in $REPOS ; do
    remote repo-$REPO git@github.com:kuiper-framework/$REPO.git

    split "$REPO" repo-$REPO
done
