<?php
header('Content-Type: application/x-javascript; charset=utf-8');
require_once( dirname( __FILE__ , 6) . '/wp-load.php' );
$ajaxurl = admin_url( 'admin-ajax.php');
$surl = site_url();
$script = <<< EOF
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
	console.log( mesA + "||" + mesB + "||" + mes0 );
	jQuery.ajax({
		type: "POST",
		url: "{$ajaxurl}",
		data: {
			"action": "etbs_woo_title_search",
			"mes"   : mesA + "||" + mesB + "||" + mes0,
		},
		success: function( response ){
			console.log( response );
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
		var rurl = "{$surl}" + "/store/?add-to-cart=" + pid +  "&quantity=" + val;
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

EOF;
echo $script;