<?php
/*-------------------------------------------*/
/* JS/CSS 追加
/*-------------------------------------------*/
if ( ! function_exists( 'etbs_woo_modal_block_scripts' ) ){
	function etbs_woo_modal_block_scripts() {
		global $woomb_version;
		wp_enqueue_script( 'cart-js', plugins_url( '/woo-modal-block/inc/js/cart.js' ), array( 'jquery' ), $woomb_version, true );
		wp_enqueue_script( 'ajjs', plugins_url( '/woo-modal-block/inc/js/ajjs.php' ), array( 'jquery' ), $woomb_version, true );
	}
	add_action( 'wp_enqueue_scripts', 'etbs_woo_modal_block_scripts' );
}

/*-------------------------------------------*/
/* WooのURLパラメーターをカスタマイズ
/*-------------------------------------------*/
if ( ! function_exists( 'etbs_woo_redirecct_url' ) ){
	function etbs_woo_redirecct_url() {
		if( isset($_GET['add-to-cart']) && isset($_GET['quantity']) ) {
			if( strpos( $_GET['add-to-cart'], '||' ) !== false && strpos( $_GET['quantity'], '||' ) !== false ) {
				$items = explode( '||', $_GET['add-to-cart'] );
				$value = explode( '||', $_GET['quantity'] );
				$c1 = count($items);
				$c2 = count($value);
				if( $c1 == $c2 ) {
					for( $i = 0; $i < $c1; $i++ ) {
						WC()->cart->add_to_cart( $items[$i], $value[$i] );
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
	function etbs_woo_title_search() {
		$txt = explode('||', $_POST['mes']);
		global $wpdb;
		$key = 'product%';
		if( $txt[0] == '@@none' ) {
			if( $txt[1] == '@@none' ) {
				$case = 1;
			} else {
				$case = 2;
				$like1 = '%' . $txt[1] . '%';
			}
		} else {
			if( $txt[1] == '@@none' ) {
				$case = 2;
				$like1 = '%' . $txt[0] . '%';
			} else {
				$case = 3;
				$like1 = '%' . $txt[0] . '%';
				$like2 = '%' . $txt[1] . '%';
			}
		}
		$likes = explode('##', $txt[2]);
		$list = '';
		$a = 1;
		foreach ($likes as $like) {
			$like0 = '%' . $like . '%';
			switch ($case) {
				case 1:
					$query = "SELECT ID FROM $wpdb->posts 
						WHERE post_type LIKE %s 
						AND post_title LIKE %s ";
					$lists = $wpdb->get_results(
						$wpdb->prepare($query, $key, $like0), 'ARRAY_A');
					break;
				case 2:
					$query = "SELECT ID FROM $wpdb->posts 
						WHERE post_type LIKE %s 
						AND post_title LIKE %s 
						AND post_title LIKE %s ";
					$lists = $wpdb->get_results(
						$wpdb->prepare($query, $key, $like0, $like1), 'ARRAY_A');
					break;
				case 3:
					$query = "SELECT ID FROM $wpdb->posts 
						WHERE post_type LIKE %s 
						AND post_title LIKE %s 
						AND post_title LIKE %s 
						AND post_title LIKE %s ";
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
	//add_action( 'wp_ajax_nopriv_etbs_woo_title_search', 'etbs_woo_title_search' );
}

?>