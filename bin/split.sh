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

remote apps git@github.com:coleusapp/apps.git
remote calendar git@github.com:coleusapp/calendar.git
remote health git@github.com:coleusapp/health.git
remote music git@github.com:coleusapp/music.git
remote notes git@github.com:coleusapp/notes.git
remote settings git@github.com:coleusapp/settings.git
remote support git@github.com:coleusapp/support.git
remote table git@github.com:coleusapp/table.git
remote users git@github.com:coleusapp/users.git
remote widgets git@github.com:coleusapp/widgets.git

split 'packages/apps' apps
split 'packages/calendar' calendar
split 'packages/health' health
split 'packages/music' music
split 'packages/notes' notes
split 'packages/settings' settings
split 'packages/support' support
split 'packages/table' table
split 'packages/users' users
split 'packages/widgets' widgets
