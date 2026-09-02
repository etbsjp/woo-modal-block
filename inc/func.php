<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/*-------------------------------------------*/
/* JS/CSS 追加
/*-------------------------------------------*/
if ( ! function_exists( 'etbs_woo_modal_block_scripts' ) ){
	/**
	 * フロント表示に必要な JS を読み込む。
	 * Enqueue the JS assets required on the front end.
	 *
	 * @return void
	 */
	function etbs_woo_modal_block_scripts() {
		global $woomb_version;
		// ハンドル名は woomb_ 接頭辞を付ける。`overlay` のような汎用名は他プラグイン・テーマと
		// 衝突し、先に登録した側が勝つため、こちらの JS が黙って読まれなくなる（エラーは出ない）。
		// かつ wp_localize_script() が他人のスクリプトにデータを付けてしまう。
		// Prefix the handles with woomb_. Generic names like `overlay` collide with other
		// plugins/themes; the first registration wins, so our script would silently not load
		// (with no error), and wp_localize_script() would attach data to someone else's script.
		wp_enqueue_script( 'woomb_cart', plugins_url( 'inc/js/cart.js', WOOMB_PLUGIN_FILE ), array( 'jquery' ), $woomb_version, true );
		wp_enqueue_script( 'woomb_aj', plugins_url( 'inc/js/aj.js', WOOMB_PLUGIN_FILE ), array( 'jquery' ), $woomb_version, true );

		// カート追加を投げる先の URL を PHP 側で解決して渡す。
		// カート追加は template_redirect で横取りするため URL 自体はどこでもよいが、実在しない
		// パス（従来は /store/ 固定）に投げると、404 をページキャッシュしているサーバでは
		// リクエストが PHP に届かず、カート追加が黙って失敗する。
		// Resolve the add-to-cart target URL on the PHP side. The request is intercepted at
		// template_redirect so any URL works, but posting to a path that does not exist
		// (previously a hard-coded /store/) fails silently on servers that page-cache 404s,
		// because the request never reaches PHP.
		$woomb_cart_base = '';
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$woomb_cart_base = wc_get_page_permalink( 'shop' );
		}
		// WooCommerce 8.9 の wc_get_page_permalink() は、ショップページが未設定・削除済みでも
		// 自身で get_home_url() に落とすため、ここが空になるのは WooCommerce 未有効のとき（＝上の
		// function_exists が false のとき）だけ。念のための最終フォールバックとして残している。
		// wc_get_page_permalink() already falls back to get_home_url() when the shop page is
		// missing, so this is only reached when WooCommerce is inactive. Kept as a last resort.
		if ( ! is_string( $woomb_cart_base ) || '' === $woomb_cart_base ) {
			$woomb_cart_base = home_url( '/' );
		}

		// aj.js 内で使う値を PHP からローカライズして渡す（aj.js は静的ファイルのため PHP 変数を直接埋め込めない）。
		// siteurl は、フルページキャッシュで古い HTML と新しい JS が組み合わさった場合の
		// フォールバック用に残してある。
		// Localize the values aj.js needs, since it is a static file and cannot embed PHP variables.
		// siteurl is kept as a fallback for when a full-page cache pairs old HTML with new JS.
		wp_localize_script( 'woomb_aj', 'woombAjData', array(
			'ajaxurl'  => admin_url( 'admin-ajax.php' ),
			'siteurl'  => site_url(),
			'cartbase' => $woomb_cart_base,
		) );
		wp_enqueue_script( 'woomb_overlay', plugins_url( 'inc/js/overlay.js', WOOMB_PLUGIN_FILE ), array( 'jquery' ), $woomb_version, true );
	}
	add_action( 'wp_enqueue_scripts', 'etbs_woo_modal_block_scripts' );
}

