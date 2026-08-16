<?php
/**
 * Template for displaying a single Venue.
 *
 * Override by copying to yourtheme/wp-events/single-venue.php
 *
 * @package WPEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$venue_id      = get_the_ID();
$address       = get_post_meta( $venue_id, 'venue_address', true );
$city          = get_post_meta( $venue_id, 'venue_city', true );
$postal_code   = get_post_meta( $venue_id, 'venue_postal_code', true );
$country       = get_post_meta( $venue_id, 'venue_country', true );
$phone         = get_post_meta( $venue_id, 'venue_phone', true );
$email         = get_post_meta( $venue_id, 'venue_email', true );
$website       = get_post_meta( $venue_id, 'venue_website', true );
$facebook      = get_post_meta( $venue_id, 'venue_facebook', true );
$instagram     = get_post_meta( $venue_id, 'venue_instagram', true );
$other_social  = get_post_meta( $venue_id, 'venue_other_social', true );
$show_directions = get_post_meta( $venue_id, 'venue_show_directions', true );
$directions_url  = $show_directions ? \WPEvents\Venue::get_directions_url( $venue_id ) : false;

if ( ! is_array( $other_social ) ) {
	$other_social = array();
}

$upcoming = new WP_Query(
	\WPEvents\QueryFilters::constrain_event_listing_args(
		array(
			'post_type'      => 'event',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'meta_key'       => 'event_start',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => 'event_venue',
					'value'   => (string) $venue_id,
					'compare' => '=',
				),
				\WPEvents\QueryFilters::upcoming_start_clause(),
			),
		)
	)
);
?>

<div class="wp-events-single-page wp-events-entity-page wp-events-venue-page">
	<div class="ddk-container">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article class="single-event single-venue">
				<header class="event-header">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="event-featured-image">
							<?php the_post_thumbnail( 'large' ); ?>
						</div>
					<?php endif; ?>

					<h1 class="event-title"><?php the_title(); ?></h1>
				</header>

				<div class="event-content">
					<div class="event-venue-details">
						<div class="venue-info">
							<?php if ( $address || $city || $postal_code || $country ) : ?>
								<div class="venue-address">
									<?php if ( $address ) : ?>
										<div><?php echo esc_html( $address ); ?></div>
									<?php endif; ?>
									<?php if ( $postal_code || $city ) : ?>
										<div><?php echo esc_html( trim( $postal_code . ' ' . $city ) ); ?></div>
									<?php endif; ?>
									<?php if ( $country ) : ?>
										<div><?php echo esc_html( $country ); ?></div>
									<?php endif; ?>
								</div>
							<?php endif; ?>

							<?php if ( $phone || $email || $website ) : ?>
								<div class="venue-contact">
									<?php if ( $phone ) : ?>
										<div><strong><?php esc_html_e( 'Phone:', 'wp-events' ); ?></strong> <?php echo esc_html( $phone ); ?></div>
									<?php endif; ?>
									<?php if ( $email ) : ?>
										<div>
											<strong><?php esc_html_e( 'Email:', 'wp-events' ); ?></strong>
											<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
										</div>
									<?php endif; ?>
									<?php if ( $website ) : ?>
										<div>
											<strong><?php esc_html_e( 'Website:', 'wp-events' ); ?></strong>
											<a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $website ); ?></a>
										</div>
									<?php endif; ?>
								</div>
							<?php endif; ?>

							<?php if ( $facebook || $instagram || ! empty( $other_social ) ) : ?>
								<div class="venue-social">
									<?php if ( $facebook ) : ?>
										<a href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Facebook', 'wp-events' ); ?></a>
									<?php endif; ?>
									<?php if ( $instagram ) : ?>
										<a href="<?php echo esc_url( $instagram ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Instagram', 'wp-events' ); ?></a>
									<?php endif; ?>
									<?php foreach ( $other_social as $social_url ) : ?>
										<?php if ( $social_url ) : ?>
											<a href="<?php echo esc_url( $social_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $social_url ); ?></a>
										<?php endif; ?>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<?php if ( $directions_url ) : ?>
								<p class="venue-directions">
									<a href="<?php echo esc_url( $directions_url ); ?>" target="_blank" rel="noopener noreferrer">
										<?php esc_html_e( 'Get directions', 'wp-events' ); ?>
									</a>
								</p>
							<?php endif; ?>
						</div>
					</div>

					<?php if ( $upcoming->have_posts() ) : ?>
						<section class="wp-events-related-events">
							<h2><?php esc_html_e( 'Upcoming events at this venue', 'wp-events' ); ?></h2>
							<div class="events-grid">
								<?php
								while ( $upcoming->have_posts() ) :
									$upcoming->the_post();
									wpevents_get_template_part( 'parts/event-card', 'grid' );
								endwhile;
								wp_reset_postdata();
								?>
							</div>
						</section>
					<?php endif; ?>
				</div>
			</article>
		<?php endwhile; ?>
	</div>
</div>

<?php
get_footer();
