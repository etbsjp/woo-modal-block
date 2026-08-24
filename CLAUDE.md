## レビュー工程に大（シニアエンジニア）を追加する

このリポジトリでは、安藤（`vk-code-reviewer`）のレビューのあと、**PR を作成する前に**
大（`etbs-senior-wp`）の監査を必ず通すこと。大は etbs の申し送りと過去に踏んだ罠に照らして
「リリースできる形になっているか」を見る担当で、安藤の一般的なコード品質レビューとは層が違う。

- `Agent` ツールで `subagent_type: etbs-senior-wp`、`name: etbs-senior-wp`、
  **`run_in_background: false`** で起動する
  （★ 作業ディレクトリが git リポジトリなら `isolation: "worktree"` を付ける。付けないと
  起動応答は「成功」と返るのに一度も作業せず待機状態に入ることがある。
  ★★ **git リポジトリでない場所からは `isolation: "worktree"` を使えない**ので、
  そのときは付けずに起動する。以前ここに「必須」と書いていたのは誤り）
- prompt には対象リポジトリ・ブランチ・差分（または PR 番号）を渡す
- 大には **出力の末尾に `監査結果: PASS` または `監査結果: FAIL` を必ず書くよう指示する**
  （★ 大の定義ファイルには出力形式の指定が無いため、指示しないと合否を機械判定できない）
- `監査結果: PASS` を受け取るまで PR を作成しない。`FAIL` なら和田へ差し戻して再監査する

## アンインストール

★ `uninstall.php` の方針は**案A**（task-queue #108）。判定は3分類。

| 利用者が作ったコンテンツ（投稿・投稿メタ） | 利用者が設定した値（オプション） | 一時状態・自分が仕掛けた cron |
|---|---|---|
| **消さない** | **消さない** | **消す** |

理由は害の非対称性。消さないことの害は「DB に少量のレコードが残る」だけだが、消すことの害は
復旧不可能。迷ったら残す側に倒す。

このプラグインでの当てはめ:

- **残す** … 該当なし（永続状態を一切持たない）
- **消す** … 該当なし（独自テーブルも cron も持たない）

★ `uninstall.php` に `//delete_option('woomodalblock');` の残骸コメントがあったが、この名前の
オプションはどこからも保存していない。「消し忘れがある」と読まれるため削除した。

★ 配布8本すべてがこの3分類で説明できる状態にしてある。テーブルと cron を持つのは editlock だけ、
一時状態のオプションを持つのは pageguard だけで、そこだけが「消す」に該当する。
**他のプラグインで「何も消していない」のは判断の結果であって書き忘れではない。**
横並びで「消す」側へ揃えにこないこと。

## 版数は3箇所ある

このプラグインの版数は次の**3箇所**にあり、1つでも取り残すと事故になる。

- `woo-modal-block.php` の `Version:` ヘッダ
- `woo-modal-block.php` の `$woomb_version`
- `readme.txt` の `Stable tag`

★ `$woomb_version` は `wp_enqueue_script()` のキャッシュバスターと、
`block_type_metadata` 経由のブロックアセットの version を兼ねている。
**ヘッダだけ上げると利用者のブラウザに旧 JS / 旧 CSS が残る。**

版数を上げたら、必ずこれで3箇所が揃っているか確認する。

```sh
grep -nE "^ \* Version:|woomb_version = |^Stable tag:" woo-modal-block.php readme.txt
```

★ readme.txt の `== Changelog ==` には、**必ず `= x.y.z =` の見出しを付けてから**項目を書く。
見出しの無い項目を直下に置くと、どの版の変更か分からなくなる（実際に一度そうなった）。

## 宣言（Requires）の方針

★★★ **`Requires at least: 6.6` は実測下限。下げてはいけない。**
8本のうち `Requires at least` を宣言しているのはこのプラグインだけ。

```
build/woo-modal-block/index.asset.php
→ array('dependencies' => array('react-jsx-runtime', 'wp-block-editor', 'wp-blocks', 'wp-i18n'), ...)
```

`react-jsx-runtime` は **WP 6.6 で追加されたスクリプトハンドル**。6.3〜6.5 では未登録なので、
依存が解決できずエディタ用スクリプトが出力されず、**ブロックが編集画面に出ない**。
エラーは出ないので、下げた側からは原因が見えない。

★★★ **`apiVersion` だけで判断しない。** `block.json` の `apiVersion: 3` は WP 6.3 以降を意味するため、
そこだけ見ると「6.3 まで下げられる」と読める（task-queue #111 の起票時に実際そう書かれた）。
**ブロックがあるなら `build/*/index.asset.php` の `dependencies` を全数見る**
（正本 `~/.claude/etbs-plugin-rules.md` の「★★★ `Requires at least` も横並びで決めない」）。

★ WP 6.8 の `wp_register_block_types_from_metadata_collection()` と 6.7 の
`wp_register_block_metadata_collection()` は `function_exists()` で分岐し、最後は
`register_block_type()` に落ちる（`woo-modal-block.php:44-58`）。**これらは下限を押し上げない。**

★ `readme.txt` に `Requires at least` は書かない（現状も無い）。PUC は readme 側の値でヘッダを
上書きするため、二重に持つと食い違う。

★ `Requires PHP: 7.4` は据え置き。

★★ **「据え置き」と「新規に足す」は別問題**（2026-08-25 / task-queue #111 で再確認）。
既に宣言している版を据え置いても新たに締め出す個体は生まれないが、**無宣言のプラグインに
`Requires PHP` を新しく足すと、いま更新が届いている個体を以後届かなくする**。
`woo-checkout-colorbox` と `widget-shortcode-tools` が無宣言なのは、この理由による意図的な判断。
**8本で揃えにこないこと。**
