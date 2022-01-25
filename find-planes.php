<?php

use AM4Utils\Functions;

require_once 'includes/functions.php';

if ( count( $argv ) < 2 ) {
	echo 'Pass a route';
	echo PHP_EOL;
	exit;
}

$route        = strtoupper( $argv[1] );
$plane_filter = isset( $argv[2] ) ? strtoupper( $argv[2] ) : false;

$rounding_disabled = false;
$ignore_range      = false;

echo '******';
echo PHP_EOL;
echo sprintf( 'Route: %s', $route );  // phpcs:ignore
echo PHP_EOL;

$list = [];

// Make sure each plane has the range.
$plane_keys = [];
$planes     = Functions\get_planes();
$routes     = Functions\get_routes();
$airports   = Functions\get_airports();

if ( ! isset( $routes[ $route ] ) ) {
	echo '******';
	echo PHP_EOL;
	echo 'Route not found';
	echo PHP_EOL;
	exit;
}

$distance = $routes[ $route ]['distance'];
$demand   = $routes[ $route ]['demand'];
$runway   = 0;

$hub_destionation = explode( '-', $route );
if ( isset( $airports[ $hub_destionation[1] ] ) ) {
	$runway = $airports[ $hub_destionation[1] ]['runway'];
}

echo sprintf( 'Distance: %s', $distance ); // phpcs:ignore
echo PHP_EOL;
echo sprintf( 'Runway: %s', $runway ); // phpcs:ignore
echo PHP_EOL;
echo sprintf( 'Demand: %s/%s/%s', $demand['y'], $demand['j'], $demand['f'] ); // phpcs:ignore
echo PHP_EOL;
echo '******';
echo PHP_EOL;

$initial_plane_keys = [];

if ( ! empty( $plane_filter ) ) {
	$initial_plane_keys = [ $plane_filter ];

	if ( 'COMMON' === $plane_filter ) {
		$initial_plane_keys = [
			'MC-21-300',
			'MC-21-400',
			'B747SP',
			'B747-8',
			'DC-10-10',
			'MD-88',
			'MD-81',
			'MD-11',
			'A320-200',
			'A220-100',
			'A380-800',
			'CRJ 100',
			'CRJ 1000',
			'B727-100',
			'B727-200',
			'F28-6000',
		];

		$plane_filter = false;
	}
} else {
	$initial_plane_keys = array_keys( $planes );
}

foreach ( $initial_plane_keys as $plane_key ) {

	$range        = $planes[ $plane_key ]['range'];
	$plane_runway = $planes[ $plane_key ]['runway'];

	$can_add = $distance <= $range || $ignore_range;

	if ( ! empty( $runway ) && ! empty( $plane_runway ) && $plane_runway > $runway ) {
		$can_add = false;
	}

	if ( $can_add ) {
		$plane_keys[] = $plane_key;
	}
}


$pax_adjust = 0.88;

foreach ( $plane_keys as $plane ) {

	// TODO add caching.

	echo 'Calculating ' . $plane; // phpcs:ignore
	echo PHP_EOL;

	$results = \AM4Utils\Functions\calculate_planes_required( $route, $plane, $pax_adjust );

	if ( ! empty( $results ) ) {
		$required = $results['required'];

		if ( empty( $plane_filter ) ) {
			if ( $required > 6.1 ) {
				$results = false;
			}

			if ( $required < 0.9  ) {
				$results = false;
			}

			if ( ! $rounding_disabled ) {
				for ( $i = 1; $i <= 5; $i++ ) {
					if ( $required > $i + 0.12 && $required < $i + 0.95 ) {
						$results = false;
					}
				}
			}
		}
	}

	if ( ! empty( $plane_filter ) && $plane_filter !== $plane ) {
		$results = false;
	}

	if ( ! empty( $results ) ) {

		$floor_required = floor( $required );

		$required_exploded = explode( '.', $required );
		if ( count( $required_exploded ) >=2  ){
			$required_exploded[1] = intval( $required_exploded[1] );
			if ( $required_exploded[1] >= 8 && $required_exploded[1] <= 99 ) {
				$floor_required = ceil( $required );
			}
		}

		$list[ $plane ] = [
			'needed'  => $results['required'],
			'runway'  => $planes[ $plane ]['runway'],
			'range'   => $planes[ $plane ]['range'],
			'cost'    => ! empty( $planes[ $plane ]['cost'] ) ? $planes[ $plane ]['cost'] * $floor_required : 0,
			'speed'   => ! empty( $planes[ $plane ]['speed'] ) ? $planes[ $plane ]['speed'] : 0,
			'fuel'    => ! empty( $planes[ $plane ]['fuel'] ) ? $planes[ $plane ]['fuel'] * $floor_required * $distance : 0,
			'a-check' => ! empty( $planes[ $plane ]['a-check'] ) ? $planes[ $plane ]['a-check'] * $floor_required: 0,
			'layout'  => sprintf( '%d/%d/%d', $results['layout']['y'], $results['layout']['j'], $results['layout']['f'] ),
			'total'   => sprintf( '%d/%d/%d', $results['pax_per_day']['y'], $results['pax_per_day']['j'], $results['pax_per_day']['f'] ),
			'demand'  => sprintf( '%d/%d/%d', $results['demand']['y'], $results['demand']['j'], $results['demand']['f'] ),
		];
	}

	uasort(
		$list,
		function( $a, $b ) {
			return $a['speed'] < $b['speed'] ? -1 : 1;
		}
	);
}

echo PHP_EOL;

foreach ( $list as $plane => $data ) {

	$lines = [
		sprintf( '%s, Need %.2f', $plane, $data['needed'] ),
		sprintf( 'Range %s', number_format( $data['range'], 0 ) ),
		sprintf( 'Cost $%s',  number_format( $data['cost'], 0 ) ),
		sprintf( 'Speed %s',  number_format( $data['speed'], 0 ) ),
		sprintf( 'Fuel %s lbs',  number_format( $data['fuel'], 0 ) ),
		sprintf( 'Runway %s', number_format( $data['runway'], 0 ) ),
		sprintf( 'A-Check $%s',  number_format( $data['a-check'], 0 ) ),
		sprintf( 'Layout %s', $data['layout'] ),
		sprintf( 'Total  %s', $data['total'] ),
		sprintf( 'Demand %s', $data['demand'] ),
	];

	$output = implode( PHP_EOL, $lines );

	echo $output . PHP_EOL . PHP_EOL; // phpcs:ignore
}