/*-------------------------------------------*/
/* ブロックCSS/JSのキャッシュバスティング用バージョン差し替え
/*-------------------------------------------*/
if ( ! function_exists( 'etbs_woo_modal_block_override_block_version' ) ) {
	/**
	 * このプラグインが登録するブロックの version を、block.json の値ではなく
	 * プラグイン本体の版数（$woomb_version）に上書きする。
	 * block.json の version は雛形のまま `0.1.0` 固定になっており、これがそのまま
	 * style-index.css 等の `?ver=` に使われるため、CSS を更新しても
	 * 利用者のブラウザキャッシュが割れない。$metadata['name'] で対象ブロックに限定し、
	 * 他プラグイン・コアブロックの version には触れない。
	 *
	 * @param array $metadata block.json から読み込まれたブロックのメタデータ配列。
	 * @return array 対象ブロックのときだけ version を上書きしたメタデータ配列。
	 */
	function etbs_woo_modal_block_override_block_version( $metadata ) {
		global $woomb_version;
		// block.json の name（create-block/ 接頭辞込み）で自プラグインのブロックだけを対象にする。
		if ( isset( $metadata['name'] ) && 'create-block/woo-modal-block' === $metadata['name'] ) {
			$metadata['version'] = $woomb_version;
		}
		return $metadata;
	}
	add_filter( 'block_type_metadata', 'etbs_woo_modal_block_override_block_version' );
}

/*-------------------------------------------*/
/* WooのURLパラメーターをカスタマイズ
/*-------------------------------------------*/
if ( ! function_exists( 'etbs_woo_redirecct_url' ) ){
	/**
	 * add-to-cart / quantity パラメーターを解釈し、複数商品を一括でカートへ追加してからカートページへリダイレクトする。
	 * Parse the add-to-cart / quantity URL parameters, add multiple products to the cart at once, then redirect to the cart page.
	 *
	 * @return void
	 */
	function etbs_woo_redirecct_url() {
		// WooCommerce が無効なら何もしない。ヘッダの `Requires Plugins` は導入時の守りにすぎず、
		// WooCommerce が fatal で自動停止した場合などは実行時に到達する。ガードが無いと
		// 未認証の GET 一発で `Call to undefined function WC()` の致命エラー（HTTP 500）になる。
		// Bail out when WooCommerce is inactive. The `Requires Plugins` header only guards
		// installation; this path is still reachable if WooCommerce is deactivated at runtime.
		// Without this guard a single unauthenticated GET triggers a fatal error (HTTP 500).
		if ( ! function_exists( 'WC' ) || ! function_exists( 'wc_get_cart_url' ) ) {
			return;
		}

		if( isset($_GET['add-to-cart']) && isset($_GET['quantity']) ) {
			// URLパラメーターのため sanitize_text_field() で正規化してから利用する。
			// These come straight from the URL, so sanitize them before use.
			$add_to_cart = sanitize_text_field( wp_unslash( $_GET['add-to-cart'] ) );
			$quantity    = sanitize_text_field( wp_unslash( $_GET['quantity'] ) );

			if( strpos( $add_to_cart, '||' ) !== false && strpos( $quantity, '||' ) !== false ) {
				$items = explode( '||', $add_to_cart );
				$value = explode( '||', $quantity );
				// 件数の一致判定は上限キャップ前の元の件数で行う。
				// キャップ後の値で比較すると、例えば25件と20件のような本来不一致であるべき組み合わせが
				// 「どちらも20」に丸められて一致判定されてしまい、意図しないカート追加が起きるため。
				// Compare the item/quantity counts BEFORE capping them at the limit.
				// Comparing the capped values would make mismatched counts (e.g. 25 vs 20) both become
				// 20 and be treated as matching, causing unintended cart additions.
				$c1 = count( $items );
				$c2 = count( $value );

				if ( $c1 === $c2 ) {
					// 上限（20件）を超える要求は先頭20件のみを処理する（無制限ループ対策）。
					// Cap processing at the first 20 items when more are requested, to prevent an unbounded loop.
					$count = min( $c1, 20 );
					for ( $i = 0; $i < $count; $i++ ) {
						// quantity は整数化し、0以下のアイテムはカート追加をスキップする。
						// Normalize quantity to an integer and skip items whose quantity is 0 or less.
						$qty = absint( $value[ $i ] );
						if ( $qty <= 0 ) {
							continue;
						}
						WC()->cart->add_to_cart( $items[ $i ], $qty );
					}
				}
				wp_redirect( wc_get_cart_url() );
				exit;
			} else {
				return;
			}
		}
	}
	add_action( 'template_redirect', 'etbs_woo_redirecct_url' );
}

