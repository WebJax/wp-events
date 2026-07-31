<?php
/**
 * Plugin settings page.
 *
 * @package WPEvents
 */

namespace WPEvents\Admin;

/**
 * Registers and renders the Events settings screen.
 */
class Settings {

	/**
	 * Option name in wp_options.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'wpevents_settings';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'wpevents-settings';

	/**
	 * Whether custom color CSS was already output.
	 *
	 * @var bool
	 */
	private static $color_tokens_printed = false;

	/**
	 * Wire admin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'print_color_tokens' ), 30 );
		add_action( 'wp_head', array( __CLASS__, 'print_color_tokens_fallback' ), 5 );
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'auto_trash_enabled' => true,
			'auto_trash_days'     => 30,
			'color_mode'          => 'theme',
			'color_primary'       => '#007cba',
			'color_accent'        => '#005a87',
		);
	}

	/**
	 * Get merged settings.
	 *
	 * @return array
	 */
	public static function get() {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array_merge( self::defaults(), $stored );
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback if key missing after merge.
	 * @return mixed
	 */
	public static function get_setting( $key, $default = null ) {
		$settings = self::get();

		if ( array_key_exists( $key, $settings ) ) {
			return $settings[ $key ];
		}

		return $default;
	}

	/**
	 * Add submenu under Events.
	 *
	 * @return void
	 */
	public static function add_menu_page() {
		add_submenu_page(
			'edit.php?post_type=event',
			__( 'Events Settings', 'wp-events' ),
			__( 'Settings', 'wp-events' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register Settings API fields.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'wpevents_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section(
			'wpevents_cleanup',
			__( 'Cleanup', 'wp-events' ),
			array( __CLASS__, 'render_cleanup_section' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'auto_trash_enabled',
			__( 'Auto-trash finished events', 'wp-events' ),
			array( __CLASS__, 'render_auto_trash_enabled_field' ),
			self::PAGE_SLUG,
			'wpevents_cleanup'
		);

		add_settings_field(
			'auto_trash_days',
			__( 'Days after end date', 'wp-events' ),
			array( __CLASS__, 'render_auto_trash_days_field' ),
			self::PAGE_SLUG,
			'wpevents_cleanup'
		);

		add_settings_section(
			'wpevents_appearance',
			__( 'Appearance', 'wp-events' ),
			array( __CLASS__, 'render_appearance_section' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'color_mode',
			__( 'Color mode', 'wp-events' ),
			array( __CLASS__, 'render_color_mode_field' ),
			self::PAGE_SLUG,
			'wpevents_appearance'
		);

		add_settings_field(
			'color_primary',
			__( 'Primary color', 'wp-events' ),
			array( __CLASS__, 'render_color_primary_field' ),
			self::PAGE_SLUG,
			'wpevents_appearance'
		);

		add_settings_field(
			'color_accent',
			__( 'Accent color', 'wp-events' ),
			array( __CLASS__, 'render_color_accent_field' ),
			self::PAGE_SLUG,
			'wpevents_appearance'
		);
	}

	/**
	 * Sanitize settings on save.
	 *
	 * @param array $input Raw submitted values.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$output   = $defaults;

		if ( ! is_array( $input ) ) {
			return $output;
		}

		$output['auto_trash_enabled'] = ! empty( $input['auto_trash_enabled'] );

		$days = isset( $input['auto_trash_days'] ) ? absint( $input['auto_trash_days'] ) : $defaults['auto_trash_days'];
		if ( $days < 1 ) {
			$days = 1;
		}
		if ( $days > 3650 ) {
			$days = 3650;
		}
		$output['auto_trash_days'] = $days;

		$mode = isset( $input['color_mode'] ) ? sanitize_key( $input['color_mode'] ) : 'theme';
		$output['color_mode'] = in_array( $mode, array( 'theme', 'custom' ), true ) ? $mode : 'theme';

		$primary = isset( $input['color_primary'] ) ? sanitize_hex_color( $input['color_primary'] ) : '';
		$output['color_primary'] = $primary ? $primary : $defaults['color_primary'];

		$accent = isset( $input['color_accent'] ) ? sanitize_hex_color( $input['color_accent'] ) : '';
		$output['color_accent'] = $accent ? $accent : $defaults['color_accent'];

		return $output;
	}

	/**
	 * Enqueue color picker on the settings screen.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public static function enqueue_assets( $hook_suffix ) {
		if ( 'event_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		$script = <<<'JS'
(function ($) {
	function toggleColorFields() {
		var isCustom = $('input[name="wpevents_settings[color_mode]"]:checked').val() === 'custom';
		$('.wpevents-color-field-row').closest('tr').toggle(isCustom);
	}

	$(function () {
		$('.wpevents-color-field').wpColorPicker();
		$('input[name="wpevents_settings[color_mode]"]').on('change', toggleColorFields);
		toggleColorFields();
	});
})(jQuery);
JS;

		wp_add_inline_script( 'wp-color-picker', $script );
	}

	/**
	 * Build custom color token CSS.
	 *
	 * @return string
	 */
	private static function get_color_token_css() {
		$settings = self::get();

		return sprintf(
			':root{--wpevents-color-primary:%1$s;--wpevents-color-accent:%2$s;--primary-color:%1$s;}',
			esc_attr( $settings['color_primary'] ),
			esc_attr( $settings['color_accent'] )
		);
	}

	/**
	 * Attach CSS color tokens to enqueued frontend styles.
	 *
	 * @return void
	 */
	public static function print_color_tokens() {
		$settings = self::get();

		if ( 'custom' !== $settings['color_mode'] || self::$color_tokens_printed ) {
			return;
		}

		$css = self::get_color_token_css();

		if ( wp_style_is( 'wp-events-frontend', 'enqueued' ) || wp_style_is( 'wp-events-frontend', 'registered' ) ) {
			wp_add_inline_style( 'wp-events-frontend', $css );
			self::$color_tokens_printed = true;
		}

		if ( wp_style_is( 'wpevents-frontend-styles', 'enqueued' ) || wp_style_is( 'wpevents-frontend-styles', 'registered' ) ) {
			wp_add_inline_style( 'wpevents-frontend-styles', $css );
			self::$color_tokens_printed = true;
		}
	}

	/**
	 * Fallback: print tokens in head if no style handle was available yet.
	 *
	 * @return void
	 */
	public static function print_color_tokens_fallback() {
		$settings = self::get();

		if ( 'custom' !== $settings['color_mode'] || self::$color_tokens_printed ) {
			return;
		}

		printf(
			'<style id="wpevents-color-tokens">%s</style>' . "\n",
			self::get_color_token_css() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values sanitized as hex.
		);
		self::$color_tokens_printed = true;
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'wpevents_settings_group' );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Cleanup section description.
	 *
	 * @return void
	 */
	public static function render_cleanup_section() {
		echo '<p>' . esc_html__( 'Events are moved to Trash a set number of days after their end date.', 'wp-events' ) . '</p>';
	}

	/**
	 * Appearance section description.
	 *
	 * @return void
	 */
	public static function render_appearance_section() {
		echo '<p>' . esc_html__( 'Choose whether event UI colors follow the theme or use plugin tokens.', 'wp-events' ) . '</p>';
	}

	/**
	 * Render auto-trash enable checkbox.
	 *
	 * @return void
	 */
	public static function render_auto_trash_enabled_field() {
		$settings = self::get();
		?>
		<label for="wpevents_auto_trash_enabled">
			<input
				type="checkbox"
				id="wpevents_auto_trash_enabled"
				name="<?php echo esc_attr( self::OPTION_KEY ); ?>[auto_trash_enabled]"
				value="1"
				<?php checked( ! empty( $settings['auto_trash_enabled'] ) ); ?>
			/>
			<?php esc_html_e( 'Automatically move finished events to Trash', 'wp-events' ); ?>
		</label>
		<?php
	}

	/**
	 * Render days field.
	 *
	 * @return void
	 */
	public static function render_auto_trash_days_field() {
		$settings = self::get();
		?>
		<input
			type="number"
			id="wpevents_auto_trash_days"
			name="<?php echo esc_attr( self::OPTION_KEY ); ?>[auto_trash_days]"
			value="<?php echo esc_attr( (string) $settings['auto_trash_days'] ); ?>"
			min="1"
			max="3650"
			step="1"
			class="small-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Number of days after event_end (or event_start if end is missing) before trashing. Default: 30.', 'wp-events' ); ?>
		</p>
		<?php
	}

	/**
	 * Render color mode radios.
	 *
	 * @return void
	 */
	public static function render_color_mode_field() {
		$settings = self::get();
		$mode     = $settings['color_mode'];
		?>
		<fieldset>
			<label>
				<input
					type="radio"
					name="<?php echo esc_attr( self::OPTION_KEY ); ?>[color_mode]"
					value="theme"
					<?php checked( $mode, 'theme' ); ?>
				/>
				<?php esc_html_e( 'Follow theme', 'wp-events' ); ?>
			</label>
			<br />
			<label>
				<input
					type="radio"
					name="<?php echo esc_attr( self::OPTION_KEY ); ?>[color_mode]"
					value="custom"
					<?php checked( $mode, 'custom' ); ?>
				/>
				<?php esc_html_e( 'Custom palette', 'wp-events' ); ?>
			</label>
		</fieldset>
		<?php
	}

	/**
	 * Render primary color field.
	 *
	 * @return void
	 */
	public static function render_color_primary_field() {
		$settings = self::get();
		?>
		<div class="wpevents-color-field-row">
			<input
				type="text"
				class="wpevents-color-field"
				id="wpevents_color_primary"
				name="<?php echo esc_attr( self::OPTION_KEY ); ?>[color_primary]"
				value="<?php echo esc_attr( $settings['color_primary'] ); ?>"
				data-default-color="#007cba"
			/>
		</div>
		<?php
	}

	/**
	 * Render accent color field.
	 *
	 * @return void
	 */
	public static function render_color_accent_field() {
		$settings = self::get();
		?>
		<div class="wpevents-color-field-row">
			<input
				type="text"
				class="wpevents-color-field"
				id="wpevents_color_accent"
				name="<?php echo esc_attr( self::OPTION_KEY ); ?>[color_accent]"
				value="<?php echo esc_attr( $settings['color_accent'] ); ?>"
				data-default-color="#005a87"
			/>
		</div>
		<?php
	}
}
