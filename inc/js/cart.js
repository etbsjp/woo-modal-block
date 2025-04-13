window.onload = function(){
	jQuery('.woocommerce table.shop_table div.quantity input.qty').after('<span class="tm-qty-minus"></span><span class="tm-qty-plus"></span>');
	jQuery('.single .entry-summary div.quantity input.qty').after('<span class="tm-qty-minus"></span><span class="tm-qty-plus"></span>');
}


function initQty() {

	var body =  jQuery('body');
	body.on("click", ".tm-qty-plus", function () {
		changeQuantity( jQuery(this).parent(), 'add');
	});
	body.on("click", ".tm-qty-minus", function () {
		changeQuantity( jQuery(this).parent(), 'remove');
	});

	function changeQuantity(quantity, state) {
		var input = quantity.find('input[type="number"]'),
			max = input.attr('max'),
			min = input.attr('min'),
			inputVal = parseFloat(input.val());

		switch (state) {
			case 'add':
				if (inputVal < max || '' === max) {
					inputVal = inputVal + 1;
				}
				break;
			case 'remove':
				if (inputVal > min) {
					inputVal = inputVal - 1;
				}
				break;
			default:
				break
		}

		input.val(inputVal);
		input.trigger("change");
	}
}

initQty();