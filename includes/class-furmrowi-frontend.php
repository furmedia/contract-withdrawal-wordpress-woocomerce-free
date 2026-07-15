<?php
namespace Furmedia\Furmrowi;

defined( 'ABSPATH' ) || exit;

class Frontend {
	private $settings;
	private $repository;
	private $security;
	private $mailer;
	private $errors = array();
	private $form = array();

	public function __construct( Settings $settings, Repository $repository, Security $security, Mailer $mailer ) {
		$this->settings   = $settings;
		$this->repository = $repository;
		$this->security   = $security;
		$this->mailer     = $mailer;
	}

	public function register_hooks() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'template_redirect', array( $this, 'handle_request' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_style' ) );
		add_action( 'wp_footer', array( $this, 'footer_link' ) );

		add_shortcode( 'furmrowi_form', array( $this, 'shortcode_form' ) );
		add_shortcode( 'furmrowi_legal_notice', array( $this, 'shortcode_legal' ) );
		add_shortcode( 'furmrowi_link', array( $this, 'shortcode_link' ) );
	}

	public function register_blocks() {
		wp_register_script(
			'furmrowi-blocks',
			FURMROWI_URL . 'assets/js/blocks.js',
			array( 'wp-blocks', 'wp-element', 'wp-i18n' ),
			FURMROWI_VERSION,
			true
		);
		wp_localize_script(
			'furmrowi-blocks',
			'furmrowiBlocksI18n',
			array(
				'formTitle'          => __( 'Withdrawal form', 'furmedia-romanian-withdrawal-law-for-woocommerce' ),
				'formDescription'    => __( 'Displays the complete online contract-withdrawal form.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ),
				'legalTitle'         => __( 'Withdrawal information', 'furmedia-romanian-withdrawal-law-for-woocommerce' ),
				'legalDescription'   => __( 'Displays the configured withdrawal information.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ),
				'linkTitle'          => __( 'Withdrawal link', 'furmedia-romanian-withdrawal-law-for-woocommerce' ),
				'linkDescription'    => __( 'Displays the permanent withdrawal link.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ),
			)
		);
		$blocks = array(
			'furmedia/romanian-withdrawal-law-form'  => array( $this, 'shortcode_form' ),
			'furmedia/romanian-withdrawal-law-legal' => array( $this, 'shortcode_legal' ),
			'furmedia/romanian-withdrawal-law-link'  => array( $this, 'shortcode_link' ),
		);
		foreach ( $blocks as $name => $callback ) {
			register_block_type(
				$name,
				array(
					'api_version'     => 3,
					'editor_script'   => 'furmrowi-blocks',
					'render_callback' => $callback,
				)
			);
		}
	}

	public function handle_request() {
		if ( isset( $_GET['furmrowi_evidence'] ) ) {
			$this->download_evidence();
		}
		if ( 'POST' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_METHOD'] ) ) : '' ) || empty( $_POST['furmrowi_action'] ) ) {
			return;
		}
		if ( ! $this->settings->get( 'enabled' ) || ! $this->settings->is_ready() ) {
			$this->errors['warning'] = __( 'The online withdrawal function is not available yet. Please contact the merchant using the published contact details.', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['furmrowi_action'] ) );
		$nonce  = $this->posted_scalar( 'furmrowi_nonce' );
		if ( ! wp_verify_nonce( $nonce, 'furmrowi_submit' ) ) {
			$this->errors['warning'] = __( 'The form session expired. Review the details and try again.', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
			return;
		}
		$this->form = $this->read_form();
		if ( 'load_order' === $action ) {
			if ( ! is_user_logged_in() || 'account' !== $this->form['order_mode'] || ! $this->owned_order( $this->form['order_id'] ) ) {
				$this->errors['warning'] = __( 'The selected order is no longer available in this account. Select it again or use manual identification.', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
			}
			return;
		}

		$idempotency = $this->posted_scalar( 'furmrowi_idempotency' );
		if ( ! $this->security->validate_submission_tokens( $nonce, $idempotency ) ) {
			$this->errors['warning'] = __( 'The form session expired. Review the details and try again.', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
			return;
		}
		if ( '' !== trim( $this->posted_scalar( 'furmrowi_check_7f31' ) ) ) {
			$this->errors['warning'] = __( 'The request could not be processed. Please try again.', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
			return;
		}
		if ( $this->security->is_session_rate_limited() ) {
			$this->errors['warning'] = __( 'Several declarations were submitted in a short period. Please wait and try again, or use the email address shown in the withdrawal information.', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
			return;
		}

		$idempotency_hash = $this->security->idempotency_hash( $idempotency );
		$existing         = $this->repository->get_by_idempotency( $idempotency_hash );
		if ( $existing && (int) $existing['customer_id'] === get_current_user_id() ) {
			$this->security->remember_success( $existing );
			$this->redirect_success();
		}

		$validated = $this->validate_form( $this->form );
		if ( ! empty( $validated['errors'] ) ) {
			$this->errors = $validated['errors'];
			return;
		}
		if ( ! $this->security->reserve_persistent_slot() ) {
			$this->errors['warning'] = __( 'Several declarations were submitted in a short period. Please wait and try again, or use the email address shown in the withdrawal information.', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
			return;
		}

		$submitted_utc   = gmdate( 'Y-m-d H:i:s' );
		$submitted_local = $this->format_date( $submitted_utc );
		$evidence        = $this->mailer->build_evidence( $this->form, $validated['items'], $submitted_utc, $submitted_local );
		try {
			$record = $this->repository->create(
				array_merge(
					$evidence,
					array(
						'order_id'               => $validated['order_id'],
						'customer_id'            => get_current_user_id(),
						'language_code'          => determine_locale(),
						'contract_reference'     => $this->form['contract_reference'],
						'firstname'              => $this->form['firstname'],
						'lastname'               => $this->form['lastname'],
						'email'                  => $this->form['email'],
						'scope'                  => $this->form['scope'],
						'items'                  => wp_json_encode( $validated['items'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
						'note'                   => $this->form['note'],
						'module_version'         => FURMROWI_VERSION,
						'legal_template_version' => (string) $this->settings->get( 'legal_template_version' ),
						'settings_snapshot'      => wp_json_encode( $this->settings->snapshot(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
						'idempotency_hash'       => $idempotency_hash,
						'date_submitted_utc'     => $submitted_utc,
					)
				)
			);
		} catch ( \Throwable $exception ) {
			$record = $this->repository->get_by_idempotency( $idempotency_hash );
			if ( ! $record || (int) $record['customer_id'] !== get_current_user_id() ) {
				$this->errors['warning'] = __( 'The declaration could not be stored safely. Please try again or contact the merchant.', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
				return;
			}
			// A concurrent request finalized the same one-time submission.
			// Reuse its evidence without sending a second confirmation.
			$this->security->remember_success( $record );
			$this->redirect_success();
		}
		try {
			do_action( 'furmrowi_withdrawal_recorded', $record );
		} catch ( \Throwable $integration_exception ) {
			try {
				do_action( 'furmrowi_integration_error', 'furmrowi_withdrawal_recorded', sanitize_text_field( $integration_exception->getMessage() ) );
			} catch ( \Throwable $ignored_exception ) {
				unset( $ignored_exception );
			}
		}

		$email_status = $this->mailer->deliver_initial( $record );
		$record       = $this->repository->get( $record['withdrawal_id'] );
		$this->mailer->notify_admin( $record, $email_status );
		$record = $this->repository->get( $record['withdrawal_id'] );
		try {
			do_action( 'furmrowi_withdrawal_processed', $record, $email_status );
		} catch ( \Throwable $integration_exception ) {
			try {
				do_action( 'furmrowi_integration_error', 'furmrowi_withdrawal_processed', sanitize_text_field( $integration_exception->getMessage() ) );
			} catch ( \Throwable $ignored_exception ) {
				unset( $ignored_exception );
			}
		}
		$this->security->remember_submission();
		$this->security->rotate_idempotency_token();
		$this->security->remember_success( $record );
		$this->redirect_success();
	}

	public function shortcode_form( $atts = array() ) {
		if ( ! $this->settings->get( 'enabled' ) ) {
			return current_user_can( 'manage_woocommerce' ) ? '<div class="furmrowi-notice furmrowi-notice-warning">' . esc_html__( 'The withdrawal function is disabled. Configure and enable it under WooCommerce → Withdrawal settings.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ) . '</div>' : '';
		}
		if ( ! $this->settings->is_ready() ) {
			return '<div class="furmrowi-notice furmrowi-notice-error">' . esc_html__( 'The merchant contact details required for this function are not configured.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ) . '</div>';
		}
		$this->enqueue_assets();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only success routing bound to server-side session evidence.
		if ( isset( $_GET['furmrowi_success'] ) ) {
			$success = $this->security->success();
			if ( $success ) {
				$record = $this->repository->get( $success['withdrawal_id'] );
				if ( $record ) {
					return $this->template( 'success.php', array( 'record' => $record ) );
				}
			}
		}

		$form = $this->form ? $this->form : $this->default_form();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only order preselection is followed by an ownership check.
		if ( ! $this->form && isset( $_GET['furmrowi_order_id'] ) && is_user_logged_in() ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only order preselection is followed by an ownership check.
			$order_id = absint( $_GET['furmrowi_order_id'] );
			if ( $this->owned_order( $order_id ) ) {
				$form['order_mode'] = 'account';
				$form['order_id']   = $order_id;
			}
		}
		$orders = $this->customer_orders();
		if ( is_user_logged_in() && ! $form['order_id'] && $orders ) {
			$form['order_id'] = $orders[0]->get_id();
		}
		if ( is_user_logged_in() && ! $orders ) {
			$form['order_mode'] = 'manual';
		}
		$selected_order = 'account' === $form['order_mode'] ? $this->owned_order( $form['order_id'] ) : null;
		$products       = $selected_order ? $this->order_items_for_view( $selected_order, $form ) : array();
		return $this->template(
			'form.php',
			array(
				'form'            => $form,
				'errors'          => $this->errors,
				'orders'          => $orders,
				'selected_order'  => $selected_order,
				'products'        => $products,
				'nonce'           => wp_create_nonce( 'furmrowi_submit' ),
				'idempotency'     => $this->security->ensure_idempotency_token(),
				'privacy_url'     => $this->privacy_url(),
				'legal_url'       => $this->settings->legal_url(),
				'login_url'       => wc_get_page_permalink( 'myaccount' ),
				'return_url'      => $this->current_url(),
			)
		);
	}

	public function shortcode_legal( $atts = array() ) {
		if ( ! $this->settings->get( 'enabled' ) ) {
			return '';
		}
		$this->enqueue_assets();
		$atts = shortcode_atts( array( 'full' => 'yes' ), (array) $atts, 'furmrowi_legal_notice' );
		return $this->template( 'legal.php', array( 'full' => 'yes' === strtolower( (string) $atts['full'] ) ) );
	}

	public function shortcode_link( $atts = array() ) {
		if ( ! $this->settings->get( 'enabled' ) ) {
			return '';
		}
		$atts  = shortcode_atts( array( 'label' => __( 'Withdraw from the contract here', 'furmedia-romanian-withdrawal-law-for-woocommerce' ), 'class' => 'furmrowi-link' ), (array) $atts, 'furmrowi_link' );
		$class = implode( ' ', array_map( 'sanitize_html_class', preg_split( '/\s+/', (string) $atts['class'], -1, PREG_SPLIT_NO_EMPTY ) ) );
		return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $this->settings->form_url() ) . '">' . esc_html( $atts['label'] ) . '</a>';
	}

	public function footer_link() {
		if ( ! is_admin() && $this->settings->get( 'enabled' ) && $this->settings->get( 'footer_link_enabled' ) ) {
			echo '<div class="furmrowi-footer-access"><a href="' . esc_url( $this->settings->form_url() ) . '">' . esc_html__( 'Withdraw from the contract here', 'furmedia-romanian-withdrawal-law-for-woocommerce' ) . '</a></div>';
		}
	}

	public function enqueue_public_style() {
		if ( ! is_admin() && $this->settings->get( 'enabled' ) ) {
			wp_enqueue_style( 'furmrowi-frontend', FURMROWI_URL . 'assets/css/frontend.css', array(), FURMROWI_VERSION );
		}
	}

	public function evidence_url( $withdrawal_id ) {
		return wp_nonce_url( add_query_arg( 'furmrowi_evidence', absint( $withdrawal_id ), home_url( '/' ) ), 'furmrowi_evidence_' . absint( $withdrawal_id ) );
	}

	public function email_status_label( $status ) {
		$labels = array(
			'pending'   => __( 'Pending', 'furmedia-romanian-withdrawal-law-for-woocommerce' ),
			'sent'      => __( 'Sent', 'furmedia-romanian-withdrawal-law-for-woocommerce' ),
			'failed'    => __( 'Delivery failed — contact the merchant', 'furmedia-romanian-withdrawal-law-for-woocommerce' ),
		);
		return isset( $labels[ $status ] ) ? $labels[ $status ] : (string) $status;
	}

	public function format_date( $utc ) {
		try {
			$date = new \DateTimeImmutable( (string) $utc, new \DateTimeZone( 'UTC' ) );
			$date = $date->setTimezone( new \DateTimeZone( (string) $this->settings->get( 'timezone', 'Europe/Bucharest' ) ) );
			return $date->format( 'd.m.Y H:i:s' ) . ' (' . $date->getTimezone()->getName() . ')';
		} catch ( \Exception $exception ) {
			return (string) $utc . ' UTC';
		}

	}

	private function validate_form( array &$form ) {
		$errors = array();
		if ( $this->length( $form['firstname'] ) < 1 || $this->length( $form['firstname'] ) > 64 ) {
			$errors['firstname'] = __( 'First name must contain between 1 and 64 characters.', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
		}
		if ( $this->length( $form['lastname'] ) < 1 || $this->length( $form['lastname'] ) > 64 ) {
			$errors['lastname'] = __( 'Last name must contain between 1 and 64 characters.', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
		}
		if ( $this->length( $form['email'] ) > 254 || ! is_email( $form['email'] ) ) {
			$errors['email'] = __( 'Enter a valid email address.', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
		}
		if ( ! in_array( $form['scope'], array( 'full', 'partial' ), true ) ) {
			$errors['scope'] = __( 'Select the scope of the withdrawal.', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
		}
		if ( $this->length( $form['note'] ) > 2000 ) {
			$errors['note'] = __( 'Notes may contain at most 2,000 characters.', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
		}

		$items    = array();
		$order_id = 0;
		if ( 'account' === $form['order_mode'] ) {
			$order = $this->owned_order( $form['order_id'] );
			if ( ! $order ) {
				$errors['order_id'] = __( 'The selected order is not available in this account.', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
			} else {
				$order_id                   = $order->get_id();
				$form['contract_reference'] = (string) $order->get_order_number();
				$canonical                  = $this->canonical_order_items( $order );
				if ( 'full' === $form['scope'] ) {
					$items = array_values( $canonical );
				} else {
					foreach ( $form['account_items'] as $item_id => $selection ) {
						if ( empty( $selection['selected'] ) ) {
							continue;
						}
						if ( ! isset( $canonical[ $item_id ] ) || $selection['quantity'] < 1 || $selection['quantity'] > $canonical[ $item_id ]['ordered_quantity'] ) {
							$errors['account_items'] = __( 'The selected products or quantities no longer match the order.', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
							break;
						}
						$item             = $canonical[ $item_id ];
						$item['quantity'] = (int) $selection['quantity'];
						$items[]          = $item;
					}
					if ( ! $items && empty( $errors['account_items'] ) ) {
						$errors['account_items'] = __( 'Select at least one product for a partial withdrawal.', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
					}
				}
			}
		} elseif ( 'manual' === $form['order_mode'] ) {
			if ( $this->length( $form['contract_reference'] ) < 1 || $this->length( $form['contract_reference'] ) > 128 ) {
				$errors['contract_reference'] = __( 'Enter an order number or contract identifier of at most 128 characters.', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
			}
			$order_id = $this->match_manual_order( $form['contract_reference'], $form['email'] );
			if ( 'partial' === $form['scope'] ) {
				foreach ( array_slice( $form['items'], 0, 20 ) as $item ) {
					if ( $this->length( $item['name'] ) < 1 || $this->length( $item['name'] ) > 255 || $item['quantity'] < 1 || $item['quantity'] > 9999 ) {
						$errors['items'] = __( 'Each product must have a name and a quantity between 1 and 9,999.', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
						break;
					}
					$items[] = array( 'name' => $item['name'], 'quantity' => (int) $item['quantity'] );
				}
				if ( ! $items && empty( $errors['items'] ) ) {
					$errors['items'] = __( 'Add at least one product for a partial withdrawal.', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
				}
			}
		} else {
			$errors['order_mode'] = __( 'Choose how the contract is identified.', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
		}
		return array( 'errors' => $errors, 'items' => $items, 'order_id' => $order_id );
	}

	private function read_form() {
		$form = array(
			'firstname'          => $this->posted_text( 'firstname' ),
			'lastname'           => $this->posted_text( 'lastname' ),
			'email'              => strtolower( sanitize_email( $this->posted_scalar( 'email' ) ) ),
			'contract_reference' => $this->posted_text( 'contract_reference' ),
			'order_mode'         => sanitize_key( $this->posted_scalar( 'order_mode' ) ),
			'order_id'           => absint( $this->posted_scalar( 'order_id' ) ),
			'scope'              => sanitize_key( $this->posted_scalar( 'scope' ) ),
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- handle_request() verifies furmrowi_nonce before parsing form values.
			'note'               => isset( $_POST['note'] ) && is_scalar( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '',
			'items'              => array(),
			'account_items'      => array(),
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified first; bounded nested values are sanitized below.
		$raw_items = isset( $_POST['items'] ) && is_array( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : array();
		foreach ( array_slice( $raw_items, 0, 20 ) as $item ) {
			if ( is_array( $item ) ) {
				$form['items'][] = array(
					'name'     => isset( $item['name'] ) && is_scalar( $item['name'] ) ? sanitize_text_field( $item['name'] ) : '',
					'quantity' => isset( $item['quantity'] ) && is_scalar( $item['quantity'] ) ? (int) $item['quantity'] : 0,
				);
			}
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified first; bounded nested values are sanitized below.
		$account_items = isset( $_POST['account_items'] ) && is_array( $_POST['account_items'] ) ? wp_unslash( $_POST['account_items'] ) : array();
		foreach ( $account_items as $item_id => $selection ) {
			if ( ctype_digit( (string) $item_id ) && is_array( $selection ) ) {
				$form['account_items'][ (int) $item_id ] = array(
					'selected' => isset( $selection['selected'] ) && '1' === (string) $selection['selected'],
					'quantity' => isset( $selection['quantity'] ) ? (int) $selection['quantity'] : 0,
				);
			}
		}
		return $form;
	}

	private function default_form() {
		$user = wp_get_current_user();
		return array(
			'firstname'          => $user->exists() ? ( $user->first_name ? $user->first_name : $user->display_name ) : '',
			'lastname'           => $user->exists() ? $user->last_name : '',
			'email'              => $user->exists() ? $user->user_email : '',
			'contract_reference' => '',
			'order_mode'         => $user->exists() ? 'account' : 'manual',
			'order_id'           => 0,
			'scope'              => 'full',
			'note'               => '',
			'items'              => array( array( 'name' => '', 'quantity' => 1 ) ),
			'account_items'      => array(),
		);
	}

	private function customer_orders() {
		if ( ! is_user_logged_in() ) {
			return array();
		}
		$statuses = array_values( array_diff( array_keys( wc_get_order_statuses() ), array( 'wc-checkout-draft' ) ) );
		return wc_get_orders(
			array(
				'customer_id' => get_current_user_id(),
				'limit'       => (int) $this->settings->get( 'recent_orders_limit', 25 ),
				'orderby'     => 'date',
				'order'       => 'DESC',
				'status'      => $statuses,
				'return'      => 'objects',
			)
		);
	}

	private function owned_order( $order_id ) {
		if ( ! is_user_logged_in() || ! $order_id ) {
			return null;
		}
		$order = wc_get_order( absint( $order_id ) );
		return $order instanceof \WC_Order && (int) $order->get_customer_id() === get_current_user_id() && 'checkout-draft' !== $order->get_status() ? $order : null;
	}

	private function canonical_order_items( \WC_Order $order ) {
		$result = array();
		foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}
			$name_parts = array();
			$options    = array();
			foreach ( $item->get_formatted_meta_data( '' ) as $meta ) {
				$key     = wp_strip_all_tags( (string) $meta->display_key );
				$value   = html_entity_decode( wp_strip_all_tags( (string) $meta->display_value ), ENT_QUOTES, 'UTF-8' );
				$options[] = array( 'name' => $key, 'value' => $value );
				$name_parts[] = $key . ': ' . $value;
			}
			$name = $item->get_name();
			if ( $name_parts ) {
				$name .= ' — ' . implode( ', ', $name_parts );
			}
			$result[ (int) $item_id ] = array(
				'order_item_id'   => (int) $item_id,
				'product_id'      => (int) $item->get_product_id(),
				'variation_id'    => (int) $item->get_variation_id(),
				'name'            => $name,
				'options'         => $options,
				'quantity'        => (int) $item->get_quantity(),
				'ordered_quantity'=> (int) $item->get_quantity(),
			);
		}
		return $result;
	}

	private function order_items_for_view( \WC_Order $order, array $form ) {
		$items = $this->canonical_order_items( $order );
		foreach ( $items as $item_id => &$item ) {
			$selection          = isset( $form['account_items'][ $item_id ] ) ? $form['account_items'][ $item_id ] : array();
			$item['selected']   = ! empty( $selection['selected'] );
			$item['withdrawal_quantity'] = ! empty( $selection['quantity'] ) ? (int) $selection['quantity'] : $item['ordered_quantity'];
		}
		unset( $item );
		return $items;
	}

	private function match_manual_order( $reference, $email ) {
		if ( ! ctype_digit( (string) $reference ) || strlen( (string) $reference ) > 20 ) {
			return 0;
		}
		$order = wc_get_order( absint( $reference ) );
		if ( ! $order instanceof \WC_Order || 'checkout-draft' === $order->get_status() ) {
			return 0;
		}
		if ( is_user_logged_in() ) {
			return (int) $order->get_customer_id() === get_current_user_id() ? $order->get_id() : 0;
		}
		return strtolower( (string) $order->get_billing_email() ) === strtolower( (string) $email ) ? $order->get_id() : 0;
	}

	private function download_evidence() {
		$id = isset( $_GET['furmrowi_evidence'] ) ? absint( wp_unslash( $_GET['furmrowi_evidence'] ) ) : 0;
		if ( ! $id || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '', 'furmrowi_evidence_' . $id ) ) {
			wp_die( esc_html__( 'Invalid evidence link.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ), '', array( 'response' => 403 ) );
		}
		$record = $this->repository->get( $id );
		if ( ! $record || ! $this->security->can_access_evidence( $record ) ) {
			wp_die( esc_html__( 'Evidence not found.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ), '', array( 'response' => 404 ) );
		}
		$this->private_headers();
		$filename = 'withdrawal-evidence-' . preg_replace( '/[^A-Za-z0-9_-]/', '', $record['reference'] ) . '.txt';
		header( 'Content-Type: text/plain; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo $record['confirmation_text']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- immutable plain-text evidence.
		exit;
	}

	private function template( $file, array $vars ) {
		extract( $vars, EXTR_SKIP );
		$frontend = $this;
		$settings = $this->settings;
		ob_start();
		include FURMROWI_PATH . 'templates/' . $file;
		return ob_get_clean();
	}

	private function enqueue_assets() {
		$this->enqueue_public_style();
		wp_enqueue_script( 'furmrowi-frontend', FURMROWI_URL . 'assets/js/frontend.js', array(), FURMROWI_VERSION, true );
	}

	private function redirect_success() {
		$url = add_query_arg( 'furmrowi_success', '1', $this->safe_return_url() );
		wp_safe_redirect( $url );
		exit;
	}

	private function safe_return_url() {
		$url = $this->posted_scalar( 'furmrowi_return_url' );
		return wp_validate_redirect( $url, $this->settings->form_url() );
	}

	private function current_url() {
		if ( is_singular() ) {
			$url = get_permalink();
			if ( $url ) {
				return $url;
			}
		}
		return $this->settings->form_url();
	}

	private function privacy_url() {
		$id = absint( $this->settings->get( 'privacy_page_id' ) );
		return $id ? (string) get_permalink( $id ) : '';
	}

	private function private_headers() {
		nocache_headers();
		header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0' );
		header( 'X-Robots-Tag: noindex, nofollow' );
	}

	private function posted_scalar( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Scalar request reader; callers verify nonce before any state change.
		return isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( (string) $_POST[ $key ] ) ) : '';
	}

	private function posted_text( $key ) {
		return sanitize_text_field( $this->posted_scalar( $key ) );
	}

	private function length( $value ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( (string) $value, 'UTF-8' ) : strlen( (string) $value );
	}
}
