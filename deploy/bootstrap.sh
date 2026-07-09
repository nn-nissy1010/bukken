#!/usr/bin/env bash
#
# bootstrap.sh — 本番の初期セットアップ（SSH + WP-CLI が使える場合）。
#   マルチサイト設置後に一度だけ実行する想定。すべて冪等。
#   前提：wp-config.php にマルチサイト定数を入れ、.htaccess を差し替え、
#         「ネットワークの設置」まで済んでいること（deploy/README.md 参照）。
#
#   SSHやWP-CLIが使えないホスト（共用サーバー等）では、README の
#   「WP-CLIが無い場合」の手順を wp-admin から手動で行う。
#
set -euo pipefail
cd "$(dirname "$0")/.."

if [ ! -f deploy/.env ]; then
	echo "deploy/.env がありません。"; exit 1
fi
# shellcheck disable=SC1091
source deploy/.env
SSH_PORT="${SSH_PORT:-22}"
WP="${WP_CLI:-wp}"

# サーバー上で WP-CLI を実行するヘルパー
run() { ssh -p "$SSH_PORT" "${SSH_USER}@${SSH_HOST}" "cd '${REMOTE_WP_PATH}' && ${WP} $*"; }

echo "== マルチサイト状態の確認 =="
if run core is-installed --network 2>/dev/null; then
	echo "  ネットワーク: OK"
else
	echo "  ネットワーク未設置。先に wp-config + ネットワークの設置を完了してください（README参照）。"; exit 1
fi

echo "== 共通テーマをネットワーク有効化 =="
run theme enable mrc-residents --network --activate

echo "== NS Cloner を導入・ネットワーク有効化 =="
run plugin is-installed ns-cloner-site-copier 2>/dev/null || run plugin install ns-cloner-site-copier
run plugin activate ns-cloner-site-copier --network

echo "== 雛形サイトに必須固定ページを作成（本番安全版・デモ無し）=="
# create-pages.php をリモートのテーマ配下に一時アップして実行
scp -P "$SSH_PORT" deploy/create-pages.php \
	"${SSH_USER}@${SSH_HOST}:${REMOTE_WP_PATH}/wp-content/mrc-create-pages.php"
run eval-file wp-content/mrc-create-pages.php --url="${SITE_URL}"
ssh -p "$SSH_PORT" "${SSH_USER}@${SSH_HOST}" "rm -f '${REMOTE_WP_PATH}/wp-content/mrc-create-pages.php'"

echo "== メール疎通テスト（Mailは mu-plugin 経由でSMTPへ）=="
run eval 'var_dump( wp_mail( get_option("admin_email"), "MRC 本番メールテスト", "SMTP疎通確認" ) );' --url="${SITE_URL}" || true

echo "== 完了。次は各物件を NS Cloner で複製し、住民登録・物件設定を行う。=="
