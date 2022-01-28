<?php

use AM4Utils\Functions;

require_once 'includes/functions.php';

if ( count( $argv ) < 2 ) {
	echo 'Pass a flight name/route registraion';
	echo PHP_EOL;
	exit;
}

$flight = strtoupper( $argv[1] );
$flight = strtoupper( $flight );

$flights = Functions\get_flights();
$routes  = Functions\get_routes();

if ( ! isset( $flights[ $flight ] ) ) {
	echo '******';
	echo PHP_EOL;
	echo 'Flight not found';
	echo PHP_EOL;
	exit;
}

$flight = $flights[ $flight ];

if ( ! isset( $routes[ $flight['route'] ] ) ) {
	echo '******';
	echo PHP_EOL;
	echo 'Route not found';
	echo PHP_EOL;
	exit;
}

echo '******';
echo PHP_EOL;
echo sprintf( 'Route: %s', $flight['route'] );  // phpcs:ignore
echo PHP_EOL;

$plane = Functions\get_plane( $flight['plane'] );
$route = $routes[ $flight['route'] ];

$flight_time = Functions\calculate_flight_time( $plane, $route );

echo sprintf( 'Plane: %s', $flight['plane'] );  // phpcs:ignore
echo PHP_EOL;
echo sprintf( 'Distance: %s', number_format( $route['distance'], 0 ) );  // phpcs:ignore
echo PHP_EOL;
echo sprintf( 'Flight Time: %s', $flight_time );  // phpcs:ignore
echo PHP_EOL;

$avg_income = 0;

foreach ( $flight['flights'] as $flight_income ) {
	$avg_income += $flight_income;
}

$avg_income = $avg_income / count( $flight['flights' ]);

$gross_hr = ( 60 / $flight_time ) * $avg_income;

echo sprintf( 'Gross Hr: $%s', number_format( $gross_hr, 0 ) );  // phpcs:ignore
echo PHP_EOL;
