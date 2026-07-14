<?php
namespace Furmedia\CWFW;

defined( 'ABSPATH' ) || exit;

class Widgets {
	public static function register() {
		register_widget( __NAMESPACE__ . '\\Form_Widget' );
		register_widget( __NAMESPACE__ . '\\Link_Widget' );
		register_widget( __NAMESPACE__ . '\\Legal_Widget' );
	}
}

abstract class Base_Widget extends \WP_Widget {
	protected function title( $instance ) {
		return isset( $instance['title'] ) ? sanitize_text_field( $instance['title'] ) : '';
	}

	protected function render_title( $args, $instance ) {
		$title = apply_filters( 'widget_title', $this->title( $instance ), $instance, $this->id_base );
		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public function form( $instance ) {
		$title = $this->title( $instance );
		?>
		<p><label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>"></p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array( 'title' => isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '' );
	}
}

class Form_Widget extends Base_Widget {
	public function __construct() {
		parent::__construct( 'cwfw_form_widget', __( 'Withdrawal: Online form', 'furmedia-romanian-withdrawal-law-for-woocommerce' ), array( 'description' => __( 'Displays the complete online contract-withdrawal form.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ) ) );
	}

	public function widget( $args, $instance ) {
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$this->render_title( $args, $instance );
		echo cwfw()->frontend()->shortcode_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

class Link_Widget extends Base_Widget {
	public function __construct() {
		parent::__construct( 'cwfw_link_widget', __( 'Withdrawal: Permanent link', 'furmedia-romanian-withdrawal-law-for-woocommerce' ), array( 'description' => __( 'Displays a visible link to the online withdrawal function.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ) ) );
	}

	public function widget( $args, $instance ) {
		$label = isset( $instance['label'] ) && $instance['label'] ? $instance['label'] : __( 'Withdraw from the contract here', 'furmedia-romanian-withdrawal-law-for-woocommerce' );
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$this->render_title( $args, $instance );
		echo cwfw()->frontend()->shortcode_link( array( 'label' => $label ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function form( $instance ) {
		parent::form( $instance );
		$label = isset( $instance['label'] ) ? sanitize_text_field( $instance['label'] ) : '';
		?><p><label for="<?php echo esc_attr( $this->get_field_id( 'label' ) ); ?>"><?php esc_html_e( 'Link label', 'furmedia-romanian-withdrawal-law-for-woocommerce' ); ?></label><input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'label' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'label' ) ); ?>" type="text" value="<?php echo esc_attr( $label ); ?>"></p><?php
	}

	public function update( $new_instance, $old_instance ) {
		$value          = parent::update( $new_instance, $old_instance );
		$value['label'] = isset( $new_instance['label'] ) ? sanitize_text_field( $new_instance['label'] ) : '';
		return $value;
	}
}

class Legal_Widget extends Base_Widget {
	public function __construct() {
		parent::__construct( 'cwfw_legal_widget', __( 'Withdrawal: Legal notice', 'furmedia-romanian-withdrawal-law-for-woocommerce' ), array( 'description' => __( 'Displays a short notice and a link to the withdrawal function.', 'furmedia-romanian-withdrawal-law-for-woocommerce' ) ) );
	}

	public function widget( $args, $instance ) {
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$this->render_title( $args, $instance );
		echo cwfw()->frontend()->shortcode_legal( array( 'full' => 'no' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
