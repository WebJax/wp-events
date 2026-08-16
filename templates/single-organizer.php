<?php
/**
 * Template for displaying a single Organizer.
 *
 * Override by copying to yourtheme/wp-events/single-organizer.php
 *
 * @package WPEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$organizer_id = get_the_ID();
$phone        = get_post_meta( $organizer_id, 'organizer_phone', true );
$email        = get_post_meta( $organizer_id, 'organizer_email', true );
$website      = get_post_meta( $organizer_id, 'organizer_website', true );

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
				'relation' => 'AND',
				array(
					'relation' => 'OR',
					array(
						'key'     => 'event_organizer',
						'value'   => 'i:' . (int) $organizer_id . ';',
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'event_organizer',
						'value'   => '"' . (int) $organizer_id . '"',
						'compare' => 'LIKE',
					),
				),
				\WPEvents\QueryFilters::upcoming_start_clause(),
			),
		)
	)
);
?>

<div class="wp-events-single-page wp-events-entity-page wp-events-organizer-page">
	<div class="ddk-container">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article class="single-event single-organizer">
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
						</div>
					</div>

					<?php if ( $upcoming->have_posts() ) : ?>
						<section class="wp-events-related-events">
							<h2><?php esc_html_e( 'Upcoming events', 'wp-events' ); ?></h2>
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
