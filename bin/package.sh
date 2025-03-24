#!/usr/bin/env bash

cd ../../
if [ -d "../../../../tiaa-dev/tiaa-backup/" ] && [ -w "../../../../tiaa-dev/tiaa-backup/" ] ; then
  TIAA_BACKUP="../../../../tiaa-dev/tiaa-backup"
else
  echo "no target directory"
  exit 1
fi

current_date=$(date "+%y%m%d%H%M")

zip  -r /tmp/tiaa-wpplugin.zip tiaa-wpplugin -x "*/bin/*" "*/.git/*" "*/.DS_Store*"

cp /tmp/tiaa-wpplugin.zip "${TIAA_BACKUP}/${current_date}-tiaa-wpplugin.zip"

cd ${TIAA_BACKUP} || exit
TIAA_BACKUP_DIR=$(pwd)
echo "saved in ${TIAA_BACKUP_DIR}/${current_date}-tiaa-wpplugin.zip"