<?php defined( 'ABSPATH' ) || exit; ?>
<section class="cwfw-wrap cwfw-success" aria-labelledby="cwfw-success-heading">
	<h2 id="cwfw-success-heading"><?php esc_html_e( 'Your withdrawal declaration was recorded', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></h2>
	<p><?php esc_html_e( 'Keep the withdrawal reference and the acknowledgement sent by email.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></p>
	<dl>
		<dt><?php esc_html_e( 'Withdrawal reference', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></dt><dd><strong><?php echo esc_html( $record['reference'] ); ?></strong></dd>
		<dt><?php esc_html_e( 'Submitted at', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></dt><dd><?php echo esc_html( $frontend->format_date( $record['date_submitted_utc'] ) ); ?></dd>
		<dt><?php esc_html_e( 'Email acknowledgement', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></dt><dd><?php echo esc_html( $frontend->email_status_label( $record['email_status'] ) ); ?></dd>
	</dl>
	<?php if ( 'sent' === $record['email_status'] ) : ?>
		<p class="cwfw-notice cwfw-notice-success"><?php esc_html_e( 'A complete copy of the declaration, including its date and time, was sent to the email address provided.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></p>
	<?php else : ?>
		<p class="cwfw-notice cwfw-notice-warning"><?php esc_html_e( 'The declaration was saved, but the email could not be delivered immediately. Download the complete evidence now and contact the merchant if necessary.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></p>
	<?php endif; ?>
	<div class="cwfw-actions">
		<a class="button alt" href="<?php echo esc_url( $frontend->evidence_url( $record['withdrawal_id'] ) ); ?>"><?php esc_html_e( 'Download submission evidence', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></a>
	</div>
</section>
