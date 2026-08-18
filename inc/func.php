<?php
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
		wp_enqueue_script( 'cartjs', plugins_url( 'inc/js/cart.js', WOOMB_PLUGIN_FILE ), array( 'jquery' ), $woomb_version, true );
		wp_enqueue_script( 'ajjs', plugins_url( 'inc/js/aj.js', WOOMB_PLUGIN_FILE ), array( 'jquery' ), $woomb_version, true );
		// aj.js 内で使う ajaxurl / siteurl を PHP からローカライズして渡す（aj.js は静的ファイルのため PHP 変数を直接埋め込めない）。
		// Localize ajaxurl / siteurl for aj.js, since it is now a static file and can no longer embed PHP variables directly.
		wp_localize_script( 'ajjs', 'woombAjData', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'siteurl' => site_url(),
		) );
		wp_enqueue_script( 'overlay', plugins_url( 'inc/js/overlay.js', WOOMB_PLUGIN_FILE ), array( 'jquery' ), $woomb_version, true );
	}
	add_action( 'wp_enqueue_scripts', 'etbs_woo_modal_block_scripts' );
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
		$txt = explode( '||', $mes );

		// 想定外の形式（"||" 区切りの要素が3未満）は何も処理せず終了する（undefined array key 対策も兼ねる）。
		// Reject malformed input (fewer than 3 "||"-separated parts) without processing; this also avoids undefined array key warnings.
		if ( count( $txt ) < 3 ) {
			wp_die( '' );
		}

		$key = 'product%';
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
			wp_die( '' );
		}

		$list = '';
		$a = 1;
		foreach ($likes as $like) {
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
						AND ( p.post_type != 'product_variation' OR parent.post_status = 'publish' ) ";
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
						AND ( p.post_type != 'product_variation' OR parent.post_status = 'publish' ) ";
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
						AND ( p.post_type != 'product_variation' OR parent.post_status = 'publish' ) ";
					$lists = $wpdb->get_results(
						$wpdb->prepare($query, $key, $like0, $like1, $like2), 'ARRAY_A' );
					break;
			}
			if (!empty($lists)) {
				if ($a == 1) {
					$list = $lists[0]['ID'];
				} else {
					$list .= '||' . $lists[0]['ID'];
				}
			}
			$a++;
		}
		wp_die($list);
	}
	add_action( 'wp_ajax_etbs_woo_title_search', 'etbs_woo_title_search' );
	add_action( 'wp_ajax_nopriv_etbs_woo_title_search', 'etbs_woo_title_search' );
}

?>
