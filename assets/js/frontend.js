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
		var form = document.getElementById('cwfw-form');
		if (!form) {
			return;
		}

		document.documentElement.classList.add('cwfw-js');
		var accountPanel = document.getElementById('cwfw-account-order');
		var manualPanel = document.getElementById('cwfw-manual-order');
		var accountItems = document.getElementById('cwfw-account-items');
		var manualItems = document.getElementById('cwfw-manual-items');
		var manualReference = document.getElementById('cwfw-contract-reference');
		var orderSelect = document.getElementById('cwfw-order-id');
		var loadOrder = form.querySelector('[data-cwfw-load-order]');
		var itemList = document.getElementById('cwfw-item-list');
		var addItem = document.getElementById('cwfw-add-item');
		var submit = document.getElementById('cwfw-submit');
		var honeypot = form.querySelector('[data-cwfw-honeypot]');
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
			var checkbox = row.querySelector('[data-cwfw-product-checkbox]');
			var quantity = row.querySelector('[data-cwfw-product-quantity]');
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
				var heading = accountItems.querySelector('[data-cwfw-account-heading]');
				if (heading) {
					heading.textContent = partial ? heading.getAttribute('data-partial-text') : heading.getAttribute('data-full-text');
				}
				Array.prototype.forEach.call(accountItems.querySelectorAll('[data-cwfw-account-product]'), function (row) {
					updateProductRow(row, account && partial);
				});
			}
		}

		function itemRows() {
			return itemList ? itemList.querySelectorAll('[data-cwfw-item-row]') : [];
		}

		function reindex(row, index) {
			var name = row.querySelector('[data-cwfw-item-name]');
			var quantity = row.querySelector('[data-cwfw-item-quantity]');
			var nameLabel = name ? row.querySelector('label[for^="cwfw-item-name-"]') : null;
			var quantityLabel = quantity ? row.querySelector('label[for^="cwfw-item-qty-"]') : null;
			if (name) {
				name.name = 'items[' + index + '][name]';
				name.id = 'cwfw-item-name-' + index;
			}
			if (quantity) {
				quantity.name = 'items[' + index + '][quantity]';
				quantity.id = 'cwfw-item-qty-' + index;
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
				var name = row.querySelector('[data-cwfw-item-name]');
				var quantity = row.querySelector('[data-cwfw-item-quantity]');
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
				var button = event.target.closest('[data-cwfw-remove-item]');
				if (!button) {
					return;
				}
				var row = button.closest('[data-cwfw-item-row]');
				var rows = itemRows();
				if (rows.length === 1) {
					row.querySelector('[data-cwfw-item-name]').value = '';
					row.querySelector('[data-cwfw-item-quantity]').value = '1';
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
				if (event.target.matches('[data-cwfw-product-checkbox]')) {
					updateProductRow(event.target.closest('[data-cwfw-account-product]'), selected('scope', 'full') === 'partial');
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
				actionField.name = 'cwfw_action';
				actionField.value = 'submit';
				form.appendChild(actionField);
				submit.textContent = submit.getAttribute('data-processing-text') || submit.textContent;
				submit.disabled = true;
				submit.setAttribute('aria-busy', 'true');
			}
		});

		var firstError = form.querySelector('[aria-invalid="true"]') || document.getElementById('cwfw-error-summary');
		if (firstError) {
			firstError.focus();
		}
		reindexAll();
		updateState();
	});
}());
