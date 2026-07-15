<?php defined( 'ABSPATH' ) || exit; ?>
<section class="furmrowi-wrap" aria-labelledby="furmrowi-heading">
	<h2 id="furmrowi-heading"><?php esc_html_e( 'Withdrawal from contract', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></h2>
	<div class="furmrowi-intro">
		<p><?php esc_html_e( 'Use this function to exercise your right to withdraw from a distance contract online, without giving a reason, within the applicable legal period. After submission, an acknowledgement containing your declaration and the date and time of submission will be sent to the email address provided without undue delay.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></p>
		<p class="furmrowi-muted"><?php esc_html_e( 'Fields marked with * are required.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></p>
	</div>

	<?php if ( ! empty( $errors ) ) : ?>
		<div class="furmrowi-notice furmrowi-notice-error" id="furmrowi-error-summary" role="alert" tabindex="-1">
			<?php echo esc_html( ! empty( $errors['warning'] ) ? $errors['warning'] : __( 'Review the highlighted fields and try again.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ) ); ?>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( $return_url ); ?>" class="furmrowi-form" id="furmrowi-form" novalidate>
		<input type="hidden" name="furmrowi_nonce" value="<?php echo esc_attr( $nonce ); ?>">
		<input type="hidden" name="furmrowi_idempotency" value="<?php echo esc_attr( $idempotency ); ?>">
		<input type="hidden" name="furmrowi_return_url" value="<?php echo esc_url( $return_url ); ?>">
		<div class="furmrowi-honeypot" aria-hidden="true">
			<label for="furmrowi-check"><?php esc_html_e( 'Do not fill this field', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></label>
			<input type="text" id="furmrowi-check" name="furmrowi_check_7f31" value="" tabindex="-1" autocomplete="off" readonly data-furmrowi-honeypot>
		</div>

		<fieldset class="furmrowi-fieldset">
			<legend><?php esc_html_e( 'Your declaration', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></legend>
			<div class="furmrowi-grid furmrowi-grid-2">
				<div class="furmrowi-field <?php echo isset( $errors['firstname'] ) ? 'has-error' : ''; ?>">
					<label for="furmrowi-firstname"><?php esc_html_e( 'First name *', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></label>
					<input type="text" id="furmrowi-firstname" name="firstname" value="<?php echo esc_attr( $form['firstname'] ); ?>" maxlength="64" autocomplete="given-name" required <?php echo isset( $errors['firstname'] ) ? 'aria-invalid="true" aria-describedby="furmrowi-error-firstname"' : ''; ?>>
					<?php if ( isset( $errors['firstname'] ) ) : ?><span class="furmrowi-error" id="furmrowi-error-firstname"><?php echo esc_html( $errors['firstname'] ); ?></span><?php endif; ?>
				</div>
				<div class="furmrowi-field <?php echo isset( $errors['lastname'] ) ? 'has-error' : ''; ?>">
					<label for="furmrowi-lastname"><?php esc_html_e( 'Last name *', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></label>
					<input type="text" id="furmrowi-lastname" name="lastname" value="<?php echo esc_attr( $form['lastname'] ); ?>" maxlength="64" autocomplete="family-name" required <?php echo isset( $errors['lastname'] ) ? 'aria-invalid="true" aria-describedby="furmrowi-error-lastname"' : ''; ?>>
					<?php if ( isset( $errors['lastname'] ) ) : ?><span class="furmrowi-error" id="furmrowi-error-lastname"><?php echo esc_html( $errors['lastname'] ); ?></span><?php endif; ?>
				</div>
			</div>

			<div class="furmrowi-field <?php echo isset( $errors['email'] ) ? 'has-error' : ''; ?>">
				<label for="furmrowi-email"><?php esc_html_e( 'Email address for the acknowledgement *', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></label>
				<input type="email" id="furmrowi-email" name="email" value="<?php echo esc_attr( $form['email'] ); ?>" maxlength="254" autocomplete="email" required <?php echo isset( $errors['email'] ) ? 'aria-invalid="true" aria-describedby="furmrowi-error-email"' : ''; ?>>
				<?php if ( isset( $errors['email'] ) ) : ?><span class="furmrowi-error" id="furmrowi-error-email"><?php echo esc_html( $errors['email'] ); ?></span><?php endif; ?>
			</div>

			<?php if ( is_user_logged_in() && $orders ) : ?>
				<fieldset class="furmrowi-choice-group <?php echo isset( $errors['order_mode'] ) ? 'has-error' : ''; ?>">
					<legend><?php esc_html_e( 'How do you identify the contract? *', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></legend>
					<label><input type="radio" name="order_mode" value="account" <?php checked( $form['order_mode'], 'account' ); ?>> <?php esc_html_e( 'Select one of my orders', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></label>
					<label><input type="radio" name="order_mode" value="manual" <?php checked( $form['order_mode'], 'manual' ); ?>> <?php esc_html_e( 'Enter an order number / contract identifier manually', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></label>
				</fieldset>

				<div class="furmrowi-account-order" id="furmrowi-account-order">
					<p class="furmrowi-muted"><?php esc_html_e( 'Order data and products are loaded securely on the server from your account.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></p>
					<div class="furmrowi-order-picker">
						<label class="screen-reader-text" for="furmrowi-order-id"><?php esc_html_e( 'Recent order', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></label>
						<select name="order_id" id="furmrowi-order-id" data-furmrowi-order-select>
							<?php foreach ( $orders as $order ) : ?>
								<option value="<?php echo esc_attr( $order->get_id() ); ?>" <?php selected( (int) $form['order_id'], $order->get_id() ); ?>>
									<?php echo esc_html( sprintf( '#%1$s · %2$s · %3$s · %4$s', $order->get_order_number(), wc_format_datetime( $order->get_date_created(), 'd.m.Y' ), wc_get_order_status_name( $order->get_status() ), wp_strip_all_tags( $order->get_formatted_order_total() ) ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<button type="submit" name="furmrowi_action" value="load_order" class="button furmrowi-secondary" data-furmrowi-load-order><?php esc_html_e( 'Load order products', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></button>
					</div>
					<?php if ( isset( $errors['order_id'] ) ) : ?><span class="furmrowi-error"><?php echo esc_html( $errors['order_id'] ); ?></span><?php endif; ?>
					<?php if ( $selected_order ) : ?>
						<div class="furmrowi-selected-order"><strong><?php
						/* translators: %s: WooCommerce order number. */
						printf( esc_html__( 'Selected order: #%s', 'furmedia-romanian-withdrawal-law-for-woocommerce' ), esc_html( $selected_order->get_order_number() ) );
						?></strong></div>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<input type="hidden" name="order_mode" value="manual">
				<?php if ( ! is_user_logged_in() ) : ?>
					<p class="furmrowi-login-note"><?php esc_html_e( 'You can continue without an account.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?> <a href="<?php echo esc_url( add_query_arg( 'redirect_to', rawurlencode( $return_url ), $login_url ) ); ?>"><?php esc_html_e( 'Sign in to select an order and its products.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></a></p>
				<?php else : ?>
					<p class="furmrowi-muted"><?php esc_html_e( 'No orders were found in this account. Identify the contract manually below.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></p>
				<?php endif; ?>
			<?php endif; ?>

			<div class="furmrowi-manual-order" id="furmrowi-manual-order">
				<div class="furmrowi-field <?php echo isset( $errors['contract_reference'] ) ? 'has-error' : ''; ?>">
					<label for="furmrowi-contract-reference"><?php esc_html_e( 'Order number or contract identifier *', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></label>
					<input type="text" id="furmrowi-contract-reference" name="contract_reference" value="<?php echo esc_attr( $form['contract_reference'] ); ?>" maxlength="128">
					<?php if ( isset( $errors['contract_reference'] ) ) : ?><span class="furmrowi-error"><?php echo esc_html( $errors['contract_reference'] ); ?></span><?php endif; ?>
				</div>
			</div>

			<fieldset class="furmrowi-choice-group <?php echo isset( $errors['scope'] ) ? 'has-error' : ''; ?>">
				<legend><?php esc_html_e( 'Withdrawal scope *', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></legend>
				<label><input type="radio" name="scope" value="full" <?php checked( $form['scope'], 'full' ); ?>> <?php esc_html_e( 'The entire order / contract', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></label>
				<label><input type="radio" name="scope" value="partial" <?php checked( $form['scope'], 'partial' ); ?>> <?php esc_html_e( 'Only selected products and quantities', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></label>
			</fieldset>

			<?php if ( $selected_order && $products ) : ?>
				<div class="furmrowi-account-items" id="furmrowi-account-items">
					<h3 data-furmrowi-account-heading data-full-text="<?php esc_attr_e( 'Products included in the selected order', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?>" data-partial-text="<?php esc_attr_e( 'Select products and quantities', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?>"><?php esc_html_e( 'Products included in the selected order', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></h3>
					<?php foreach ( $products as $furmrowi_item_id => $furmrowi_item ) : ?>
						<div class="furmrowi-account-product" data-furmrowi-account-product>
							<input type="checkbox" id="furmrowi-product-<?php echo esc_attr( $furmrowi_item_id ); ?>" name="account_items[<?php echo esc_attr( $furmrowi_item_id ); ?>][selected]" value="1" <?php checked( $furmrowi_item['selected'] ); ?> data-furmrowi-product-checkbox>
							<label for="furmrowi-product-<?php echo esc_attr( $furmrowi_item_id ); ?>"><strong><?php echo esc_html( $furmrowi_item['name'] ); ?></strong><span><?php
							/* translators: %d: quantity originally ordered. */
							printf( esc_html__( 'Ordered: %d', 'furmedia-romanian-withdrawal-law-for-woocommerce' ), (int) $furmrowi_item['ordered_quantity'] );
							?></span></label>
							<div><label for="furmrowi-qty-<?php echo esc_attr( $furmrowi_item_id ); ?>"><?php esc_html_e( 'Quantity', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></label><input type="number" id="furmrowi-qty-<?php echo esc_attr( $furmrowi_item_id ); ?>" name="account_items[<?php echo esc_attr( $furmrowi_item_id ); ?>][quantity]" value="<?php echo esc_attr( $furmrowi_item['withdrawal_quantity'] ); ?>" min="1" max="<?php echo esc_attr( $furmrowi_item['ordered_quantity'] ); ?>" data-furmrowi-product-quantity></div>
						</div>
					<?php endforeach; ?>
					<?php if ( isset( $errors['account_items'] ) ) : ?><span class="furmrowi-error"><?php echo esc_html( $errors['account_items'] ); ?></span><?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="furmrowi-manual-items" id="furmrowi-manual-items">
				<h3><?php esc_html_e( 'Products and quantities', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></h3>
				<div id="furmrowi-item-list">
					<?php $furmrowi_manual_items = $form['items'] ? $form['items'] : array( array( 'name' => '', 'quantity' => 1 ) ); ?>
					<?php foreach ( $furmrowi_manual_items as $furmrowi_index => $furmrowi_manual_item ) : ?>
						<div class="furmrowi-manual-item" data-furmrowi-item-row>
							<div><label for="furmrowi-item-name-<?php echo esc_attr( $furmrowi_index ); ?>"><?php esc_html_e( 'Product', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></label><input type="text" id="furmrowi-item-name-<?php echo esc_attr( $furmrowi_index ); ?>" name="items[<?php echo esc_attr( $furmrowi_index ); ?>][name]" value="<?php echo esc_attr( $furmrowi_manual_item['name'] ); ?>" maxlength="255" data-furmrowi-item-name></div>
							<div><label for="furmrowi-item-qty-<?php echo esc_attr( $furmrowi_index ); ?>"><?php esc_html_e( 'Quantity', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></label><input type="number" id="furmrowi-item-qty-<?php echo esc_attr( $furmrowi_index ); ?>" name="items[<?php echo esc_attr( $furmrowi_index ); ?>][quantity]" value="<?php echo esc_attr( $furmrowi_manual_item['quantity'] ); ?>" min="1" max="9999" data-furmrowi-item-quantity></div>
							<button type="button" class="button furmrowi-remove" data-furmrowi-remove-item><?php esc_html_e( 'Remove', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button furmrowi-secondary" id="furmrowi-add-item"><?php esc_html_e( 'Add product', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></button>
				<?php if ( isset( $errors['items'] ) ) : ?><span class="furmrowi-error"><?php echo esc_html( $errors['items'] ); ?></span><?php endif; ?>
			</div>

			<div class="furmrowi-field <?php echo isset( $errors['note'] ) ? 'has-error' : ''; ?>">
				<label for="furmrowi-note"><?php esc_html_e( 'Notes (optional)', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></label>
				<textarea id="furmrowi-note" name="note" rows="4" maxlength="2000"><?php echo esc_textarea( $form['note'] ); ?></textarea>
				<?php if ( isset( $errors['note'] ) ) : ?><span class="furmrowi-error"><?php echo esc_html( $errors['note'] ); ?></span><?php endif; ?>
			</div>
		</fieldset>

		<div class="furmrowi-declaration" role="note">
			<strong><?php esc_html_e( 'I hereby give notice that I withdraw from the distance contract identified above.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></strong>
			<p><?php esc_html_e( 'Activating “Confirm withdrawal” submits this unambiguous declaration. The acknowledgement confirms receipt; it does not automatically approve a return or refund.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></p>
		</div>
		<p class="furmrowi-privacy"><?php esc_html_e( 'The data is processed to record, acknowledge and handle this withdrawal declaration.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?> <?php if ( $privacy_url ) : ?><a href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Privacy policy', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></a>.<?php endif; ?> <a href="<?php echo esc_url( $legal_url ); ?>"><?php esc_html_e( 'Withdrawal information', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></a>.</p>
		<button type="submit" name="furmrowi_action" value="submit" class="button alt furmrowi-submit" id="furmrowi-submit" data-processing-text="<?php esc_attr_e( 'Submitting the declaration…', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?>"><?php esc_html_e( 'Confirm withdrawal', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></button>
	</form>
</section>