/*-------------------------------------------*/
/* Post_Title から商品ID(バリエーション)を探す
/*-------------------------------------------*/
if ( ! function_exists( 'etbs_woo_title_search' ) ){
	/**
	 * post_title の部分一致で商品ID（バリエーション含む）を検索する未認証AJAXハンドラー。
	 * Unauthenticated AJAX handler that looks up product IDs (including variations) by a partial post_title match.
	 *
	 * 未公開商品の情報が漏れないよう、公開済み（post_status = publish）の投稿のみを対象にする。
	 * バリエーションは自身に加えて親商品も公開済みであることを要求する。
	 * Only published posts (post_status = publish) are matched to avoid leaking unpublished product data.
	 * Variations additionally require their parent product to be published.
	 *
	 * @return void 検索結果を || 区切りの商品ID文字列として wp_die() で出力し、処理を終了する。
	 *              Outputs the matched product IDs (joined by "||") via wp_die() and terminates the request.
	 */
	function etbs_woo_title_search() {
		global $wpdb;

		// $_POST['mes'] が存在しない場合は何も処理せず終了する（PHP8 の undefined array key 警告対策）。
		// Bail out immediately when $_POST['mes'] is missing, to avoid a PHP8 "undefined array key" warning.
		if ( ! isset( $_POST['mes'] ) ) {
			wp_die( '' );
		}

		// unslash してからサニタイズし、以降はサニタイズ済みの値のみを使う。
		// Unslash then sanitize the raw input; only the sanitized value is used from here on.
		$mes = sanitize_text_field( wp_unslash( $_POST['mes'] ) );

		// 入力長の上限。件数の検査（count( $likes ) > 20）は explode() が配列を作り終えた
		// 後にしか走らないため、これが無いと未認証の1リクエストでメモリ確保量を攻撃者が
		// 決められる（実測: 8MB の POST で語数 4,194,305 個・128MB を確保）。
		// 8192 は正当な入力が絶対に届かない値にしてある。実データの商品名は最長98バイトで、
		// 20種類ぶんでも約2,000バイト。ここで弾くと位置を保つ分岐を通らないため、
		// 通常の超過は必ず count( $likes ) > 20 側が受け止める。
		// Cap the input length. The item-count check runs only after explode() has already
		// built the array, so without this an unauthenticated request controls how much
		// memory is allocated (measured: an 8MB POST yields 4,194,305 items and 128MB).
		// 8192 is far above any legitimate input (longest product title is 98 bytes; 20 of
		// them total ~2,000 bytes), so real overflows are still handled by the branch that
		// preserves slot positions.
		if ( strlen( $mes ) > 8192 ) {
			wp_die( '' );
		}

		$txt = explode( '||', $mes );

		// 想定外の形式（"||" 区切りの要素が3未満）は何も処理せず終了する（undefined array key 対策も兼ねる）。
		// Reject malformed input (fewer than 3 "||"-separated parts) without processing; this also avoids undefined array key warnings.
		if ( count( $txt ) < 3 ) {
			wp_die( '' );
		}

		$key = 'product%';

		// 前後の語も $like0 と同じ規則で扱う。trim して空になった語は「指定なし」（@@none）と
		// 同じ扱いにする。空語をそのまま渡すと post_title LIKE '%%' という常に真の条件が
		// SQL に1本増えるだけで、絞り込みには何も寄与しない。
		// Normalize the leading/trailing terms the same way as $like0: a term that is empty
		// after trim() is treated as "not specified" (@@none). Passing an empty term through
		// only adds an always-true post_title LIKE '%%' condition that narrows nothing.
		foreach ( array( 0, 1 ) as $woomb_i ) {
			$txt[ $woomb_i ] = trim( $txt[ $woomb_i ] );
			if ( '' === $txt[ $woomb_i ] ) {
				$txt[ $woomb_i ] = '@@none';
			}
		}

		if( $txt[0] == '@@none' ) {
			if( $txt[1] == '@@none' ) {
				$case = 1;
			} else {
				$case = 2;
				// LIKE 句へ渡す前に esc_like() で % / _ をエスケープする。
				// Escape % / _ with esc_like() before building the LIKE pattern.
				$like1 = '%' . $wpdb->esc_like( $txt[1] ) . '%';
			}
		} else {
			if( $txt[1] == '@@none' ) {
				$case = 2;
				$like1 = '%' . $wpdb->esc_like( $txt[0] ) . '%';
			} else {
				$case = 3;
				$like1 = '%' . $wpdb->esc_like( $txt[0] ) . '%';
				$like2 = '%' . $wpdb->esc_like( $txt[1] ) . '%';
			}
		}
		$likes = explode('##', $txt[2]);

		// 上限（20件）を超えるリクエストは一切処理しない（無制限ループ対策）。
		// Refuse to process the request at all when it exceeds the 20-item limit (prevents an unbounded loop).
		if ( count( $likes ) > 20 ) {
			// 処理は拒否するが、空文字だけは語数ぶん返す。空文字を1個だけ返すと JS 側の
			// response.split('||') が要素1個になり、pid[1] 以降が undefined になる。
			// jQuery の attr( name, undefined ) はセッターではなくゲッターとして働くため、
			// 2枠目以降に前回検索時の postid が残り、利用者が選んでいない商品がカートに入る。
			// Refuse to process, but still return one empty slot per term. Returning a single
			// empty string would make response.split('||') yield one element, leaving pid[1]
			// and beyond undefined; jQuery's attr( name, undefined ) acts as a getter, so the
			// previous postid would remain and an unselected product could end up in the cart.
			wp_die( implode( '||', array_fill( 0, min( count( $likes ), 100 ), '' ) ) );
		}

		// 語ごとに必ず1要素を積む。aj.js は response.split('||') の添字を quantity{i} の
		// 並びに対応させているため、ヒットしなかった語を詰めると i 番目の枠へ別商品のIDが入る。
		// Always push exactly one element per term. aj.js maps the index of
		// response.split('||') to the quantity{i} inputs, so collapsing a miss would put
		// another product's ID into that slot.
		$ids = array();
		foreach ($likes as $like) {
			$like = trim( $like );
			// 空語は検索しない。LIKE '%%' は全件走査になり、未認証の1リクエストから
			// 最大20回起こせる（取り出した行のうち使うのは1件だけ）。
			// Skip empty terms: LIKE '%%' scans every row and can be triggered up to 20
			// times per unauthenticated request, while only one row is ever used.
			if ( '' === $like ) {
				$ids[] = '';
				continue;
			}
			$like0 = '%' . $wpdb->esc_like( $like ) . '%';
			switch ($case) {
				case 1:
					// バリエーションは post_type != 'product_variation' の条件で除外されないが、
					// 自身と親の両方が publish であることを要求することで未公開商品の露出を防ぐ。
					// Variations are not excluded by post_type, but requiring both the row itself
					// and its parent to be published prevents unpublished products from leaking.
					$query = "SELECT p.ID FROM $wpdb->posts p
						LEFT JOIN $wpdb->posts parent ON p.post_type = 'product_variation' AND parent.ID = p.post_parent
						WHERE p.post_type LIKE %s
						AND p.post_title LIKE %s
						AND p.post_status = 'publish'
						AND ( p.post_type != 'product_variation' OR parent.post_status = 'publish' )
						LIMIT 1 ";
					$lists = $wpdb->get_results(
						$wpdb->prepare($query, $key, $like0), 'ARRAY_A');
					break;
				case 2:
					$query = "SELECT p.ID FROM $wpdb->posts p
						LEFT JOIN $wpdb->posts parent ON p.post_type = 'product_variation' AND parent.ID = p.post_parent
						WHERE p.post_type LIKE %s
						AND p.post_title LIKE %s
						AND p.post_title LIKE %s
						AND p.post_status = 'publish'
						AND ( p.post_type != 'product_variation' OR parent.post_status = 'publish' )
						LIMIT 1 ";
					$lists = $wpdb->get_results(
						$wpdb->prepare($query, $key, $like0, $like1), 'ARRAY_A');
					break;
				case 3:
					$query = "SELECT p.ID FROM $wpdb->posts p
						LEFT JOIN $wpdb->posts parent ON p.post_type = 'product_variation' AND parent.ID = p.post_parent
						WHERE p.post_type LIKE %s
						AND p.post_title LIKE %s
						AND p.post_title LIKE %s
						AND p.post_title LIKE %s
						AND p.post_status = 'publish'
						AND ( p.post_type != 'product_variation' OR parent.post_status = 'publish' )
						LIMIT 1 ";
					$lists = $wpdb->get_results(
						$wpdb->prepare($query, $key, $like0, $like1, $like2), 'ARRAY_A' );
					break;
			}
			$ids[] = ! empty( $lists ) ? $lists[0]['ID'] : '';
		}
		wp_die( implode( '||', $ids ) );
	}
	add_action( 'wp_ajax_etbs_woo_title_search', 'etbs_woo_title_search' );
	add_action( 'wp_ajax_nopriv_etbs_woo_title_search', 'etbs_woo_title_search' );
}

