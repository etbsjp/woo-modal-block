// ボタン、モダル、モダルの閉じるボタン、オーバーレイを変数に格納
const btn = document.querySelector('.eb_btn');
const modal = document.querySelector('.eb_modal');
const closeBtn = document.querySelector('.eb_close');
const overlay = document.querySelector('.eb_overlay');

// ブロックを設置していないページ（トップページ等）では4つとも null になる。
// 1つでも欠けたまま addEventListener 等に触れると TypeError になるため、
// 4つ全てが取得できた場合のみイベントを登録する。
if ( btn && modal && closeBtn && overlay ) {
	// ボタンをクリックしたら、モダルとオーバーレイに.activeを付ける
	btn.addEventListener('click', function(e){
		// aタグのデフォルトの機能を停止する
		e.preventDefault();
		// モーダルとオーバーレイにactiveクラスを付与する
		modal.classList.add('active');
		overlay.classList.add('active');
	});

	// モダルの閉じるボタンをクリックしたら、モダルとオーバーレイのactiveクラスを外す
	closeBtn.addEventListener('click', function(){
		modal.classList.remove('active');
		overlay.classList.remove('active');
	});

	// オーバーレイをクリックしたら、モダルとオーバーレイのactiveクラスを外す
	overlay.addEventListener('click', function() {
		modal.classList.remove('active');
		overlay.classList.remove('active');
	});
}
