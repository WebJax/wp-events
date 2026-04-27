<?php
/**
 * WP Events Blocks - Clean version from scratch
 *
 * @package WPEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPEvents_Blocks_Clean {

	public static function init() {
		// Add block category
		add_filter( 'block_categories_all', array( __CLASS__, 'add_block_category' ), 10, 2 );

		// Register blocks on init
		add_action( 'init', array( __CLASS__, 'register_all_blocks' ), 20 );

		// Enqueue scripts for editor
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_scripts' ) );

		// Enqueue frontend assets
		add_action( 'enqueue_block_assets', array( __CLASS__, 'enqueue_frontend_assets' ) );

		// Template loader system
		add_filter( 'template_include', array( __CLASS__, 'template_loader' ) );
	}

	/**
	 * Add WP Events block category
	 */
	public static function add_block_category( $categories, $post ) {
		return array_merge(
			$categories,
			array(
				array(
					'slug'  => 'wp-events',
					'title' => 'WP Events',
					'icon'  => 'calendar-alt',
				),
			)
		);
	}

	/**
	 * Register all blocks
	 */
	public static function register_all_blocks() {
		register_block_type(
			'wp-events/venue',
			array(
				'render_callback' => array( __CLASS__, 'render_venue_block' ),
				'supports'        => array(
					'align'      => array( 'left', 'center', 'right' ),
					'anchor'     => true,
					'className'  => true,
					'color'      => array(
						'gradients'                     => true,
						'link'                          => true,
						'__experimentalDefaultControls' => array(
							'background' => true,
							'text'       => true,
						),
					),
					'spacing'    => array(
						'margin'                        => true,
						'padding'                       => true,
						'__experimentalDefaultControls' => array(
							'margin'  => false,
							'padding' => false,
						),
					),
					'typography' => array(
						'fontSize'                      => true,
						'fontFamily'                    => true,
						'fontStyle'                     => true,
						'fontWeight'                    => true,
						'letterSpacing'                 => true,
						'lineHeight'                    => true,
						'textDecoration'                => true,
						'textTransform'                 => true,
						'__experimentalDefaultControls' => array(
							'fontSize' => true,
						),
					),
				),
				'attributes'      => array(
					'showAddress'    => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showContact'    => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'showDirections' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'linkToVenue'    => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
			)
		);

				register_block_type(
					'wp-events/organizer',
					array(
						'render_callback' => array( __CLASS__, 'render_organizer_block' ),
						'supports'        => array(
							'anchor'     => true,
							'className'  => true,
							'color'      => array(
								'gradients' => true,
								'link'      => true,
								'__experimentalDefaultControls' => array(
									'background' => true,
									'text'       => true,
								),
							),
							'spacing'    => array(
								'margin'  => true,
								'padding' => true,
								'__experimentalDefaultControls' => array(
									'margin'  => false,
									'padding' => false,
								),
							),
							'typography' => array(
								'fontSize'       => true,
								'fontFamily'     => true,
								'fontStyle'      => true,
								'fontWeight'     => true,
								'letterSpacing'  => true,
								'lineHeight'     => true,
								'textDecoration' => true,
								'textTransform'  => true,
								'__experimentalDefaultControls' => array(
									'fontSize' => true,
								),
							),
						),
						'attributes'      => array(
							'textAlign' => array(
								'type' => 'string',
							),
						),
					)
				);

		register_block_type(
			'wp-events/event-schedule',
			array(
				'render_callback' => array( __CLASS__, 'render_event_schedule_block' ),
				'supports'        => array(
					'align'      => array( 'left', 'center', 'right' ),
					'anchor'     => true,
					'className'  => true,
					'color'      => array(
						'gradients'                     => true,
						'link'                          => true,
						'__experimentalDefaultControls' => array(
							'background' => true,
							'text'       => true,
						),
					),
					'spacing'    => array(
						'margin'                        => true,
						'padding'                       => true,
						'__experimentalDefaultControls' => array(
							'margin'  => false,
							'padding' => false,
						),
					),
					'typography' => array(
						'fontSize'                      => true,
						'fontFamily'                    => true,
						'fontStyle'                     => true,
						'fontWeight'                    => true,
						'letterSpacing'                 => true,
						'lineHeight'                    => true,
						'textDecoration'                => true,
						'textTransform'                 => true,
						'__experimentalDefaultControls' => array(
							'fontSize' => true,
						),
					),
				),
				'attributes'      => array(
					'displayMode'   => array(
						'type'    => 'string',
						'default' => 'combined',
					),
					'timeSeparator' => array(
						'type'    => 'string',
						'default' => '–',
					),
					'dateFormat'    => array(
						'type'    => 'string',
						'default' => 'j. F Y',
					),
					'timeFormat'    => array(
						'type'    => 'string',
						'default' => 'H:i',
					),
					'showLabel'     => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'customLabel'   => array(
						'type'    => 'string',
						'default' => '',
					),
					'labelBold'     => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'labelItalic'   => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);

				register_block_type(
					'wp-events/events-list',
					array(
						'render_callback' => array( __CLASS__, 'render_events_list_block' ),
						'supports'        => array(
							'align'      => array( 'left', 'center', 'right', 'wide', 'full' ),
							'anchor'     => true,
							'className'  => true,
							'color'      => array(
								'gradients' => true,
								'link'      => true,
								'__experimentalDefaultControls' => array(
									'background' => true,
									'text'       => true,
								),
							),
							'spacing'    => array(
								'margin'  => true,
								'padding' => true,
								'__experimentalDefaultControls' => array(
									'margin'  => false,
									'padding' => false,
								),
							),
							'typography' => array(
								'fontSize'       => true,
								'fontFamily'     => true,
								'fontStyle'      => true,
								'fontWeight'     => true,
								'letterSpacing'  => true,
								'lineHeight'     => true,
								'textDecoration' => true,
								'textTransform'  => true,
								'__experimentalDefaultControls' => array(
									'fontSize' => true,
								),
							),
						),
						'attributes'      => array(
							'numberOfEvents' => array(
								'type'    => 'number',
								'default' => 5,
							),
							'showVenue'      => array(
								'type'    => 'boolean',
								'default' => true,
							),
							'showDate'       => array(
								'type'    => 'boolean',
								'default' => true,
							),
							'showExcerpt'    => array(
								'type'    => 'boolean',
								'default' => false,
							),
						),
					)
				);

		register_block_type(
			'wp-events/events-carousel',
			array(
				'render_callback' => array( __CLASS__, 'render_events_carousel_block' ),
				'supports'        => array(
					'align'      => array( 'left', 'center', 'right', 'wide', 'full' ),
					'anchor'     => true,
					'className'  => true,
					'color'      => array(
						'gradients'                     => true,
						'link'                          => true,
						'__experimentalDefaultControls' => array(
							'background' => true,
							'text'       => true,
						),
					),
					'spacing'    => array(
						'margin'                        => true,
						'padding'                       => true,
						'__experimentalDefaultControls' => array(
							'margin'  => false,
							'padding' => false,
						),
					),
					'typography' => array(
						'fontSize'                      => true,
						'fontFamily'                    => true,
						'fontStyle'                     => true,
						'fontWeight'                    => true,
						'letterSpacing'                 => true,
						'lineHeight'                    => true,
						'textDecoration'                => true,
						'textTransform'                 => true,
						'__experimentalDefaultControls' => array(
							'fontSize' => true,
						),
					),
				),
				'attributes'      => array(
					'numberOfEvents' => array(
						'type'    => 'number',
						'default' => 3,
					),
					'autoplay'       => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'showDots'       => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showArrows'     => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
			)
		);

		// Event Price Block
		register_block_type(
			'wp-events/event-price',
			array(
				'render_callback' => array( __CLASS__, 'render_event_price_block' ),
				'supports'        => array(
					'anchor'     => true,
					'className'  => true,
					'color'      => array(
						'gradients'                     => true,
						'link'                          => true,
						'__experimentalDefaultControls' => array(
							'background' => true,
							'text'       => true,
						),
					),
					'spacing'    => array(
						'margin'                        => true,
						'padding'                       => true,
						'__experimentalDefaultControls' => array(
							'margin'  => false,
							'padding' => false,
						),
					),
					'typography' => array(
						'fontSize'                      => true,
						'fontFamily'                    => true,
						'fontStyle'                     => true,
						'fontWeight'                    => true,
						'letterSpacing'                 => true,
						'lineHeight'                    => true,
						'textDecoration'                => true,
						'textTransform'                 => true,
						'__experimentalDefaultControls' => array(
							'fontSize' => true,
						),
					),
				),
				'attributes'      => array(
					'textAlign'    => array(
						'type' => 'string',
					),
					'showLabel'    => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'customLabel'  => array(
						'type'    => 'string',
						'default' => 'Pris:',
					),
					'labelBold'    => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'labelItalic'  => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'showCurrency' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'priceFormat'  => array(
						'type'    => 'string',
						'default' => 'after',
					),
				),
			)
		);
	}

	/**
	 * Enqueue editor scripts
	 */
	public static function enqueue_editor_scripts() {
		// Enqueue main blocks script
		wp_enqueue_script(
			'wp-events-blocks',
			WPEVENTS_PLUGIN_URL . 'assets/blocks.js',
			array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor', 'wp-components' ),
			WPEVENTS_VERSION,
			true
		);

		// Enqueue blocks CSS
		wp_enqueue_style(
			'wp-events-blocks-style',
			WPEVENTS_PLUGIN_URL . 'assets/wp-events.css',
			array(),
			WPEVENTS_VERSION
		);

		// Localize script for translations
		wp_localize_script(
			'wp-events-blocks',
			'wpEventsBlocks',
			array(
				'category' => 'wp-events',
			)
		);
	}

	/**
	 * Enqueue frontend assets
	 */
	public static function enqueue_frontend_assets() {
		// Enqueue frontend CSS - only loads when blocks are present on the page
		wp_enqueue_style(
			'wp-events-frontend',
			WPEVENTS_PLUGIN_URL . 'assets/wp-events-frontend.css',
			array(),
			WPEVENTS_VERSION
		);

		// Enqueue Swiper for carousel block
		if ( has_block( 'wp-events/events-carousel' ) ) {
			wp_enqueue_style( 'swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), '11.0.0' );
			wp_enqueue_script( 'swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array(), '11.0.0', true );

			wp_enqueue_script(
				'wp-events-frontend-js',
				WPEVENTS_PLUGIN_URL . 'assets/wp-events-frontend.js',
				array( 'swiper' ),
				WPEVENTS_VERSION,
				true
			);
		}
	}

	/**
	 * Template loader - checks theme first, then plugin templates
	 */
	public static function template_loader( $template ) {
		if ( is_embed() ) {
			return $template;
		}

		$default_file = self::get_template_loader_default_file();

		if ( $default_file ) {
			/**
			 * Filter hook to choose which files to find before WP does its thing.
			 *
			 * @param array $search_files Array of template files to search for.
			 * @param string $default_file The default template filename.
			 */
			$search_files = self::get_template_loader_files( $default_file );
			$template     = locate_template( $search_files );

			if ( ! $template ) {
				$template = WPEVENTS_PLUGIN_DIR . 'templates/' . $default_file;
			}

			// Enqueue frontend assets for our templates
			if ( strpos( $template, 'wp-events' ) !== false || strpos( $template, WPEVENTS_PLUGIN_DIR ) !== false ) {
				add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ), 20 );
			}
		}

		return $template;
	}

	/**
	 * Get the default filename for a template.
	 */
	private static function get_template_loader_default_file() {
		if ( is_single() && get_post_type() === 'event' ) {
			$default_file = 'single-event.php';
		} elseif ( is_single() && get_post_type() === 'venue' ) {
			$default_file = 'single-venue.php';
		} elseif ( is_single() && get_post_type() === 'organizer' ) {
			$default_file = 'single-organizer.php';
		} elseif ( is_post_type_archive( 'event' ) ) {
			// Check for view parameter to load alternative templates
			$view          = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';
			$allowed_views = array( 'list', 'calendar', 'compact' );

			if ( $view && in_array( $view, $allowed_views, true ) ) {
				$default_file = 'archive-event-' . $view . '.php';
			} else {
				$default_file = 'archive-event.php';
			}
		} elseif ( is_tax( 'event_category' ) ) {
			$default_file = 'taxonomy-event_category.php';
		} elseif ( is_tax( 'event_tag' ) ) {
			$default_file = 'taxonomy-event_tag.php';
		} else {
			$default_file = '';
		}

		return $default_file;
	}

	/**
	 * Get an array of filenames to search for a given template.
	 */
	private static function get_template_loader_files( $default_file ) {
		$templates = array();
		$template  = str_replace( WPEVENTS_PLUGIN_DIR . 'templates/', '', $default_file );

		if ( is_tax( 'event_category' ) || is_tax( 'event_tag' ) ) {
			$object = get_queried_object();

			// Look for specific term template first (e.g., taxonomy-event_category-udstilling.php)
			$specific_template = str_replace( '.php', '-' . $object->slug . '.php', $template );
			$templates[]       = 'wp-events/' . $specific_template;

			// Then general taxonomy template (e.g., taxonomy-event_category.php)
			$templates[] = 'wp-events/' . $template;
		} else {
			$templates[] = 'wp-events/' . $template;
		}

		// Add theme root fallback
		$templates[] = $template;

		return array_unique( $templates );
	}

	/**
	 * Render venue block
	 */
	public static function render_venue_block( $attributes ) {
		$post_id = get_the_ID();
		if ( ! $post_id || get_post_type( $post_id ) !== 'event' ) {
			return '<p>' . esc_html__( 'Venue block can only be used in event posts.', 'wp-events' ) . '</p>';
		}

		$venue_id = get_post_meta( $post_id, 'event_venue', true );
		if ( ! $venue_id ) {
			return '<p>' . esc_html__( 'No venue assigned to this event.', 'wp-events' ) . '</p>';
		}

		$venue = get_post( $venue_id );
		if ( ! $venue ) {
			return '<p>' . esc_html__( 'Venue not found.', 'wp-events' ) . '</p>';
		}

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => 'wp-events-venue',
			)
		);

		$output = '<div ' . $wrapper_attributes . '>';

		$venue_title = esc_html( $venue->post_title );
		if ( ! empty( $attributes['linkToVenue'] ) ) {
			$venue_title = '<a href="' . esc_url( get_permalink( $venue_id ) ) . '">' . $venue_title . '</a>';
		}
		$output .= '<h3>' . $venue_title . '</h3>';

		if ( ! empty( $attributes['showAddress'] ) ) {
			$address     = get_post_meta( $venue_id, 'venue_address', true );
			$city        = get_post_meta( $venue_id, 'venue_city', true );
			$postal_code = get_post_meta( $venue_id, 'venue_postal_code', true );
			$country     = get_post_meta( $venue_id, 'venue_country', true );

			$full_address = array_filter( array( $address, trim( $postal_code . ' ' . $city ), $country ) );

			if ( ! empty( $full_address ) ) {
				$output .= '<div class="venue-address">';
				foreach ( $full_address as $line ) {
					$output .= '<div>' . esc_html( trim( $line ) ) . '</div>';
				}
				$output .= '</div>';
			}
		}

		if ( ! empty( $attributes['showContact'] ) ) {
			$phone = get_post_meta( $venue_id, 'venue_phone', true );
			$email = get_post_meta( $venue_id, 'venue_email', true );

			if ( $phone ) {
				$output .= '<p class="venue-phone">' . esc_html__( 'Phone:', 'wp-events' ) . ' ' . esc_html( $phone ) . '</p>';
			}
			if ( $email ) {
				$output .= '<p class="venue-email">' . esc_html__( 'Email:', 'wp-events' ) . ' <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></p>';
			}
		}

		// Show directions link if enabled
		if ( ! empty( $attributes['showDirections'] ) ) {
			$show_directions = get_post_meta( $venue_id, 'venue_show_directions', true );
			if ( $show_directions ) {
				$directions_url = WPEvents_CPT::get_venue_directions_url( $venue_id );
				if ( $directions_url ) {
					$output .= '<p class="venue-directions">';
					$output .= '<a href="' . esc_url( $directions_url ) . '" target="_blank" rel="noopener">';
					$output .= '<span class="icon-location"></span> ';
					$output .= esc_html__( 'Get directions', 'wp-events' );
					$output .= '</a>';
					$output .= '</p>';
				}
			}
		}

		$output .= '</div>';
		return $output;
	}

	/**
	 * Render organizer block
	 */
	public static function render_organizer_block( $attributes ) {
		$post_id = get_the_ID();
		if ( ! $post_id || get_post_type( $post_id ) !== 'event' ) {
			return '<p>' . esc_html__( 'Organizer block can only be used in event posts.', 'wp-events' ) . '</p>';
		}

		$organizer_ids = get_post_meta( $post_id, 'event_organizer', true );
		if ( ! is_array( $organizer_ids ) || empty( $organizer_ids ) ) {
			return '<p>' . esc_html__( 'No organizer assigned to this event.', 'wp-events' ) . '</p>';
		}

		// Build CSS classes including text alignment
		$classes = array( 'wp-events-organizer-heading' );
		if ( ! empty( $attributes['textAlign'] ) ) {
			$classes[] = 'has-text-align-' . sanitize_html_class( $attributes['textAlign'] );
		}

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => implode( ' ', $classes ),
			)
		);

		// Get first organizer only for heading
		$organizer = get_post( $organizer_ids[0] );
		if ( ! $organizer ) {
			return '<p>' . esc_html__( 'Organizer not found.', 'wp-events' ) . '</p>';
		}

		$organizer_title = esc_html( $organizer->post_title );

		return '<h2 ' . $wrapper_attributes . '>' . $organizer_title . '</h2>';
	}

	/**
	 * Render schedule block.
	 */
	public static function render_event_schedule_block( $attributes ) {
		$post_id = get_the_ID();
		if ( ! $post_id || get_post_type( $post_id ) !== 'event' ) {
			return '<p>' . esc_html__( 'Schedule block can only be used in event posts.', 'wp-events' ) . '</p>';
		}

		$start_date = get_post_meta( $post_id, 'event_start', true );
		if ( ! $start_date ) {
			return '<p>' . esc_html__( 'No start date set for this event.', 'wp-events' ) . '</p>';
		}

		$start_ts = strtotime( $start_date );
		if ( ! $start_ts ) {
			return '<p>' . esc_html__( 'Invalid start date format.', 'wp-events' ) . '</p>';
		}

		$end_date = get_post_meta( $post_id, 'event_end', true );
		$end_ts   = $end_date ? strtotime( $end_date ) : false;

		$date_format     = ! empty( $attributes['dateFormat'] ) ? $attributes['dateFormat'] : 'j. F Y';
		$time_format     = ! empty( $attributes['timeFormat'] ) ? $attributes['timeFormat'] : 'H:i';
		$time_separator  = ! empty( $attributes['timeSeparator'] ) ? $attributes['timeSeparator'] : '–';
		$display_mode    = ! empty( $attributes['displayMode'] ) ? $attributes['displayMode'] : 'combined';
		$same_day_dates  = $end_ts && wp_date( 'Y-m-d', $start_ts ) === wp_date( 'Y-m-d', $end_ts );
		$show_end_as_set = $end_ts && 'start-only' !== $display_mode;

		if ( ! $end_ts ) {
			$display_mode = 'start-only';
		}

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => 'wp-events-schedule',
			)
		);

		$output = '<div ' . $wrapper_attributes . '>';
		if ( ! empty( $attributes['showLabel'] ) && ! empty( $attributes['customLabel'] ) ) {
			$output .= self::render_schedule_label( $attributes );
		}

		if ( 'end-only' === $display_mode && $show_end_as_set ) {
			$output .= '<time datetime="' . esc_attr( $end_date ) . '">' . esc_html( wp_date( $date_format . ', ' . $time_format, $end_ts ) ) . '</time>';
			$output .= '</div>';
			return $output;
		}

		if ( 'start-only' === $display_mode ) {
			$output .= '<time datetime="' . esc_attr( $start_date ) . '">' . esc_html( wp_date( $date_format . ', ' . $time_format, $start_ts ) ) . '</time>';
			$output .= '</div>';
			return $output;
		}

		if ( $show_end_as_set && ! $same_day_dates ) {
			$output .= '<time datetime="' . esc_attr( $start_date ) . '">' . esc_html( wp_date( $date_format . ', ' . $time_format, $start_ts ) ) . '</time>';
			$output .= ' ' . esc_html( $time_separator ) . ' ';
			$output .= '<time datetime="' . esc_attr( $end_date ) . '">' . esc_html( wp_date( $date_format . ', ' . $time_format, $end_ts ) ) . '</time>';
			$output .= '</div>';
			return $output;
		}

		$output .= '<time datetime="' . esc_attr( $start_date ) . '">' . esc_html( wp_date( $date_format, $start_ts ) ) . '</time>';
		$output .= ' · ';
		$output .= '<time datetime="' . esc_attr( $start_date ) . '">' . esc_html( wp_date( $time_format, $start_ts ) ) . '</time>';
		if ( $show_end_as_set ) {
			$output .= ' ' . esc_html( $time_separator ) . ' ';
			$output .= '<time datetime="' . esc_attr( $end_date ) . '">' . esc_html( wp_date( $time_format, $end_ts ) ) . '</time>';
		}
		$output .= '</div>';

		return $output;
	}

	/**
	 * Render optional label for schedule block.
	 */
	private static function render_schedule_label( $attributes ) {
		$label_styles = array();
		if ( ! empty( $attributes['labelBold'] ) ) {
			$label_styles[] = 'font-weight: bold';
		}
		if ( ! empty( $attributes['labelItalic'] ) ) {
			$label_styles[] = 'font-style: italic';
		}

		$label_style_attr = ! empty( $label_styles ) ? ' style="' . esc_attr( implode( '; ', $label_styles ) ) . '"' : '';
		return '<span class="label"' . $label_style_attr . '>' . esc_html( $attributes['customLabel'] ) . ' </span>';
	}

	public static function render_events_list_block( $attributes ) {
		$limit = isset( $attributes['numberOfEvents'] ) ? intval( $attributes['numberOfEvents'] ) : 5;

		$args = array(
			'post_type'      => 'event',
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'     => 'event_start',
					'value'   => current_time( 'Y-m-d' ),
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
			'meta_key'       => 'event_start',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		);

		$events = get_posts( $args );

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => 'wp-events-list',
			)
		);

		if ( empty( $events ) ) {
			return '<div ' . $wrapper_attributes . '>' . esc_html__( 'No upcoming events found.', 'wp-events' ) . '</div>';
		}

		$output = '<div ' . $wrapper_attributes . '>';
		foreach ( $events as $event ) {
			$start_date = get_post_meta( $event->ID, 'event_start', true );
			$venue_id   = get_post_meta( $event->ID, 'event_venue', true );
			$venue      = $venue_id ? get_the_title( $venue_id ) : '';

			$output .= '<div class="event-item">';
			$output .= '<h3><a href="' . esc_url( get_permalink( $event->ID ) ) . '">' . esc_html( get_the_title( $event->ID ) ) . '</a></h3>';
			if ( $start_date ) {
				$output .= '<div class="event-date">' . esc_html( wp_date( get_option( 'date_format' ), strtotime( $start_date ) ) ) . '</div>';
			}
			if ( $venue ) {
				$output .= '<div class="event-venue">' . esc_html( $venue ) . '</div>';
			}
			$output .= '</div>';
		}
		$output .= '</div>';

		return $output;
	}

	public static function render_events_carousel_block( $attributes ) {
		$limit = isset( $attributes['numberOfEvents'] ) ? intval( $attributes['numberOfEvents'] ) : 3;

		$args = array(
			'post_type'      => 'event',
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'     => 'event_start',
					'value'   => current_time( 'Y-m-d' ),
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
			'meta_key'       => 'event_start',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		);

		$events = get_posts( $args );

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => 'wp-events-carousel swiper',
			)
		);

		if ( empty( $events ) ) {
			return '<div ' . $wrapper_attributes . '>' . esc_html__( 'No upcoming events found.', 'wp-events' ) . '</div>';
		}

		$output  = '<div ' . $wrapper_attributes . '>';
		$output .= '<div class="swiper-wrapper">';
		foreach ( $events as $event ) {
			$start_date     = get_post_meta( $event->ID, 'event_start', true );
			$venue_id       = get_post_meta( $event->ID, 'event_venue', true );
			$venue          = $venue_id ? get_the_title( $venue_id ) : '';
			$featured_image = get_the_post_thumbnail( $event->ID, 'medium' );

			$output .= '<div class="swiper-slide">';
			$output .= '<div class="event-card">';
			if ( $featured_image ) {
				$output .= '<div class="event-image">' . $featured_image . '</div>';
			}
			$output .= '<div class="event-content">';
			$output .= '<h3><a href="' . esc_url( get_permalink( $event->ID ) ) . '">' . esc_html( get_the_title( $event->ID ) ) . '</a></h3>';
			if ( $start_date ) {
				$output .= '<div class="event-date">' . esc_html( wp_date( get_option( 'date_format' ), strtotime( $start_date ) ) ) . '</div>';
			}
			if ( $venue ) {
				$output .= '<div class="event-venue">' . esc_html( $venue ) . '</div>';
			}
			$output .= '</div>';
			$output .= '</div>';
			$output .= '</div>';
		}
		$output .= '</div>'; // .swiper-wrapper

		if ( ! empty( $attributes['showDots'] ) ) {
			$output .= '<div class="swiper-pagination"></div>';
		}
		if ( ! empty( $attributes['showArrows'] ) ) {
			$output .= '<div class="swiper-button-next"></div>';
			$output .= '<div class="swiper-button-prev"></div>';
		}

		$output .= '</div>'; // .swiper

		return $output;
	}

	// Event Price Block
	public static function render_event_price_block( $attributes, $content, $block ) {
		$post_id = get_the_ID();
		if ( ! $post_id || get_post_type( $post_id ) !== 'event' ) {
			return '<p>' . esc_html__( 'Price block can only be used in event posts.', 'wp-events' ) . '</p>';
		}

		$price    = get_post_meta( $post_id, 'event_price', true );
		$currency = get_post_meta( $post_id, 'event_currency', true );

		if ( empty( $price ) || ! is_numeric( $price ) || (float) $price <= 0 ) {
			return '';
		}

		// Get attributes
		$text_align    = isset( $attributes['textAlign'] ) ? $attributes['textAlign'] : '';
		$show_label    = isset( $attributes['showLabel'] ) ? $attributes['showLabel'] : true;
		$custom_label  = isset( $attributes['customLabel'] ) ? $attributes['customLabel'] : __( 'Price', 'wp-events' );
		$label_bold    = isset( $attributes['labelBold'] ) ? $attributes['labelBold'] : false;
		$label_italic  = isset( $attributes['labelItalic'] ) ? $attributes['labelItalic'] : false;
		$show_currency = isset( $attributes['showCurrency'] ) ? $attributes['showCurrency'] : true;
		$price_format  = isset( $attributes['priceFormat'] ) ? $attributes['priceFormat'] : 'after';

		// Build wrapper classes
		$classes = array( 'wp-events-price' );
		if ( ! empty( $text_align ) ) {
			$classes[] = 'has-text-align-' . $text_align;
		}

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => implode( ' ', $classes ),
			)
		);

		// Format currency
		$currency_code = ! empty( $currency ) ? strtoupper( $currency ) : 'DKK';

		// Common currency symbols
		$currency_symbols = array(
			'DKK' => 'kr',
			'EUR' => '€',
			'USD' => '$',
			'GBP' => '£',
			'NOK' => 'kr',
			'SEK' => 'kr',
		);

		$currency_symbol = isset( $currency_symbols[ $currency_code ] ) ? $currency_symbols[ $currency_code ] : $currency_code;

		// Format price with currency
		$formatted_price = number_format_i18n( floatval( $price ), 0 );

		if ( $show_currency ) {
			if ( 'before' === $price_format ) {
				$price_display = $currency_symbol . ' ' . $formatted_price;
			} else {
				$price_display = $formatted_price . ' ' . $currency_symbol;
			}
		} else {
			$price_display = $formatted_price;
		}

		// Build label HTML
		$label_html = '';
		if ( $show_label && ! empty( $custom_label ) ) {
			$label_classes = array();
			if ( $label_bold ) {
				$label_classes[] = 'label-bold';
			}
			if ( $label_italic ) {
				$label_classes[] = 'label-italic';
			}

			$label_class_attr = ! empty( $label_classes ) ? ' class="' . implode( ' ', $label_classes ) . '"' : '';
			$label_html       = '<span' . $label_class_attr . '>' . esc_html( $custom_label ) . ':</span> ';
		}

		// Build output with schema markup
		$output  = '<div ' . $wrapper_attributes . '>';
		$output .= '<div class="price-container" itemscope itemtype="https://schema.org/Offer">';
		$output .= $label_html;
		$output .= '<span class="price-amount" itemprop="price" content="' . esc_attr( $price ) . '">';
		$output .= $price_display;
		$output .= '</span>';
		if ( $show_currency ) {
			$output .= '<meta itemprop="priceCurrency" content="' . esc_attr( $currency_code ) . '">';
		}
		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}


}