/*-------------------------------------------*/
/* ダッシュボードウィジェット（使い方・サポート案内）
/*-------------------------------------------*/
if ( ! function_exists( 'woomb_add_dashboard_widget' ) ) {
	/**
	 * ダッシュボードにサポート導線ウィジェットを登録する。
	 * 商品・カートを扱う機能であるため、権限は manage_woocommerce を要求する。
	 * Register the support-info dashboard widget.
	 * Requires manage_woocommerce because this plugin deals with products and the cart.
	 *
	 * @return void
	 */
	function woomb_add_dashboard_widget() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		wp_add_dashboard_widget(
			'woomb_dashboard_widget',
			'ETBS Modal Block',
			'woomb_render_dashboard_widget'
		);
	}
	add_action( 'wp_dashboard_setup', 'woomb_add_dashboard_widget' );
}

if ( ! function_exists( 'woomb_render_dashboard_widget' ) ) {
	/**
	 * ダッシュボードウィジェットの本文を出力する。
	 * Render the dashboard widget content.
	 *
	 * @return void
	 */
	function woomb_render_dashboard_widget() {
		?>
		<p><?php esc_html_e( 'モーダルウィンドウから商品をカートへ追加できるブロックです。', 'woo-modal-block' ); ?><?php esc_html_e( 'バリエーション違いの商品をまとめて選んでもらう用途に向いています。', 'woo-modal-block' ); ?></p>

		<strong><?php esc_html_e( '使い方', 'woo-modal-block' ); ?></strong>
		<ul style="margin:6px 0 12px 1.2em;list-style:disc;">
			<li>
				<?php
				printf(
					/* translators: 1: 「Woo Modal Block」（強調タグ付き） 2: 「ボタン」（強調タグ付き） 3: eb_btn（コードタグ付き） */
					esc_html__( 'ブロックエディタで%1$sブロックを追加します。ブロックの直前に%2$sブロックを配置し、追加CSSクラスに%3$sを指定してください（モーダルを開くトリガーになります）。', 'woo-modal-block' ),
					'<strong>' . esc_html__( '「Woo Modal Block」', 'woo-modal-block' ) . '</strong>',
					'<strong>' . esc_html__( '「ボタン」', 'woo-modal-block' ) . '</strong>',
					'<code>eb_btn</code>'
				);
				?>
			</li>
			<li><?php esc_html_e( 'ブロック設定パネルでタイトル・商品名・バリエーションのラベルと値を入力します。バリエーションリスト1は必須、リスト2は任意（トグルで有効化）です。', 'woo-modal-block' ); ?></li>
		</ul>

		<strong><?php esc_html_e( '注意事項', 'woo-modal-block' ); ?></strong>
		<ul style="margin:6px 0 12px 1.2em;list-style:disc;">
			<li><?php esc_html_e( '1つのモーダルで扱える商品は20種類までです。', 'woo-modal-block' ); ?><?php esc_html_e( '超えた場合、商品IDの検索は行われず、カートには1件も入りません。URLパラメーターから直接追加する場合は先頭20件までが処理されます。いずれも画面にエラーは表示されません。', 'woo-modal-block' ); ?></li>
			<li>
				<?php
				printf(
					/* translators: 1: add-to-cart（コードタグ付き） 2: quantity（コードタグ付き） 3: ||（コードタグ付き） */
					esc_html__( 'URLパラメーター %1$s と %2$s を %3$s 区切りで渡すと、外部リンクから複数商品を一括でカートへ追加し、カートページへリダイレクトできます。', 'woo-modal-block' ),
					'<code>add-to-cart</code>',
					'<code>quantity</code>',
					'<code>||</code>'
				);
				?>
			</li>
		</ul>

		<strong><?php esc_html_e( 'サポート', 'woo-modal-block' ); ?></strong>
		<p style="margin:6px 0 12px;">
			<?php
			printf(
				/* translators: 1: サポートページへのリンク開始タグ 2: リンク終了タグ 3: 寄付ページへのリンク開始タグ 4: リンク終了タグ */
				esc_html__( '有償サポートやカスタマイズは%1$sこちらのページ%2$sからお問い合わせください。開発の継続は%3$sご支援%4$sで応援いただけます。', 'woo-modal-block' ),
				'<a href="https://etbs.jp/product-category/wordpress-tools/?utm_source=woo-modal-block&utm_medium=plugin" target="_blank" rel="noopener noreferrer">',
				'</a>',
				'<a href="https://etbs.jp/product/donate/?utm_source=woo-modal-block&utm_medium=plugin" target="_blank" rel="noopener noreferrer">',
				'</a>'
			);
			?>
		</p>
		<?php
	}
}

