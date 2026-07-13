<?php
namespace Foxly\CWFW;

defined( 'ABSPATH' ) || exit;

class Mailer {
	private $settings;
	private $repository;

	public function __construct( Settings $settings, Repository $repository ) {
		$this->settings   = $settings;
		$this->repository = $repository;
	}

	public function build_evidence( array $form, array $items, $submitted_utc, $submitted_local ) {
		$placeholder = Repository::REFERENCE_PLACEHOLDER;
		$scope_label = 'partial' === $form['scope'] ? __( 'Only selected products and quantities', 'contract-withdrawal-free-for-woocommerce' ) : __( 'The entire order / contract', 'contract-withdrawal-free-for-woocommerce' );
		$declaration = sprintf(
			/* translators: 1: customer name, 2: contract reference, 3: scope, 4: local timestamp, 5: withdrawal reference. */
			__( 'I, %1$s, hereby give notice that I withdraw from the distance contract identified by “%2$s”, for: %3$s. This declaration was submitted at %4$s and recorded under reference %5$s.', 'contract-withdrawal-free-for-woocommerce' ),
			trim( $form['firstname'] . ' ' . $form['lastname'] ),
			$form['contract_reference'],
			$scope_label,
			$submitted_local,
			$placeholder
		);
		if ( $items ) {
			$declaration .= "\n" . __( 'Products and quantities', 'contract-withdrawal-free-for-woocommerce' ) . ':';
			foreach ( $items as $item ) {
				$declaration .= "\n- " . $item['name'] . ' × ' . (int) $item['quantity'];
			}
		}
		if ( '' !== $form['note'] ) {
			$declaration .= "\n" . __( 'Notes', 'contract-withdrawal-free-for-woocommerce' ) . ': ' . $form['note'];
		}

		/* translators: %s: withdrawal reference. */
		$subject = sprintf( __( 'Acknowledgement of contract withdrawal — %s', 'contract-withdrawal-free-for-woocommerce' ), $placeholder );
		$text    = __( 'We acknowledge receipt of your contract-withdrawal declaration. The complete declaration and submission details are reproduced below.', 'contract-withdrawal-free-for-woocommerce' ) . "\n\n";
		$text   .= __( 'Withdrawal reference', 'contract-withdrawal-free-for-woocommerce' ) . ': ' . $placeholder . "\n";
		$text   .= __( 'Submitted at', 'contract-withdrawal-free-for-woocommerce' ) . ': ' . $submitted_local . "\n";
		$text   .= __( 'Order or contract reference', 'contract-withdrawal-free-for-woocommerce' ) . ': ' . $form['contract_reference'] . "\n";
		$text   .= __( 'Name', 'contract-withdrawal-free-for-woocommerce' ) . ': ' . trim( $form['firstname'] . ' ' . $form['lastname'] ) . "\n";
		$text   .= __( 'Email', 'contract-withdrawal-free-for-woocommerce' ) . ': ' . $form['email'] . "\n";
		$text   .= __( 'Withdrawal scope', 'contract-withdrawal-free-for-woocommerce' ) . ': ' . $scope_label . "\n\n";
		$text   .= __( 'Declaration submitted', 'contract-withdrawal-free-for-woocommerce' ) . ":\n" . $declaration . "\n\n";
		$text   .= __( 'This message acknowledges receipt. It does not automatically approve a return or refund.', 'contract-withdrawal-free-for-woocommerce' ) . "\n\n";
		$text   .= (string) $this->settings->get( 'business_name' ) . "\n" . home_url( '/' ) . "\n" . (string) $this->settings->get( 'contact_email' );

		$html  = '<div style="font-family:Arial,sans-serif;line-height:1.6;color:#172033;max-width:720px">';
		$html .= '<h1 style="font-size:24px">' . esc_html__( 'Acknowledgement of contract withdrawal', 'contract-withdrawal-free-for-woocommerce' ) . '</h1>';
		$html .= '<p>' . esc_html__( 'We acknowledge receipt of your contract-withdrawal declaration. The complete declaration and submission details are reproduced below.', 'contract-withdrawal-free-for-woocommerce' ) . '</p>';
		$html .= '<table role="presentation" style="border-collapse:collapse;width:100%">';
		$fields = array(
			__( 'Withdrawal reference', 'contract-withdrawal-free-for-woocommerce' ) => $placeholder,
			__( 'Submitted at', 'contract-withdrawal-free-for-woocommerce' ) => $submitted_local,
			__( 'Order or contract reference', 'contract-withdrawal-free-for-woocommerce' ) => $form['contract_reference'],
			__( 'Name', 'contract-withdrawal-free-for-woocommerce' ) => trim( $form['firstname'] . ' ' . $form['lastname'] ),
			__( 'Email', 'contract-withdrawal-free-for-woocommerce' ) => $form['email'],
			__( 'Withdrawal scope', 'contract-withdrawal-free-for-woocommerce' ) => $scope_label,
		);
		foreach ( $fields as $label => $value ) {
			$html .= '<tr><th style="text-align:left;padding:8px;border:1px solid #dce1e7;background:#f8fafc">' . esc_html( $label ) . '</th><td style="padding:8px;border:1px solid #dce1e7">' . esc_html( $value ) . '</td></tr>';
		}
		$html .= '</table><h2 style="font-size:18px">' . esc_html__( 'Declaration submitted', 'contract-withdrawal-free-for-woocommerce' ) . '</h2>';
		$html .= '<div style="white-space:pre-wrap;padding:16px;border-left:4px solid #173faf;background:#f4f7ff">' . esc_html( $declaration ) . '</div>';
		$html .= '<p><small>' . esc_html__( 'This message acknowledges receipt. It does not automatically approve a return or refund.', 'contract-withdrawal-free-for-woocommerce' ) . '</small></p></div>';

		return array(
			'declaration'          => $declaration,
			'confirmation_subject' => $subject,
			'confirmation_text'    => $text,
			'confirmation_html'    => $html,
		);
	}

