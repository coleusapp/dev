#!/usr/bin/env bash

set -e
set -x

CURRENT_BRANCH="1.x"

function split()
{
    SHA1=`./bin/splitsh-lite --prefix=$1`
    git push $2 "$SHA1:refs/heads/$CURRENT_BRANCH" -f
}

function remote()
{
    git remote add $1 $2 || true
}

git pull origin $CURRENT_BRANCH

remote support git@github.com:coleusapp/support.git
remote users git@github.com:coleusapp/users.git
remote table git@github.com:coleusapp/table.git
remote widget git@github.com:coleusapp/widget.git
remote health git@github.com:coleusapp/health.git

split 'src/Coleus/Support' support
split 'src/Coleus/Users' users
split 'src/Coleus/Table' table
split 'src/Coleus/Widget' widget
split 'src/Coleus/Health' health
