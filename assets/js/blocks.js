(function (blocks, element, i18n) {
	'use strict';
	var el = element.createElement;
	var __ = i18n.__;
	var t = window.cwfwBlocksI18n || {};
	var definitions = [
		['foxly/contract-withdrawal-free-form', t.formTitle || __('Withdrawal form', 'contract-withdrawal-free-for-woocommerce'), t.formDescription || __('Displays the complete online contract-withdrawal form.', 'contract-withdrawal-free-for-woocommerce'), 'feedback'],
		['foxly/contract-withdrawal-free-legal', t.legalTitle || __('Withdrawal information', 'contract-withdrawal-free-for-woocommerce'), t.legalDescription || __('Displays the configured withdrawal information.', 'contract-withdrawal-free-for-woocommerce'), 'privacy'],
		['foxly/contract-withdrawal-free-link', t.linkTitle || __('Withdrawal link', 'contract-withdrawal-free-for-woocommerce'), t.linkDescription || __('Displays the permanent withdrawal link.', 'contract-withdrawal-free-for-woocommerce'), 'admin-links']
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