	public function deliver_initial( array $record ) {
		$status = $this->send( $record['email'], $record['confirmation_subject'], $record['confirmation_html'], $this->settings->notification_email() ) ? 'sent' : 'failed';
		$this->repository->mark_customer_email_status( $record['withdrawal_id'], $status );
		if ( 'failed' === $status ) {
			$this->safe_log( 'customer acknowledgement failed', $record['withdrawal_id'] );
		}
		return $status;
	}

	public function notify_admin( array $record, $customer_email_status ) {
		$recipient = $this->settings->notification_email();
		if ( ! $recipient ) {
			$this->repository->set_admin_email_status( $record['withdrawal_id'], 'not_configured' );
			return 'not_configured';
		}
		/* translators: %s: withdrawal reference. */
		$subject = sprintf( __( 'New contract-withdrawal declaration — %s', 'contract-withdrawal-free-for-woocommerce' ), $record['reference'] );
		/* translators: %s: withdrawal reference. */
		$text  = sprintf( __( 'A new declaration was recorded under reference %s.', 'contract-withdrawal-free-for-woocommerce' ), $record['reference'] ) . "\n\n";
		$text .= __( 'Customer confirmation status', 'contract-withdrawal-free-for-woocommerce' ) . ': ' . $customer_email_status . "\n\n" . $record['declaration'];
		$html  = '<div style="font-family:Arial,sans-serif;line-height:1.6;white-space:pre-wrap">' . nl2br( esc_html( $text ) ) . '</div>';
		$status = $this->send( $recipient, $subject, $html, $record['email'] ) ? 'sent' : 'failed';
		$this->repository->set_admin_email_status( $record['withdrawal_id'], $status );
		if ( 'failed' === $status ) {
			$this->safe_log( 'administrator notification failed', $record['withdrawal_id'] );
		}
		return $status;
	}

	private function send( $to, $subject, $html, $reply_to = '' ) {
		$to = sanitize_email( (string) $to );
		if ( ! $to || strlen( $to ) > 254 ) {
			return false;
		}
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$reply   = sanitize_email( (string) $reply_to );
		if ( $reply ) {
			$headers[] = 'Reply-To: ' . $reply;
		}
		return (bool) wp_mail( $to, wp_strip_all_tags( (string) $subject ), (string) $html, $headers );
	}

	private function safe_log( $message, $withdrawal_id ) {
		do_action( 'cwfw_mail_delivery_error', sanitize_text_field( $message ), absint( $withdrawal_id ) );
	}
}
