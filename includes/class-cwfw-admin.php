<?php
namespace Foxly\CWFW;

defined( 'ABSPATH' ) || exit;

class Admin {
	private $settings;
	private $repository;
	private $frontend;

	public function __construct( Settings $settings, Repository $repository, Frontend $frontend ) {
		$this->settings   = $settings;
		$this->repository = $repository;
		$this->frontend   = $frontend;
	}

	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_cwfw_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_post_cwfw_admin_evidence', array( $this, 'download_evidence' ) );
	}

	public function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Contract withdrawals (Free)', 'contract-withdrawal-free-for-woocommerce' ),
			__( 'Contract withdrawals (Free)', 'contract-withdrawal-free-for-woocommerce' ),
			'manage_woocommerce',
			'cwfw-withdrawals',
			array( $this, 'withdrawals_page' )
		);
		add_submenu_page(
			'woocommerce',
			__( 'Withdrawal settings (Free)', 'contract-withdrawal-free-for-woocommerce' ),
			__( 'Withdrawal settings (Free)', 'contract-withdrawal-free-for-woocommerce' ),
			'manage_woocommerce',
			'cwfw-settings',
			array( $this, 'settings_page' )
		);
	}

	public function enqueue_assets() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin screen routing.
		$page = isset( $_GET['page'] ) && is_scalar( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( in_array( $page, array( 'cwfw-withdrawals', 'cwfw-settings' ), true ) ) {
			wp_enqueue_style( 'cwfw-admin', CWFW_URL . 'assets/css/admin.css', array(), CWFW_VERSION );
		}
	}

	public function withdrawals_page() {
		$this->authorize();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only record navigation.
		$view_id = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0;
		if ( $view_id ) {
			$this->detail_page( $view_id );
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination.
		$page     = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
		$per_page = 20;
		$rows     = $this->repository->admin_rows( $page, $per_page );
		$total    = $this->repository->admin_total();
		?>
		<div class="wrap cwfw-admin">
			<h1><?php esc_html_e( 'Contract withdrawals', 'contract-withdrawal-free-for-woocommerce' ); ?></h1>
			<p><?php esc_html_e( 'Declarations are evidence records. This free edition does not approve eligibility, cancel orders, restock products or issue refunds.', 'contract-withdrawal-free-for-woocommerce' ); ?></p>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Reference', 'contract-withdrawal-free-for-woocommerce' ); ?></th><th><?php esc_html_e( 'Submitted', 'contract-withdrawal-free-for-woocommerce' ); ?></th><th><?php esc_html_e( 'Customer', 'contract-withdrawal-free-for-woocommerce' ); ?></th><th><?php esc_html_e( 'Contract', 'contract-withdrawal-free-for-woocommerce' ); ?></th><th><?php esc_html_e( 'Email', 'contract-withdrawal-free-for-woocommerce' ); ?></th></tr></thead>
				<tbody>
				<?php if ( ! $rows ) : ?><tr><td colspan="5"><?php esc_html_e( 'No withdrawal declarations have been recorded.', 'contract-withdrawal-free-for-woocommerce' ); ?></td></tr><?php endif; ?>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'cwfw-withdrawals', 'view' => (int) $row['withdrawal_id'] ), admin_url( 'admin.php' ) ) ); ?>"><strong><?php echo esc_html( $row['reference'] ); ?></strong></a></td>
						<td><?php echo esc_html( $this->frontend->format_date( $row['date_submitted_utc'] ) ); ?></td>
						<td><?php echo esc_html( trim( $row['firstname'] . ' ' . $row['lastname'] ) ); ?><br><small><?php echo esc_html( $row['email'] ); ?></small></td>
						<td><?php echo esc_html( $row['contract_reference'] ); ?><br><small><?php echo esc_html( 'partial' === $row['scope'] ? __( 'Partial', 'contract-withdrawal-free-for-woocommerce' ) : __( 'Full', 'contract-withdrawal-free-for-woocommerce' ) ); ?></small></td>
						<td><?php echo esc_html( $this->frontend->email_status_label( $row['email_status'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php
			$total_pages = (int) ceil( $total / $per_page );
			if ( $total_pages > 1 ) {
				echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $page, 'total' => $total_pages ) ) ) . '</div></div>';
			}
			?>
		</div>
		<?php
	}

	private function detail_page( $withdrawal_id ) {
		$record = $this->repository->get( $withdrawal_id );
		if ( ! $record ) {
			wp_die( esc_html__( 'Withdrawal record not found.', 'contract-withdrawal-free-for-woocommerce' ), '', array( 'response' => 404 ) );
		}
		$download_url = wp_nonce_url(
			add_query_arg( array( 'action' => 'cwfw_admin_evidence', 'withdrawal_id' => (int) $withdrawal_id ), admin_url( 'admin-post.php' ) ),
			'cwfw_admin_evidence_' . (int) $withdrawal_id
		);
		?>
		<div class="wrap cwfw-admin">
			<h1><?php echo esc_html( $record['reference'] ); ?></h1>
			<p><a href="<?php echo esc_url( add_query_arg( 'page', 'cwfw-withdrawals', admin_url( 'admin.php' ) ) ); ?>">&larr; <?php esc_html_e( 'Back to declarations', 'contract-withdrawal-free-for-woocommerce' ); ?></a></p>
			<div class="cwfw-card"><h2><?php esc_html_e( 'Submission details', 'contract-withdrawal-free-for-woocommerce' ); ?></h2>
				<dl><dt><?php esc_html_e( 'Submitted', 'contract-withdrawal-free-for-woocommerce' ); ?></dt><dd><?php echo esc_html( $this->frontend->format_date( $record['date_submitted_utc'] ) ); ?></dd><dt><?php esc_html_e( 'Customer', 'contract-withdrawal-free-for-woocommerce' ); ?></dt><dd><?php echo esc_html( trim( $record['firstname'] . ' ' . $record['lastname'] ) . ' — ' . $record['email'] ); ?></dd><dt><?php esc_html_e( 'Contract', 'contract-withdrawal-free-for-woocommerce' ); ?></dt><dd><?php echo esc_html( $record['contract_reference'] ); ?></dd><dt><?php esc_html_e( 'Customer email', 'contract-withdrawal-free-for-woocommerce' ); ?></dt><dd><?php echo esc_html( $this->frontend->email_status_label( $record['email_status'] ) ); ?></dd><dt><?php esc_html_e( 'Administrator email', 'contract-withdrawal-free-for-woocommerce' ); ?></dt><dd><?php echo esc_html( $record['admin_email_status'] ); ?></dd></dl>
			</div>
			<div class="cwfw-card"><h2><?php esc_html_e( 'Immutable declaration', 'contract-withdrawal-free-for-woocommerce' ); ?></h2><pre><?php echo esc_html( $record['declaration'] ); ?></pre><p><a class="button" href="<?php echo esc_url( $download_url ); ?>"><?php esc_html_e( 'Download evidence', 'contract-withdrawal-free-for-woocommerce' ); ?></a></p></div>
		</div>
		<?php
	}

	public function settings_page() {
		$this->authorize();
		$settings = $this->settings->all();
		?>
		<div class="wrap cwfw-admin">
			<h1><?php esc_html_e( 'Contract Withdrawal Free settings', 'contract-withdrawal-free-for-woocommerce' ); ?></h1>
			<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect notice flag. ?>
			<?php if ( isset( $_GET['updated'] ) ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'contract-withdrawal-free-for-woocommerce' ); ?></p></div><?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cwfw_save_settings">
				<?php wp_nonce_field( 'cwfw_save_settings' ); ?>
				<div class="cwfw-card"><h2><?php esc_html_e( 'Availability', 'contract-withdrawal-free-for-woocommerce' ); ?></h2>
					<label><input type="checkbox" name="enabled" value="1" <?php checked( $settings['enabled'] ); ?>> <?php esc_html_e( 'Enable the public withdrawal function', 'contract-withdrawal-free-for-woocommerce' ); ?></label><br>
					<label><input type="checkbox" name="footer_link_enabled" value="1" <?php checked( $settings['footer_link_enabled'] ); ?>> <?php esc_html_e( 'Show a permanent footer link', 'contract-withdrawal-free-for-woocommerce' ); ?></label>
				</div>
				<div class="cwfw-card"><h2><?php esc_html_e( 'Merchant details', 'contract-withdrawal-free-for-woocommerce' ); ?></h2>
					<p><label><?php esc_html_e( 'Business name', 'contract-withdrawal-free-for-woocommerce' ); ?><input class="regular-text" type="text" name="business_name" value="<?php echo esc_attr( $settings['business_name'] ); ?>" required></label></p>
					<p><label><?php esc_html_e( 'Business address', 'contract-withdrawal-free-for-woocommerce' ); ?><textarea class="large-text" rows="3" name="business_address" required><?php echo esc_textarea( $settings['business_address'] ); ?></textarea></label></p>
					<p><label><?php esc_html_e( 'Return address', 'contract-withdrawal-free-for-woocommerce' ); ?><textarea class="large-text" rows="3" name="return_address"><?php echo esc_textarea( $settings['return_address'] ); ?></textarea></label></p>
					<p><label><?php esc_html_e( 'Public contact email', 'contract-withdrawal-free-for-woocommerce' ); ?><input class="regular-text" type="email" name="contact_email" value="<?php echo esc_attr( $settings['contact_email'] ); ?>" required></label></p>
					<p><label><?php esc_html_e( 'Notification email', 'contract-withdrawal-free-for-woocommerce' ); ?><input class="regular-text" type="email" name="notification_email" value="<?php echo esc_attr( $settings['notification_email'] ); ?>"></label></p>
					<p><label><?php esc_html_e( 'Contact phone', 'contract-withdrawal-free-for-woocommerce' ); ?><input class="regular-text" type="text" name="contact_phone" value="<?php echo esc_attr( $settings['contact_phone'] ); ?>"></label></p>
				</div>
				<div class="cwfw-card"><h2><?php esc_html_e( 'Withdrawal information', 'contract-withdrawal-free-for-woocommerce' ); ?></h2>
					<p><label><?php esc_html_e( 'Withdrawal period (days)', 'contract-withdrawal-free-for-woocommerce' ); ?><input type="number" min="14" max="365" name="period_days" value="<?php echo esc_attr( $settings['period_days'] ); ?>"></label></p>
					<p><label><?php esc_html_e( 'Direct return cost', 'contract-withdrawal-free-for-woocommerce' ); ?><select name="return_cost_payer"><option value="consumer" <?php selected( $settings['return_cost_payer'], 'consumer' ); ?>><?php esc_html_e( 'Consumer', 'contract-withdrawal-free-for-woocommerce' ); ?></option><option value="professional" <?php selected( $settings['return_cost_payer'], 'professional' ); ?>><?php esc_html_e( 'Trader', 'contract-withdrawal-free-for-woocommerce' ); ?></option></select></label></p>
					<p><label><?php esc_html_e( 'Additional return information', 'contract-withdrawal-free-for-woocommerce' ); ?><textarea class="large-text" rows="3" name="additional_return_cost_info"><?php echo esc_textarea( $settings['additional_return_cost_info'] ); ?></textarea></label></p>
					<p><label><?php esc_html_e( 'Display timezone', 'contract-withdrawal-free-for-woocommerce' ); ?><input class="regular-text" type="text" name="timezone" value="<?php echo esc_attr( $settings['timezone'] ); ?>"></label></p>
				</div>
				<div class="cwfw-card"><h2><?php esc_html_e( 'Pages and privacy', 'contract-withdrawal-free-for-woocommerce' ); ?></h2>
					<?php $this->page_select( 'form_page_id', __( 'Withdrawal form page', 'contract-withdrawal-free-for-woocommerce' ), $settings['form_page_id'] ); ?>
					<?php $this->page_select( 'legal_page_id', __( 'Withdrawal information page', 'contract-withdrawal-free-for-woocommerce' ), $settings['legal_page_id'] ); ?>
					<?php $this->page_select( 'privacy_page_id', __( 'Privacy policy page', 'contract-withdrawal-free-for-woocommerce' ), $settings['privacy_page_id'] ); ?>
				</div>
				<div class="cwfw-card"><h2><?php esc_html_e( 'Placement tools', 'contract-withdrawal-free-for-woocommerce' ); ?></h2><p><code>[cwfw_form]</code> <code>[retragere_din_contract]</code> <code>[cwfw_legal_notice]</code> <code>[cwfw_link]</code></p><p><?php esc_html_e( 'The same form, legal notice and link are available as Gutenberg blocks and classic widgets.', 'contract-withdrawal-free-for-woocommerce' ); ?></p></div>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public function save_settings() {
		$this->authorize();
		check_admin_referer( 'cwfw_save_settings' );
		$input           = array();
		$textarea_fields = array( 'business_address', 'return_address', 'additional_return_cost_info' );
		foreach ( array( 'business_name', 'business_address', 'return_address', 'contact_email', 'notification_email', 'contact_phone', 'period_days', 'return_cost_payer', 'additional_return_cost_info', 'timezone', 'form_page_id', 'legal_page_id', 'privacy_page_id' ) as $key ) {
			if ( ! isset( $_POST[ $key ] ) || ! is_scalar( $_POST[ $key ] ) ) {
				$input[ $key ] = '';
				continue;
			}
			$input[ $key ] = in_array( $key, $textarea_fields, true )
				? sanitize_textarea_field( wp_unslash( (string) $_POST[ $key ] ) )
				: sanitize_text_field( wp_unslash( (string) $_POST[ $key ] ) );
		}
		$input['enabled']             = isset( $_POST['enabled'] ) ? 1 : 0;
		$input['footer_link_enabled'] = isset( $_POST['footer_link_enabled'] ) ? 1 : 0;
		$this->settings->update( $input );
		wp_safe_redirect( add_query_arg( array( 'page' => 'cwfw-settings', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function download_evidence() {
		$this->authorize();
		$id = isset( $_GET['withdrawal_id'] ) ? absint( $_GET['withdrawal_id'] ) : 0;
		check_admin_referer( 'cwfw_admin_evidence_' . $id );
		$record = $this->repository->get( $id );
		if ( ! $record ) {
			wp_die( esc_html__( 'Withdrawal record not found.', 'contract-withdrawal-free-for-woocommerce' ), '', array( 'response' => 404 ) );
		}
		nocache_headers();
		header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0' );
		header( 'X-Robots-Tag: noindex, nofollow' );
		header( 'Content-Type: text/plain; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="withdrawal-evidence-' . preg_replace( '/[^A-Za-z0-9_-]/', '', $record['reference'] ) . '.txt"' );
		echo $record['confirmation_text']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- immutable plain-text evidence.
		exit;
	}

	private function page_select( $name, $label, $selected ) {
		$safe_name = sanitize_key( $name );
		$dropdown  = wp_dropdown_pages(
			array(
				'name'              => esc_attr( $safe_name ),
				'id'                => esc_attr( 'cwfw-' . $safe_name ),
				'selected'          => absint( $selected ),
				'show_option_none'  => esc_html__( '— Select —', 'contract-withdrawal-free-for-woocommerce' ),
				'option_none_value' => 0,
				'echo'              => 0,
			)
		);
		echo '<p><label for="' . esc_attr( 'cwfw-' . $safe_name ) . '">' . esc_html( $label ) . '</label> ';
		if ( is_string( $dropdown ) ) {
			echo wp_kses(
				$dropdown,
				array(
					'select' => array(
						'id'   => true,
						'name' => true,
					),
					'option' => array(
						'selected' => true,
						'value'    => true,
					),
				)
			);
		}
		echo '</p>';
	}

	private function authorize() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage withdrawal records.', 'contract-withdrawal-free-for-woocommerce' ), '', array( 'response' => 403 ) );
		}
	}
}
