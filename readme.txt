=== Woo Modal Block ===
Contributors:      DAI
Tags:              woocommerce, block, modal, cart
Tested up to:      6.7
Stable tag:        1.0.2
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

モーダルウィンドウから商品をカートへ追加できる WooCommerce 用ブロックです。

== Description ==

投稿や固定ページにボタンと一緒に配置すると、モーダルウィンドウから商品をカートへ追加できます（ブロックエディタ用のブロックです）。バリエーション違いの商品をまとめて選んでもらう用途に向いています。

= 使い方 =

* ブロックエディタで「Woo Modal Block」ブロックを追加します。
* ブロックの直前に「ボタン」ブロックを配置し、追加CSSクラスに `eb_btn` を指定してください（モーダルを開くトリガーになります）。
* ブロック設定パネルでタイトル・商品名・バリエーションのラベルと値を入力します。バリエーションリスト1は必須、リスト2は任意です（トグルで有効化）。

= 制限事項 =

* 1つのモーダルで扱える商品は20種類までです。超えた場合、商品検索・カート追加とも先頭20件のみが処理されます（画面にエラーは表示されません）。

== Installation ==

1. プラグインファイルを `/wp-content/plugins/woo-modal-block` ディレクトリへアップロードするか、WordPress のプラグイン画面からインストールしてください。
2. 「プラグイン」画面から本プラグインを有効化してください。
3. ブロックエディタで「Woo Modal Block」ブロックを追加し、「使い方」に従って設定してください。

== Frequently Asked Questions ==

= モーダルが開きません =

ブロックの直前に配置した「ボタン」ブロックの追加CSSクラスに `eb_btn` が指定されているかご確認ください。

== Changelog ==

* [ New Feature ] Add a dashboard widget with usage notes and support links to the plugins list row
* [ Security Fix ] Restrict the unauthenticated product search AJAX endpoint to published products only, and add limits to the search and add-to-cart request parameters
* [ Bug Fix ] Deliver a required script as a static file instead of a PHP file executed inside the plugin directory, avoiding failures on hosts that block direct PHP execution there

= 0.1.0 =
* Release
