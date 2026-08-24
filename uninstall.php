<?php
/**
 * アンインストール処理。
 *
 * Woo Modal Block は永続状態を一切持たない（オプション・投稿メタ・独自テーブル・cron の
 * いずれも作らない）。したがって削除時に後始末すべきものが無く、「何もしない」が正しい実装になる
 * （task-queue #108 の案A決定）。
 *
 * ★ 以前は `//delete_option('woomodalblock');` という残骸コメントが置かれていたが、
 * この名前のオプションはどこからも保存していない。コメントを残すと「消し忘れがある」と
 * 読まれるため削除した。
 *
 * @package woo-modal-block
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// 意図的に何もしない。
