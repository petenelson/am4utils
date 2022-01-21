<?php


$new_feed = new \DOMDocument();
$new_feed->loadXML( file_get_contents( 'phpunit.xml') );

$root = $new_feed->childNodes[0];

$channel = $root->getElementsByTagName( 'coverage' );

var_dump( $channel ); die();