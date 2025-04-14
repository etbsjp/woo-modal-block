/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import {
	RichText,
	BlockControls,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { TextControl, TextareaControl, ToggleControl, PanelBody } = wp.components;
	const { titleField, itemField, txtVars1, txtVars2, txtVars1val, txtVars2val, toggleField, btnText } = attributes;
	function onChangeTextField( newValue ) {
		setAttributes( { titleField: newValue } );
	}
	function onChangeItemField( newValue ) {
		setAttributes( { itemField: newValue } );
	}
	function onChangeTextareaField1( newValue ) {
		setAttributes( { txtVars1: newValue } );
	}
	function onChangeTextareaValue1( newValue ) {
		setAttributes( { txtVars1val: newValue } );
	}
	function onChangeToggleField( newValue ) {
			setAttributes( { toggleField: newValue } );
		}
	function onChangeTextareaField2( newValue ) {
		setAttributes( { txtVars2: newValue } );
	}
	function onChangeTextareaValue2( newValue ) {
		setAttributes( { txtVars2val: newValue } );
	}
	function onChangebtnText( newValue ) {
		setAttributes( { btnText: newValue } );
	}
	return (
		<>
		<div { ...useBlockProps() }>
			{ __(
				'直前に[ ボタン ]を追加して[ 追加CSSクラス ]に[ eb_btn ]を追記してください',
				'woo-modal-block'
			) }
		</div>
			<InspectorControls>
				<PanelBody title={ __( 'Settings' ) }>
					<TextControl
						label="タイトル"
						value={ titleField }
						onChange={ onChangeTextField }
					/>
					<TextControl
						label="商品名"
						value={ itemField }
						onChange={ onChangeItemField }
					/>
					<TextareaControl
						label="バリエーションリスト1ラベル(必須)"
						value={ txtVars1 }
						onChange={ onChangeTextareaField1 }			
					/>
					<TextareaControl
						label="バリエーションリスト1値(必須)"
						help="商品のバリエーションに使用した内容を入力してください"
						value={ txtVars1val }
						onChange={ onChangeTextareaValue1 }			
					/>
					<ToggleControl
						label="バリエーション2を使用する"
						checked={ toggleField }
						onChange={ onChangeToggleField }
					/>
					<TextareaControl
						label="バリエーションリスト2ラベル"
						value={ txtVars2 }
						onChange={ onChangeTextareaField2 }				
					/>
					<TextareaControl
						label="バリエーションリスト2値"
						help="商品のバリエーションに使用した内容を入力してください"
						value={ txtVars2val }
						onChange={ onChangeTextareaValue2 }				
					/>
					<TextControl
						label="ボタンテキスト"
						value={ btnText }
						onChange={ onChangebtnText }
					/>
				</PanelBody>
			</InspectorControls>
		</>
	);
}