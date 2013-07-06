<?php
/**
 * oEmbed View Template
 * The minified template for oEmbed rich display.
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/oembed.php
 *
 */

if ( !defined('ABSPATH') ) { die('-1'); } ?><blockquote class="tribe-event-embed">
	<p><?php the_title(); ?></p>
	<a href="<?php the_permalink(); ?>">See Event</a>
</blockquote>
<?php tribe_events_oembed_js(); ?>