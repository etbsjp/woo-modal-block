function etbs_woo_item_search(){
	var mesA = jQuery("#itemname").text();
	if (!mesA || mesA.trim() === "") {
		mesA = "@@none";
	}
	var mesB = jQuery("select.eb_modal_select_date").val();
	if (typeof mesB === "undefined" || mesB === null) {
		mesB = "@@none";
	}
	var cnt1 = jQuery("#itemname").attr('cnt1');
	for (let i = 0; i < cnt1; i++) {
		if( i == 0 ){
			var mes0 = jQuery("#kinds" + i).text();
		} else {
			mes0 += "##" + jQuery("#kinds" + i).text();
		}
	}
	jQuery.ajax({
		type: "POST",
		url: woombAjData.ajaxurl,
		data: {
			"action": "etbs_woo_title_search",
			"mes"   : mesA + "||" + mesB + "||" + mes0,
		},
		success: function( response ){
			var pid = response.split('||');
			var cnt1 = jQuery("#itemname").attr('cnt1');
			for (let i = 0; i < cnt1; i++) {
				jQuery("[name=quantity" + i + "]").attr( "postid", pid[i] );
			}
		}
	});
	return false;
	alert( "予期しないエラーしました。何度も発生する場合は管理者に問い合わせて下さい。" );
}

jQuery(function() {
  jQuery("select.eb_modal_select_date").change(function () {
	var val = jQuery("select.eb_modal_select_date").val();
	if( val == 0 ) {
		var cnt1 = jQuery("#itemname").attr('cnt1');
		for (let i = 0; i < cnt1; i++) {
			jQuery("[name=quantity" + i + "]").attr( "postid", "" );
		}
		jQuery(".eb_modal a.btn").css( "pointer-events", "none" );
		jQuery(".eb_modal a.btn").attr( "href", "#" );
	} else {
		jQuery(".eb_modal a.btn").css( "pointer-events", "none" );
		setTimeout(() => {
			etbs_woo_item_search();
			setTimeout(() => {
				etbs_woo_item_quantity();
				setTimeout(() => {
					jQuery(".eb_modal a.btn").css( "pointer-events", "auto" );
				}, 200);
			}, 1900);
		}, 100);
	}
  });
});

function etbs_woo_item_quantity() {
	var val = jQuery("select.eb_modal_select_date").val();
	if( val != 0 ) {
		var cnt1 = jQuery("#itemname").attr('cnt1');
		for (let i = 0; i < cnt1; i++) {
			if( i == 0 ){
				var pid = jQuery("[name=quantity" + i + "]").attr('postid');
				var val = jQuery("[name=quantity" + i + "]").val();
			} else {
				pid += "||" + jQuery("[name=quantity" + i + "]").attr('postid');
				val += "||" + jQuery("[name=quantity" + i + "]").val();
			}
		}
		// カート追加先は PHP 側で解決した URL を使う。cartbase が無い場合（フルページキャッシュで
		// 古い HTML が配られた場合）のみ、従来の /store/ に落とす。
		// Use the URL resolved on the PHP side. Fall back to the old /store/ path only when
		// cartbase is absent, i.e. when a full-page cache served older HTML.
		var base = woombAjData.cartbase || ( woombAjData.siteurl + "/store/" );
		var sep  = base.indexOf( "?" ) === -1 ? "?" : "&";
		var rurl = base + sep + "add-to-cart=" + pid + "&quantity=" + val;
		jQuery(".eb_modal a.btn").attr( "href", rurl );
	}
}

jQuery('[name^=quantity]').change(function() {
	setTimeout(() => {
		etbs_woo_item_search();
		setTimeout(() => {
			etbs_woo_item_quantity();
			setTimeout(() => {
				jQuery(".eb_modal a.btn").css( "pointer-events", "auto" );
			}, 200);
		}, 1900);
	}, 100);
});
