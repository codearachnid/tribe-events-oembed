<?php
/**
 * oEmbed View Template
 * The minified template for oEmbed rich display.
 *
 * The bulk of this view is based on TEC/views/list/single-event.php
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/oembed.php
 *
 */

if ( !defined('ABSPATH') )
	die('-1');


$venue_name = tribe_events_oembed_venue();

// Venue microformats
$has_venue = ( $venue_name ) ? ' vcard': '';
$has_venue_address = ( $venue_name ) ? ' location': '';


?>
<div class="tribe-event-embed">
<div class="wrapper">
	
	<?php do_action( 'tribe_events_before_the_oembed_title' ); ?>
	<h2 class="title summary">
		<a class="url" href="<?php echo tribe_get_event_link() ?>" title="<?php the_title() ?>" rel="bookmark">
			<?php the_title(); ?>
		</a>
		<?php if ( tribe_get_cost() ) : ?> 
			<span class="cost"><?php echo tribe_get_cost( null, true ); ?></span>
		<?php endif; ?>
	</h2>
	<?php do_action( 'tribe_events_after_the_oembed_title' ); ?>

	<?php 

	if ( has_post_thumbnail() ) 
		tribe_events_oembed_featured();
	?>

	<?php do_action( 'tribe_events_before_the_oembed_meta' ); ?>
	<div class="<?php echo $has_venue . $has_venue_address; ?> meta">

		<div class="updated published time-details">
			<?php echo tribe_events_event_schedule_details() ?>
			<?php echo tribe_events_event_recurring_info_tooltip() ?>
		</div>
		
		<?php if ( $venue_name ) : ?>
			<div class="venue">
				@ <?php echo $venue_name; ?>
			</div>
		<?php endif; ?>

		<?php do_action( 'tribe_events_before_the_oembed_content' ) ?>
			<div class="excerpt entry-summary">
				<?php the_excerpt() ?>
				<a href="<?php echo tribe_get_event_link() ?>" class="read-more" rel="bookmark"><?php _e( 'Find out more', 'tribe-events-calendar' ) ?> &raquo;</a>
			</div>
		<?php do_action( 'tribe_events_after_the_oembed_content' ) ?>

	</div>
	<?php do_action( 'tribe_events_after_the_oembed_meta' ); ?>
	<div style="clear:both"></div>
</div>
</div>
<?php tribe_events_oembed_js(); ?>