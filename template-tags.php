<?php

if ( !defined('ABSPATH') ) 
	die('-1');

function tribe_events_oembed_js( $echo = true ){
	$include_js = sprintf( '<script async src="%s" charset="utf-8"></script>',
		tribe_events_oembed::get_widget_js() 
		);
	echo apply_filters( 'tribe_events_oembed_js', $include_js, tribe_events_oembed::get_widget_js() );
}

function tribe_events_oembed_featured(){
	$html = tribe_event_featured_image( null, tribe_get_option( 'oembed-featured-image', 'thumbnail' ) );
	if( !empty( $html ) )
		$html = '<div class="featured">' . strip_tags( $html, '<a><img>' ) . '</div>';
	echo apply_filters( 'tribe_events_oembed_featured', $html );
}

function tribe_events_oembed_venue(){
	$venue_name = tribe_get_meta( 'tribe_event_venue_name' );
	$venue_name .= ' <span>' . tribe_get_meta( 'tribe_event_venue_gmap_link' ) . '</span>';
	$venue_name = strip_tags( $venue_name, '<a><span>' );
	$venue_name = $venue_name ? $venue_name : false;
	return apply_filters( 'tribe_events_oembed_venue', $venue_name );
}