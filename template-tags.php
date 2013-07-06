<?php

if ( !defined('ABSPATH') ) 
	die('-1');

function tribe_events_oembed_js( $echo = true ){
	$include_js = sprintf( '<script async src="%s" charset="utf-8"></script>',
		tribe_events_oembed::get_widget_js() 
		);
	echo apply_filters( 'tribe_events_oembed_js', $include_js, tribe_events_oembed::get_widget_js() );
}