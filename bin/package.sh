#!/usr/bin/env bash

export PACKAGE_NAME="tiaa-wpplugin"
# Verify we're running from inside ${PACKAGE_NAME}/bin/
if [[ "$(basename "$PWD")" != "bin" || "$(basename "$(dirname "$PWD")")" != "${PACKAGE_NAME}" ]]; then
  echo "error: must be run from ${PACKAGE_NAME}/bin/ as ./package.sh"
  exit 1
fi

export TIAA_BACKUP="${HOME}/tmp/tiaa-backup/"
if [ ! -d "${TIAA_BACKUP}" ] || [ ! -w "${TIAA_BACKUP}"  ] ; then
  echo "no target directory: ${TIAA_BACKUP}"
  exit 1
fi

cd ../../
current_date=$(date "+%y%m%d%H%M")
rm -f /tmp/${PACKAGE_NAME}.zip
zip  -r /tmp/${PACKAGE_NAME}.zip ${PACKAGE_NAME} \
      -x "*/bin/*" -x "*/.git/*" -x "*/.DS_Store*" -x "*/.idea/*" -x "*/.gitignore" -x "*.iml" \
      -x "*/CLAUDE.md" -x "*/.claude/*"

cp /tmp/${PACKAGE_NAME}.zip "${TIAA_BACKUP}/${current_date}-${PACKAGE_NAME}.zip"

cd ${TIAA_BACKUP} || exit 2
TIAA_BACKUP_DIR=$(pwd)
echo "saved in ${TIAA_BACKUP_DIR}/${current_date}-tiaa-wpplugin.zip"