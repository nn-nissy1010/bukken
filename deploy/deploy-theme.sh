#!/usr/bin/env bash
#
# deploy-theme.sh — 共通テーマと本番用mu-plugin(SMTP)をサーバーへ同期する。
# 使い方:  deploy/deploy-theme.sh
#   事前に deploy/.env を用意（deploy/.env.example をコピー）。
#   rsync + ssh を使用。お名前.com で rsync 不可なら SFTP でも同じ2ディレクトリを上げればよい。
#
set -euo pipefail
cd "$(dirname "$0")/.."

if [ ! -f deploy/.env ]; then
	echo "deploy/.env がありません。deploy/.env.example をコピーして実値を入れてください。"; exit 1
fi
# shellcheck disable=SC1091
source deploy/.env
SSH_PORT="${SSH_PORT:-22}"

LOCAL_THEME="wp/themes/mrc-residents/"
REMOTE_THEME="${REMOTE_WP_PATH}/wp-content/themes/mrc-residents/"
REMOTE_MU="${REMOTE_WP_PATH}/wp-content/mu-plugins/"

echo "== リモートに mu-plugins ディレクトリを用意 =="
ssh -p "$SSH_PORT" "${SSH_USER}@${SSH_HOST}" "mkdir -p '${REMOTE_MU}' '${REMOTE_THEME}'"

echo "== テーマを同期（dev/ とローカル専用物は除外）=="
rsync -avz --delete \
	--exclude 'dev/' \
	--exclude '.DS_Store' \
	--exclude 'screenshot.png' \
	-e "ssh -p ${SSH_PORT}" \
	"$LOCAL_THEME" "${SSH_USER}@${SSH_HOST}:${REMOTE_THEME}"

echo "== 本番SMTP mu-plugin を配置 =="
rsync -avz -e "ssh -p ${SSH_PORT}" \
	deploy/mu-plugins/mrc-smtp.php \
	"${SSH_USER}@${SSH_HOST}:${REMOTE_MU}mrc-smtp.php"

echo "== 完了。テーマは全物件で即時反映されます。=="
