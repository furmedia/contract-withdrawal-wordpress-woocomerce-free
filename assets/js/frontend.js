(function () {
	'use strict';

	function ready(callback) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', callback);
		} else {
			callback();
		}
	}

	ready(function () {
		var form = document.getElementById('furmrowi-form');
		if (!form) {
			return;
		}

		document.documentElement.classList.add('furmrowi-js');
		var accountPanel = document.getElementById('furmrowi-account-order');
		var manualPanel = document.getElementById('furmrowi-manual-order');
		var accountItems = document.getElementById('furmrowi-account-items');
		var manualItems = document.getElementById('furmrowi-manual-items');
		var manualReference = document.getElementById('furmrowi-contract-reference');
		var orderSelect = document.getElementById('furmrowi-order-id');
		var loadOrder = form.querySelector('[data-furmrowi-load-order]');
		var itemList = document.getElementById('furmrowi-item-list');
		var addItem = document.getElementById('furmrowi-add-item');
		var submit = document.getElementById('furmrowi-submit');
		var honeypot = form.querySelector('[data-furmrowi-honeypot]');
		var maximumItems = 20;

		if (honeypot) {
			honeypot.value = '';
		}

		function selected(name, fallback) {
			var input = form.querySelector('input[name="' + name + '"]:checked');
			if (!input) {
				input = form.querySelector('input[name="' + name + '"][type="hidden"]');
			}
			return input ? input.value : fallback;
		}

		function show(panel, visible) {
			if (!panel) {
				return;
			}
			panel.hidden = !visible;
			panel.setAttribute('aria-hidden', visible ? 'false' : 'true');
		}

		function updateProductRow(row, enabled) {
			var checkbox = row.querySelector('[data-furmrowi-product-checkbox]');
			var quantity = row.querySelector('[data-furmrowi-product-quantity]');
			if (checkbox) {
				checkbox.disabled = !enabled;
			}
			if (quantity) {
				quantity.disabled = !enabled || !checkbox || !checkbox.checked;
				quantity.required = enabled && checkbox && checkbox.checked;
			}
		}

		function updateState() {
			var account = selected('order_mode', 'manual') === 'account';
			var partial = selected('scope', 'full') === 'partial';
			var manualPartial = !account && partial;

			show(accountPanel, account);
			show(manualPanel, !account);
			show(accountItems, account);
			show(manualItems, manualPartial);

			if (manualReference) {
				manualReference.disabled = account;
				manualReference.required = !account;
			}
			if (orderSelect) {
				orderSelect.disabled = !account;
				orderSelect.required = account;
			}
			if (loadOrder) {
				loadOrder.disabled = !account;
			}
			if (manualItems) {
				Array.prototype.forEach.call(manualItems.querySelectorAll('input'), function (input) {
					input.disabled = !manualPartial;
					input.required = manualPartial;
				});
			}
			if (accountItems) {
				var heading = accountItems.querySelector('[data-furmrowi-account-heading]');
				if (heading) {
					heading.textContent = partial ? heading.getAttribute('data-partial-text') : heading.getAttribute('data-full-text');
				}
				Array.prototype.forEach.call(accountItems.querySelectorAll('[data-furmrowi-account-product]'), function (row) {
					updateProductRow(row, account && partial);
				});
			}
		}

		function itemRows() {
			return itemList ? itemList.querySelectorAll('[data-furmrowi-item-row]') : [];
		}

		function reindex(row, index) {
			var name = row.querySelector('[data-furmrowi-item-name]');
			var quantity = row.querySelector('[data-furmrowi-item-quantity]');
			var nameLabel = name ? row.querySelector('label[for^="furmrowi-item-name-"]') : null;
			var quantityLabel = quantity ? row.querySelector('label[for^="furmrowi-item-qty-"]') : null;
			if (name) {
				name.name = 'items[' + index + '][name]';
				name.id = 'furmrowi-item-name-' + index;
			}
			if (quantity) {
				quantity.name = 'items[' + index + '][quantity]';
				quantity.id = 'furmrowi-item-qty-' + index;
			}
			if (nameLabel) {
				nameLabel.setAttribute('for', name.id);
			}
			if (quantityLabel) {
				quantityLabel.setAttribute('for', quantity.id);
			}
		}

		function reindexAll() {
			Array.prototype.forEach.call(itemRows(), reindex);
			if (addItem) {
				addItem.disabled = itemRows().length >= maximumItems;
			}
		}

		if (addItem && itemList) {
			addItem.addEventListener('click', function () {
				var rows = itemRows();
				if (!rows.length || rows.length >= maximumItems) {
					return;
				}
				var row = rows[0].cloneNode(true);
				var name = row.querySelector('[data-furmrowi-item-name]');
				var quantity = row.querySelector('[data-furmrowi-item-quantity]');
				if (name) {
					name.value = '';
				}
				if (quantity) {
					quantity.value = '1';
				}
				itemList.appendChild(row);
				reindexAll();
				updateState();
				if (name) {
					name.focus();
				}
			});

			itemList.addEventListener('click', function (event) {
				var button = event.target.closest('[data-furmrowi-remove-item]');
				if (!button) {
					return;
				}
				var row = button.closest('[data-furmrowi-item-row]');
				var rows = itemRows();
				if (rows.length === 1) {
					row.querySelector('[data-furmrowi-item-name]').value = '';
					row.querySelector('[data-furmrowi-item-quantity]').value = '1';
				} else {
					row.parentNode.removeChild(row);
					reindexAll();
				}
			});
		}

		Array.prototype.forEach.call(form.querySelectorAll('input[name="scope"],input[name="order_mode"]'), function (radio) {
			radio.addEventListener('change', updateState);
		});
		if (accountItems) {
			accountItems.addEventListener('change', function (event) {
				if (event.target.matches('[data-furmrowi-product-checkbox]')) {
					updateProductRow(event.target.closest('[data-furmrowi-account-product]'), selected('scope', 'full') === 'partial');
				}
			});
		}

		form.addEventListener('submit', function (event) {
			var submitter = event.submitter || document.activeElement;
			var loadingOrder = submitter && submitter.value === 'load_order';
			if (loadingOrder) {
				submitter.setAttribute('aria-busy', 'true');
				return;
			}
			updateState();
			if (!form.checkValidity()) {
				event.preventDefault();
				if (typeof form.reportValidity === 'function') {
					form.reportValidity();
				}
				return;
			}
			if (submit) {
				var actionField = document.createElement('input');
				actionField.type = 'hidden';
				actionField.name = 'furmrowi_action';
				actionField.value = 'submit';
				form.appendChild(actionField);
				submit.textContent = submit.getAttribute('data-processing-text') || submit.textContent;
				submit.disabled = true;
				submit.setAttribute('aria-busy', 'true');
			}
		});

		var firstError = form.querySelector('[aria-invalid="true"]') || document.getElementById('furmrowi-error-summary');
		if (firstError) {
			firstError.focus();
		}
		reindexAll();
		updateState();
	});
}());
