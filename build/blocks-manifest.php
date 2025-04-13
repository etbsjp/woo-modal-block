<?php
// This file is generated. Do not modify it manually.
return array(
	'woo-modal-block' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'create-block/woo-modal-block',
		'version' => '0.1.0',
		'title' => 'Woo Modal Block',
		'category' => 'woocommerce',
		'icon' => 'smiley',
		'description' => 'モーダルウィンドウから商品をカートへ追加します',
		'attributes' => array(
			'titleField' => array(
				'type' => 'string'
			),
			'itemField' => array(
				'type' => 'string'
			),
			'txtVars1' => array(
				'type' => 'string'
			),
			'txtVars1val' => array(
				'type' => 'string'
			),
			'toggleField' => array(
				'type' => 'boolean'
			),
			'txtVars2' => array(
				'type' => 'string'
			),
			'txtVars2val' => array(
				'type' => 'string'
			),
			'btnText' => array(
				'type' => 'string'
			)
		),
		'example' => array(
			
		),
		'supports' => array(
			'html' => false
		),
		'textdomain' => 'woo-modal-block',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css'
	)
);