/*-------------------------------------------*/
/* 寄付・開発依頼リンク（プラグイン一覧行）
/*-------------------------------------------*/
if ( ! function_exists( 'woomb_plugin_row_meta' ) ) {
	/**
	 * プラグイン一覧の自プラグイン行に「開発を支援」「開発のご依頼」リンクを追加する。
	 * Add "Support development" / "Request development" links to this plugin's own row.
	 *
	 * @param string[] $links プラグイン行に表示されるリンクの配列。Existing row meta links.
	 * @param string   $file  相対パス（`plugin_basename()`）で表された対象プラグインファイル。Plugin file the row belongs to (relative, `plugin_basename()` form).
	 * @return string[] リンクを追加した配列。The links array, with our links appended when this is our own row.
	 */
	function woomb_plugin_row_meta( $links, $file ) {
		if ( plugin_basename( WOOMB_PLUGIN_FILE ) !== $file ) {
			return $links;
		}
		$links[] = '<a href="https://etbs.jp/product/donate/?utm_source=woo-modal-block&utm_medium=plugin" target="_blank" rel="noopener noreferrer">'
			. esc_html__( '開発を支援', 'woo-modal-block' ) . '</a>';
		$links[] = '<a href="https://etbs.jp/product-category/wordpress-tools/?utm_source=woo-modal-block&utm_medium=plugin" target="_blank" rel="noopener noreferrer">'
			. esc_html__( '開発のご依頼', 'woo-modal-block' ) . '</a>';
		return $links;
	}
	add_filter( 'plugin_row_meta', 'woomb_plugin_row_meta', 10, 2 );
}

?>
