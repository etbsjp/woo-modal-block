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
