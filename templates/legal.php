<?php defined( 'ABSPATH' ) || exit; ?>
<?php if ( ! $full ) : ?>
	<aside class="cwfw-legal-notice"><p><?php esc_html_e( 'You may also exercise your right of withdrawal through the dedicated online function. After activating “Confirm withdrawal”, an acknowledgement containing the declaration and its submission date and time will be sent by email without undue delay.', 'contract-withdrawal-free-for-woocommerce' ); ?></p><a class="button" href="<?php echo esc_url( $settings->form_url() ); ?>"><?php esc_html_e( 'Withdraw from the contract here', 'contract-withdrawal-free-for-woocommerce' ); ?></a></aside>
<?php else : ?>
	<section class="cwfw-wrap cwfw-legal">
		<h2><?php esc_html_e( 'Information on the right of withdrawal', 'contract-withdrawal-free-for-woocommerce' ); ?></h2>
		<p><?php
		/* translators: %d: statutory withdrawal period in days. */
		printf( esc_html__( 'You have the right to withdraw from the contract, without giving reasons, within %d days.', 'contract-withdrawal-free-for-woocommerce' ), (int) $settings->get( 'period_days', 14 ) );
		?></p>
		<p><?php esc_html_e( 'For services, the period runs from the date the contract is concluded. For sales contracts, it runs from the day on which you or a third party indicated by you, other than the carrier, acquires physical possession of the goods. For multiple goods delivered separately, the period runs from receipt of the last good; for a good delivered in lots or pieces, from receipt of the last lot or piece; and for regular delivery, from receipt of the first good.', 'contract-withdrawal-free-for-woocommerce' ); ?></p>
		<p><?php esc_html_e( 'To exercise the right, it is sufficient to communicate an unambiguous statement before the period expires, by post or email. You may use the model form below, but using it is not mandatory.', 'contract-withdrawal-free-for-woocommerce' ); ?></p>
		<p><?php esc_html_e( 'You may also submit the declaration through the dedicated online function. After activating “Confirm withdrawal”, an acknowledgement containing the declaration and its submission date and time will be sent by email without undue delay.', 'contract-withdrawal-free-for-woocommerce' ); ?></p>
		<p><?php
		/* translators: %d: statutory withdrawal period in days. */
		printf( esc_html__( 'To meet the %d-day deadline, it is sufficient to send the communication concerning exercise of the right of withdrawal before the period expires.', 'contract-withdrawal-free-for-woocommerce' ), (int) $settings->get( 'period_days', 14 ) );
		?></p>
		<address><strong><?php echo esc_html( $settings->get( 'business_name' ) ); ?></strong><br><?php echo nl2br( esc_html( $settings->get( 'business_address' ) ) ); ?><br><?php echo esc_html( $settings->get( 'contact_email' ) ); ?><?php if ( $settings->get( 'contact_phone' ) ) : ?><br><?php echo esc_html( $settings->get( 'contact_phone' ) ); ?><?php endif; ?></address>
		<h3><?php esc_html_e( 'Consequences of withdrawal', 'contract-withdrawal-free-for-woocommerce' ); ?></h3>
		<p><?php esc_html_e( 'If you withdraw, we will reimburse the amounts received, including the cost of standard delivery, without undue delay and no later than 14 days after being informed. Reimbursement is made using the same payment method unless you expressly agree otherwise, without fees for the consumer.', 'contract-withdrawal-free-for-woocommerce' ); ?></p>
		<p><?php esc_html_e( 'We may withhold reimbursement until we receive the goods or until you supply evidence of having sent them back, whichever is earliest.', 'contract-withdrawal-free-for-woocommerce' ); ?></p>
		<p><?php esc_html_e( 'Send or hand over the goods to the return address below without undue delay and no later than 14 days after communicating the withdrawal. The deadline is met if the goods are sent before the period expires.', 'contract-withdrawal-free-for-woocommerce' ); ?></p>
		<?php if ( $settings->get( 'return_address' ) ) : ?><address><?php echo nl2br( esc_html( $settings->get( 'return_address' ) ) ); ?></address><?php endif; ?>
		<p><?php echo 'professional' === $settings->get( 'return_cost_payer' ) ? esc_html__( 'The trader bears the direct cost of returning the goods.', 'contract-withdrawal-free-for-woocommerce' ) : esc_html__( 'The consumer bears the direct cost of returning the goods.', 'contract-withdrawal-free-for-woocommerce' ); ?> <?php echo esc_html( $settings->get( 'additional_return_cost_info' ) ); ?></p>
		<p><?php esc_html_e( 'You are liable only for diminished value resulting from handling beyond what is necessary to establish the nature, characteristics and functioning of the goods.', 'contract-withdrawal-free-for-woocommerce' ); ?></p>
		<p><?php esc_html_e( 'If you requested that a service begin during the withdrawal period, you owe an amount proportionate to the services supplied until you communicate the withdrawal, compared with the full coverage of the contract.', 'contract-withdrawal-free-for-woocommerce' ); ?></p>
		<p><?php esc_html_e( 'The right of withdrawal does not apply in the cases provided by Article 16 of Romanian Emergency Ordinance no. 34/2014. Depending on the case, these include fully performed services begun with prior express agreement and acknowledgement of loss of the right; personalized goods; goods liable to deteriorate or expire rapidly; sealed health or hygiene goods that were unsealed; inseparably mixed goods; unsealed recordings or software; and digital content whose supply began with prior express agreement and acknowledgement of loss of the right.', 'contract-withdrawal-free-for-woocommerce' ); ?></p>
		<div class="cwfw-actions"><a class="button alt" href="<?php echo esc_url( $settings->form_url() ); ?>"><?php esc_html_e( 'Withdraw from the contract here', 'contract-withdrawal-free-for-woocommerce' ); ?></a></div>
		<p class="cwfw-muted"><?php esc_html_e( 'This software presents merchant-configured information and supports a declaration workflow. It does not determine legal eligibility or replace professional legal review.', 'contract-withdrawal-free-for-woocommerce' ); ?></p>
	</section>
<?php endif; ?>
