/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * The save function defines the way in which the different attributes should
 * be combined into the final markup, which is then serialized by the block
 * editor into `post_content`.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#save
 *
 * @return {Element} Element to render.
 */
export default function save( { attributes } ) {
	const { titleField, itemField, txtVars1, txtVars2, txtVars1val, txtVars2val, toggleField, btnText } = attributes;
	var txt1l = txtVars1.split("\n");
	var txt1v = txtVars1val.split("\n");
	var txt1c = txt1v.length;
	if( toggleField ) {
		var txt2l = txtVars2.split("\n");
		var txt2v = txtVars2val.split("\n");
		var txt2c = txt2v.length;
	}
	return (
		<div { ...useBlockProps.save() } >
			<div class="eb_overlay"></div>
			<div class="eb_modal" >
				<div class="eb_close">&times;</div>
				<h3 class="wp-block-heading has-text-align-center">{ titleField }</h3>
				<span id="itemname" class="eb_modalitem" cnt1={txt1c} cnt2={txt2c}>{ itemField }</span>
				{toggleField && (
					<select className="eb_modal_select_date">
						<option value="0">選択してください</option>
						{txt2l.map((label, index) => (
							<option key={index} value={txt2v[index]}>{label}</option>
						))}
					</select>
				)}
				<ul class="eb_modal_attribute">
					{txt1l.map((label, index) => (
						<li>
							<span class="kinds">{label}</span><span id={`kinds${index}`} class="eb_modalitem">{txt1v[index]}</span>
							<div class="quantity">
								<input type="number" class="input-text qty text" name={`quantity${index}`} value="0" aria-label="商品数量" min="0" max="" step="1" placeholder="" inputmode="numeric" autocomplete="off" postid="" />
								<span class="tm-qty-minus"></span><span class="tm-qty-plus"></span>
							</div>
						</li>
					))}
				</ul>
				<div class="wp-block-vk-blocks-button vk_button vk_button-color-custom vk_button-align-center">
					<a href="#" class="vk_button_link btn has-background has-vk-color-primary-background-color btn-md" role="button" aria-pressed="true" rel="noopener">
						<div class="vk_button_link_caption"><span class="vk_button_link_txt">{ btnText }</span></div>
					</a>
				</div>
			</div>
			<section class="eb_section"></section>
		</div>
	);
}
