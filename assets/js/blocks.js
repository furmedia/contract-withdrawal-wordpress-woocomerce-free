(function (blocks, element, i18n) {
	'use strict';
	var el = element.createElement;
	var __ = i18n.__;
	var t = window.cwfwBlocksI18n || {};
	var definitions = [
		['furmedia/romanian-withdrawal-law-form', t.formTitle || __('Withdrawal form', 'furmedia-romanian-withdrawal-law-for-woocommerce'), t.formDescription || __('Displays the complete online contract-withdrawal form.', 'furmedia-romanian-withdrawal-law-for-woocommerce'), 'feedback'],
		['furmedia/romanian-withdrawal-law-legal', t.legalTitle || __('Withdrawal information', 'furmedia-romanian-withdrawal-law-for-woocommerce'), t.legalDescription || __('Displays the configured withdrawal information.', 'furmedia-romanian-withdrawal-law-for-woocommerce'), 'privacy'],
		['furmedia/romanian-withdrawal-law-link', t.linkTitle || __('Withdrawal link', 'furmedia-romanian-withdrawal-law-for-woocommerce'), t.linkDescription || __('Displays the permanent withdrawal link.', 'furmedia-romanian-withdrawal-law-for-woocommerce'), 'admin-links']
	];

	definitions.forEach(function (definition) {
		blocks.registerBlockType(definition[0], {
			apiVersion: 3,
			title: definition[1],
			description: definition[2],
			icon: definition[3],
			category: 'widgets',
			supports: { html: false },
			edit: function () {
				return el('div', { className: 'components-placeholder' },
					el('div', { className: 'components-placeholder__label' }, definition[1]),
					el('div', { className: 'components-placeholder__instructions' }, definition[2])
				);
			},
			save: function () { return null; }
		});
	});
}(window.wp.blocks, window.wp.element, window.wp.i18n));
