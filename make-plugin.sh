#!/usr/bin/env bash
VERSION=`cat plugins/characters-client-preview.json | jq -r '.version'`
NAME=myaac-characters-client-preview-v$VERSION.zip
rm -f $NAME
zip -r $NAME plugins/ -x */\.*
