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
		add_action( 'init', array( __CLASS__, 'maybe_flush_rewrites' ), 99 );
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
			'archive_slug'       => 'arrangementerne',
			'single_slug'        => 'arrangement',
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
	 * Archive slug (plural), used for the events listing.
	 *
	 * @return string
	 */
	public static function get_archive_slug() {
		$defaults = self::defaults();

		return self::clean_slug( self::get_setting( 'archive_slug', '' ), $defaults['archive_slug'] );
	}

	/**
	 * Single event slug (singular).
	 *
	 * @return string
	 */
	public static function get_single_slug() {
		$defaults = self::defaults();

		return self::clean_slug( self::get_setting( 'single_slug', '' ), $defaults['single_slug'] );
	}

	/**
	 * Turn a submitted value into a permalink slug.
	 *
	 * @param mixed  $value    Raw value.
	 * @param string $fallback Used when the value is empty after sanitizing.
	 * @return string
	 */
	private static function clean_slug( $value, $fallback ) {
		$slug = sanitize_title( is_string( $value ) ? $value : '' );

		if ( '' === $slug ) {
			$slug = sanitize_title( $fallback );
		}

		return $slug;
	}

	/**
	 * Flush rewrite rules once when the public slugs change.
	 *
	 * Post types are registered earlier on init, so the flush sees the current slugs.
	 *
	 * @return void
	 */
	public static function maybe_flush_rewrites() {
		$current = array(
			'archive_slug' => self::get_archive_slug(),
			'single_slug'  => self::get_single_slug(),
		);

		$stored = get_option( 'wpevents_rewrite_slugs', array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		if ( $stored === $current ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( 'wpevents_rewrite_slugs', $current, false );
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
			'wpevents_permalinks',
			__( 'Permalinks', 'wp-events' ),
			array( __CLASS__, 'render_permalinks_section' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'archive_slug',
			__( 'Short link title', 'wp-events' ),
			array( __CLASS__, 'render_archive_slug_field' ),
			self::PAGE_SLUG,
			'wpevents_permalinks',
			array(
				'label_for' => 'wpevents_archive_slug',
			)
		);

		add_settings_field(
			'single_slug',
			__( 'Event link', 'wp-events' ),
			array( __CLASS__, 'render_single_slug_field' ),
			self::PAGE_SLUG,
			'wpevents_permalinks',
			array(
				'label_for' => 'wpevents_single_slug',
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
	}

	/**
	 * Settings tabs, in display order.
	 *
	 * @return array<string, string>
	 */
	private static function tabs() {
		return array(
			'permalinks' => __( 'Permalinks', 'wp-events' ),
			'cleanup'    => __( 'Cleanup', 'wp-events' ),
			'appearance' => __( 'Appearance', 'wp-events' ),
		);
	}

	/**
	 * Tab requested in the URL, limited to known tabs.
	 *
	 * @return string
	 */
	private static function current_tab() {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'permalinks'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! array_key_exists( $tab, self::tabs() ) ) {
			return 'permalinks';
		}

		return $tab;
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

		$output['archive_slug'] = self::clean_slug(
			isset( $input['archive_slug'] ) ? $input['archive_slug'] : '',
			$defaults['archive_slug']
		);
		$output['single_slug']  = self::clean_slug(
			isset( $input['single_slug'] ) ? $input['single_slug'] : '',
			$defaults['single_slug']
		);

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
		wp_enqueue_style(
			'wpevents-settings',
			plugins_url( 'assets/admin-settings.css', WPEVENTS_PLUGIN_FILE ),
			array( 'wp-color-picker' ),
			WPEVENTS_VERSION
		);
		wp_enqueue_script( 'wp-color-picker' );

		$script = <<<'JS'
(function ($) {
	var pickersReady = false;

	function refreshSlugPreview(input) {
		var preview = document.querySelector(input.getAttribute('data-preview'));
		if (!preview) {
			return;
		}

		var fallback = input.getAttribute('data-default') || '';
		var slug = input.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
		if (!slug) {
			slug = fallback;
		}

		var url = preview.getAttribute('data-home') + slug + preview.getAttribute('data-suffix');
		preview.textContent = url;
		preview.setAttribute('href', url);
	}

	function updateCustomPreview() {
		var preview = document.getElementById('wpevents-custom-preview');
		var primary = document.getElementById('wpevents_color_primary');
		var accent = document.getElementById('wpevents_color_accent');
		if (!preview || !primary || !accent) {
			return;
		}
		if (primary.value) {
			preview.style.setProperty('--preview-primary', primary.value);
		}
		if (accent.value) {
			preview.style.setProperty('--preview-accent', accent.value);
		}
	}

	function initColorPickers() {
		if (pickersReady) {
			updateCustomPreview();
			return;
		}
		pickersReady = true;
		$('.wpevents-color-field').wpColorPicker({
			change: function (event, ui) {
				var preview = document.getElementById('wpevents-custom-preview');
				var field = this.id ? this : event.target;
				if (!preview || !ui || !ui.color || !field) {
					return;
				}
				var color = ui.color.toString();
				if (field.id === 'wpevents_color_primary') {
					preview.style.setProperty('--preview-primary', color);
				} else if (field.id === 'wpevents_color_accent') {
					preview.style.setProperty('--preview-accent', color);
				}
			},
			clear: function () {
				window.setTimeout(updateCustomPreview, 0);
			}
		});
		updateCustomPreview();
	}

	function syncPaletteCards() {
		var selected = document.querySelector('input[name="wpevents_settings[color_mode]"]:checked');
		document.querySelectorAll('.wpevents-palette-card').forEach(function (card) {
			card.classList.toggle('is-selected', !!(selected && card.contains(selected)));
		});
	}

	function showTab(id) {
		document.querySelectorAll('.wpevents-tab-panel').forEach(function (panel) {
			panel.hidden = panel.getAttribute('data-tab') !== id;
		});
		document.querySelectorAll('.wpevents-tabs .nav-tab').forEach(function (tab) {
			var active = tab.getAttribute('data-tab') === id;
			tab.classList.toggle('nav-tab-active', active);
			tab.setAttribute('aria-selected', active ? 'true' : 'false');
		});
		if (window.history && window.history.replaceState) {
			var url = new URL(window.location.href);
			url.searchParams.set('tab', id);
			window.history.replaceState(null, '', url.toString());
		}
		if (id === 'appearance') {
			initColorPickers();
		}
	}

	$(function () {
		$('.wpevents-slug-field').on('input', function () {
			refreshSlugPreview(this);
		});

		$('.wpevents-tabs').on('click', '.nav-tab', function (event) {
			event.preventDefault();
			showTab(this.getAttribute('data-tab'));
		});

		$('.wpevents-palette-choices').on('change', 'input[type="radio"]', syncPaletteCards);
		$('.wpevents-palette-card').on('click', function (event) {
			if ($(event.target).closest('.wp-picker-container, .wpevents-palette-pickers, a, button').length) {
				return;
			}
			var radio = this.querySelector('input[type="radio"]');
			if (!radio) {
				return;
			}
			radio.checked = true;
			syncPaletteCards();
		});

		$('.wpevents-preview__link').on('click', function (event) {
			event.preventDefault();
		});

		syncPaletteCards();

		var activePanel = document.querySelector('.wpevents-tab-panel:not([hidden])');
		if (activePanel && activePanel.getAttribute('data-tab') === 'appearance') {
			initColorPickers();
		}
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

		$tabs   = self::tabs();
		$active = self::current_tab();
		?>
		<div class="wrap wpevents-settings">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<nav class="nav-tab-wrapper wpevents-tabs" aria-label="<?php esc_attr_e( 'Settings sections', 'wp-events' ); ?>">
				<?php foreach ( $tabs as $tab_id => $label ) : ?>
					<a
						class="nav-tab<?php echo $active === $tab_id ? ' nav-tab-active' : ''; ?>"
						href="<?php echo esc_url( self::tab_url( $tab_id ) ); ?>"
						data-tab="<?php echo esc_attr( $tab_id ); ?>"
						aria-selected="<?php echo $active === $tab_id ? 'true' : 'false'; ?>"
					><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>
			<form action="options.php" method="post">
				<?php settings_fields( 'wpevents_settings_group' ); ?>
				<div class="wpevents-tab-panel" data-tab="permalinks"<?php echo 'permalinks' === $active ? '' : ' hidden'; ?>>
					<?php self::render_permalinks_section(); ?>
					<table class="form-table" role="presentation">
						<?php do_settings_fields( self::PAGE_SLUG, 'wpevents_permalinks' ); ?>
					</table>
				</div>
				<div class="wpevents-tab-panel" data-tab="cleanup"<?php echo 'cleanup' === $active ? '' : ' hidden'; ?>>
					<?php self::render_cleanup_section(); ?>
					<table class="form-table" role="presentation">
						<?php do_settings_fields( self::PAGE_SLUG, 'wpevents_cleanup' ); ?>
					</table>
				</div>
				<div class="wpevents-tab-panel" data-tab="appearance"<?php echo 'appearance' === $active ? '' : ' hidden'; ?>>
					<?php self::render_appearance_panel(); ?>
				</div>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Admin URL for one settings tab.
	 *
	 * @param string $tab_id Tab key.
	 * @return string
	 */
	private static function tab_url( $tab_id ) {
		return add_query_arg(
			array(
				'post_type' => 'event',
				'page'      => self::PAGE_SLUG,
				'tab'       => $tab_id,
			),
			admin_url( 'edit.php' )
		);
	}

	/**
	 * Permalinks section description.
	 *
	 * @return void
	 */
	public static function render_permalinks_section() {
		echo '<p>' . esc_html__( 'Set the public URL slugs for the event archive and for a single event.', 'wp-events' ) . '</p>';
	}

	/**
	 * Render the plural archive slug.
	 *
	 * @return void
	 */
	public static function render_archive_slug_field() {
		self::render_slug_field(
			'archive_slug',
			'wpevents_archive_slug',
			'wpevents-archive-slug-preview',
			'/',
			__( 'The above should be plural.', 'wp-events' )
		);
	}

	/**
	 * Render the singular event slug.
	 *
	 * @return void
	 */
	public static function render_single_slug_field() {
		self::render_slug_field(
			'single_slug',
			'wpevents_single_slug',
			'wpevents-single-slug-preview',
			'/single-post-name/',
			__( 'The above should be singular.', 'wp-events' )
		);
	}

	/**
	 * Render a slug input with a live URL preview.
	 *
	 * @param string $key         Settings key.
	 * @param string $input_id    Input element id.
	 * @param string $preview_id  Preview link id.
	 * @param string $suffix      Path appended after the slug.
	 * @param string $description Help text. Already translated.
	 * @return void
	 */
	private static function render_slug_field( $key, $input_id, $preview_id, $suffix, $description ) {
		$defaults = self::defaults();
		$slug     = 'archive_slug' === $key ? self::get_archive_slug() : self::get_single_slug();
		$home     = trailingslashit( home_url() );
		$preview  = $home . $slug . $suffix;
		?>
		<code><?php echo esc_html( $home ); ?></code>
		<input
			type="text"
			class="regular-text code wpevents-slug-field"
			id="<?php echo esc_attr( $input_id ); ?>"
			name="<?php echo esc_attr( self::OPTION_KEY . '[' . $key . ']' ); ?>"
			value="<?php echo esc_attr( $slug ); ?>"
			data-preview="<?php echo esc_attr( '#' . $preview_id ); ?>"
			data-default="<?php echo esc_attr( $defaults[ $key ] ); ?>"
			autocomplete="off"
			spellcheck="false"
		/>
		<p class="description"><?php echo esc_html( $description ); ?></p>
		<p class="description">
			<?php esc_html_e( 'Preview:', 'wp-events' ); ?>
			<a
				id="<?php echo esc_attr( $preview_id ); ?>"
				href="<?php echo esc_url( $preview ); ?>"
				data-home="<?php echo esc_url( $home ); ?>"
				data-suffix="<?php echo esc_attr( $suffix ); ?>"
			><?php echo esc_html( $preview ); ?></a>
		</p>
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
	 * Appearance tab: two palettes, each shown on heading, body, link and buttons.
	 *
	 * @return void
	 */
	private static function render_appearance_panel() {
		$settings = self::get();
		$theme    = self::theme_preview_colors();
		$mode     = $settings['color_mode'];
		?>
		<p class="description">
			<?php esc_html_e( 'Choose whether event UI colors follow the theme or use plugin tokens.', 'wp-events' ); ?>
		</p>
		<fieldset class="wpevents-palette-choices">
			<legend class="screen-reader-text"><?php esc_html_e( 'Color mode', 'wp-events' ); ?></legend>
			<div class="wpevents-palette-card<?php echo 'theme' === $mode ? ' is-selected' : ''; ?>">
				<label class="wpevents-palette-card__choice">
					<input
						type="radio"
						name="<?php echo esc_attr( self::OPTION_KEY ); ?>[color_mode]"
						value="theme"
						<?php checked( $mode, 'theme' ); ?>
					/>
					<?php esc_html_e( 'Follow theme', 'wp-events' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Headings, links and buttons use the theme color. Body text uses the theme text color.', 'wp-events' ); ?>
				</p>
				<?php
				self::render_color_specimen(
					'',
					$theme['primary'],
					$theme['accent'],
					$theme['text']
				);
				?>
			</div>
			<div class="wpevents-palette-card<?php echo 'custom' === $mode ? ' is-selected' : ''; ?>">
				<label class="wpevents-palette-card__choice">
					<input
						type="radio"
						name="<?php echo esc_attr( self::OPTION_KEY ); ?>[color_mode]"
						value="custom"
						<?php checked( $mode, 'custom' ); ?>
					/>
					<?php esc_html_e( 'Custom palette', 'wp-events' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Headings and links use the primary color. The second button uses the accent color.', 'wp-events' ); ?>
				</p>
				<?php
				self::render_color_specimen(
					'wpevents-custom-preview',
					$settings['color_primary'],
					$settings['color_accent'],
					$theme['text']
				);
				?>
				<div class="wpevents-palette-pickers">
					<div>
						<label for="wpevents_color_primary"><?php esc_html_e( 'Primary color', 'wp-events' ); ?></label>
						<input
							type="text"
							class="wpevents-color-field"
							id="wpevents_color_primary"
							name="<?php echo esc_attr( self::OPTION_KEY ); ?>[color_primary]"
							value="<?php echo esc_attr( $settings['color_primary'] ); ?>"
							data-default-color="#007cba"
						/>
					</div>
					<div>
						<label for="wpevents_color_accent"><?php esc_html_e( 'Accent color', 'wp-events' ); ?></label>
						<input
							type="text"
							class="wpevents-color-field"
							id="wpevents_color_accent"
							name="<?php echo esc_attr( self::OPTION_KEY ); ?>[color_accent]"
							value="<?php echo esc_attr( $settings['color_accent'] ); ?>"
							data-default-color="#005a87"
						/>
					</div>
				</div>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Sample event UI used to compare a palette.
	 *
	 * @param string $preview_id Element id. Empty when the sample is static.
	 * @param string $primary    Primary hex color.
	 * @param string $accent     Accent hex color.
	 * @param string $text       Body text hex color.
	 * @return void
	 */
	private static function render_color_specimen( $preview_id, $primary, $accent, $text ) {
		$style = sprintf(
			'--preview-primary:%1$s;--preview-accent:%2$s;--preview-text:%3$s;',
			$primary,
			$accent,
			$text
		);
		?>
		<div
			class="wpevents-preview"
			<?php echo '' !== $preview_id ? 'id="' . esc_attr( $preview_id ) . '"' : ''; ?>
			style="<?php echo esc_attr( $style ); ?>"
		>
			<h3 class="wpevents-preview__heading"><?php esc_html_e( 'Talk at the centre', 'wp-events' ); ?></h3>
			<p class="wpevents-preview__text">
				<?php esc_html_e( 'An open event for everyone.', 'wp-events' ); ?>
				<a class="wpevents-preview__link" href="#wpevents-color-preview"><?php esc_html_e( 'Read more about the evening', 'wp-events' ); ?></a>
			</p>
			<p class="wpevents-preview__actions">
				<button type="button" class="wpevents-preview__button"><?php esc_html_e( 'Sign up', 'wp-events' ); ?></button>
				<button type="button" class="wpevents-preview__button wpevents-preview__button--accent"><?php esc_html_e( 'See the programme', 'wp-events' ); ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * Colors the theme actually exposes to the event UI.
	 *
	 * @return array{primary: string, accent: string, text: string}
	 */
	private static function theme_preview_colors() {
		static $colors = null;

		if ( null !== $colors ) {
			return $colors;
		}

		$colors = array(
			'primary' => '#801010',
			'accent'  => '#5c0b0b',
			'text'    => '#1e1e1e',
		);

		$file = get_stylesheet_directory() . '/style.css';
		if ( is_readable( $file ) ) {
			$css = file_get_contents( $file, false, null, 0, 12000 );
			if ( is_string( $css ) ) {
				$colors['primary'] = self::matched_hex( $css, '/--primary-color:\s*(#[0-9a-fA-F]{3,8})\s*;/', $colors['primary'] );
				$colors['text']    = self::matched_hex( $css, '/--primary-txt-color:\s*(#[0-9a-fA-F]{3,8})\s*;/', $colors['text'] );
			}
		}

		$colors['accent'] = self::darken_hex( $colors['primary'], 36 );

		return $colors;
	}

	/**
	 * First sanitized hex match, or the fallback.
	 *
	 * @param string $css      Stylesheet chunk.
	 * @param string $pattern  Pattern with one hex capture.
	 * @param string $fallback Color used when the pattern does not match.
	 * @return string
	 */
	private static function matched_hex( $css, $pattern, $fallback ) {
		if ( ! preg_match( $pattern, $css, $match ) ) {
			return $fallback;
		}

		$color = sanitize_hex_color( $match[1] );

		return $color ? $color : $fallback;
	}

	/**
	 * Darken a hex color by a fixed amount per channel.
	 *
	 * @param string $hex    Hex color.
	 * @param int    $amount Amount to subtract from each channel.
	 * @return string
	 */
	private static function darken_hex( $hex, $amount ) {
		$color = sanitize_hex_color( $hex );
		if ( ! $color ) {
			return '#5c0b0b';
		}

		$digits = ltrim( $color, '#' );
		if ( 3 === strlen( $digits ) ) {
			$digits = $digits[0] . $digits[0] . $digits[1] . $digits[1] . $digits[2] . $digits[2];
		}

		$channels = array(
			hexdec( substr( $digits, 0, 2 ) ),
			hexdec( substr( $digits, 2, 2 ) ),
			hexdec( substr( $digits, 4, 2 ) ),
		);

		$channels = array_map(
			static function ( $channel ) use ( $amount ) {
				return max( 0, $channel - $amount );
			},
			$channels
		);

		return sprintf( '#%02x%02x%02x', $channels[0], $channels[1], $channels[2] );
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

}
