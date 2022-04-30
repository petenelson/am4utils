<?php

use AM4Utils\Functions;

global $rounding_disabled;

$rounding_disabled = ! empty( $plane_filter );
$ignore_range      = false;

$rounding_disabled = true;

if ( empty( $route ) ) {
	echo '******';
	echo PHP_EOL;
	echo 'Route required, ex: find-planes KJFK-KAUS';
	echo PHP_EOL;
	echo '******';
	echo PHP_EOL;
	exit;
}

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
$price    = $routes[ $route ]['ticket_price'];
$runway   = 0;

$hub_destionation = explode( '-', $route );
if ( isset( $airports[ $hub_destionation[1] ] ) ) {
	$runway = $airports[ $hub_destionation[1] ]['runway'];
}

if ( $airports[ $hub_destionation[0] ]['runway'] < $runway ) {
	$runway = $airports[ $hub_destionation[0] ]['runway'];	
}

echo sprintf( 'Distance: %s', $distance ); // phpcs:ignore
echo PHP_EOL;
echo sprintf( 'Shortest Runway: %s', $runway ); // phpcs:ignore
echo PHP_EOL;
echo sprintf( 'Demand: %s/%s/%s', $demand['y'], $demand['j'], $demand['f'] ); // phpcs:ignore
echo PHP_EOL;
echo sprintf( 'Price: $%s/$%s/$%s', number_format( $price['y'], 0 ), number_format( $price['j'], 0 ), number_format( $price['f'], 0 ) ); // phpcs:ignore
echo PHP_EOL;
echo '******';
echo PHP_EOL;

$initial_plane_keys = [];

if ( ! empty( $plane_filter ) ) {
	$initial_plane_keys = [ $plane_filter ];

	if ( 'COMMON' === $plane_filter ) {
		$initial_plane_keys = [
			'A380-800',
			'B747-400D',
			'B747-8',
			'A330-900NEO',
			'A330-800NEO',
			'B777-300LR',
			'B777-200',
			'B777-8X',
			'B787-10',
			'B777-200ER',
			'IL-96-400',
			'A350-1000',
			'B777-300ER',
			'B777-300',
			'B747SP',
			'A350-900',
			'MD-11ER',
			'B747-400ER',
			'A340-600',
			'MC-21-300',
			'MC-21-400',
			'B727-200',
		];

		$plane_filter = false;
	}
} else {
	$initial_plane_keys = array_keys( $planes );
}

foreach ( $initial_plane_keys as $plane_key ) {

	if ( ! isset( $planes[ $plane_key ] ) ) {
		echo 'Plane ' . $plane_key . ' does not exist.' . PHP_EOL; // phpcs:ignore
		die();
	}

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

	echo 'Calculating ' . $plane; // phpcs:ignore
	echo PHP_EOL;

	$results = Functions\calculate_planes_required( $route, $plane, $pax_adjust );

	if ( ! empty( $results ) ) {
		$required = $results['required'];

		if ( empty( $plane_filter ) ) {
			if ( $required > 6.1 ) {
				$results = false;
			}

			if ( $required < 0.5  ) {
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

		$income_per_flight = 
			( $results['layout']['y'] * $results['ticket_price']['y'] ) +
			( $results['layout']['j'] * $results['ticket_price']['j'] ) +
			( $results['layout']['f'] * $results['ticket_price']['f'] );

		$list[ $plane ] = [
			'needed'  => $results['required'],
			'runway'  => $planes[ $plane ]['runway'],
			'range'   => $planes[ $plane ]['range'],
			'profit_per_hour' => $planes[ $plane ]['profit_per_hour'],
			'cost'    => ! empty( $planes[ $plane ]['cost'] ) ? $planes[ $plane ]['cost'] * $floor_required : 0,
			'speed'   => ! empty( $planes[ $plane ]['speed'] ) ? $planes[ $plane ]['speed'] : 0,
			'fuel'    => ! empty( $planes[ $plane ]['fuel'] ) ? $planes[ $plane ]['fuel'] * $floor_required * $distance : 0,
			'a-check' => ! empty( $planes[ $plane ]['a-check'] ) ? $planes[ $plane ]['a-check'] * $floor_required: 0,
			'layout'  => sprintf( '%d/%d/%d', $results['layout']['y'], $results['layout']['j'], $results['layout']['f'] ),
			'total'   => sprintf( '%d/%d/%d', $results['pax_per_day']['y'], $results['pax_per_day']['j'], $results['pax_per_day']['f'] ),
			'demand'  => sprintf( '%d/%d/%d', $results['demand']['y'], $results['demand']['j'], $results['demand']['f'] ),
			'flights_per_day'   => $results['flights_per_day'],
			'income_per_flight' => $income_per_flight * $pax_adjust,
			'income_per_day'    => $income_per_flight * $results['flights_per_day'],
		];

		$list[ $plane ]['fuel'] = floor( $list[ $plane ]['fuel'] * 0.9 ); // Note, has the 10% fuel cost decrease.

		$list[ $plane ]['fuel_cost'] = floor( ( $list[ $plane ]['fuel'] / 1000 ) * 700 );

		$list[ $plane ]['income_per_flight'] = $list[ $plane ]['income_per_flight'] - $list[ $plane ]['fuel_cost'];

		$list[ $plane ]['income_per_day'] = $list[ $plane ]['income_per_flight'] * $list[ $plane ]['flights_per_day'] ;

		$list[ $plane ]['flight_time'] = Functions\calculate_flight_time( $planes[ $plane ], $routes[ $route ] );
		$list[ $plane ]['income_per_minute'] = $list[ $plane ]['income_per_flight'] / $list[ $plane ]['flight_time'];
		$list[ $plane ]['income_per_hour']   = $list[ $plane ]['income_per_minute'] * 60;

	}

	uasort(
		$list,
		function( $a, $b ) {
			// return $a['speed'] < $b['speed'] ? -1 : 1;
			return $a['income_per_day'] < $b['income_per_day'] ? -1 : 1;
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
		sprintf( 'Fuel Cost $%s',  number_format( $data['fuel_cost'], 0 ) ),
		sprintf( 'Runway %s', number_format( $data['runway'], 0 ) ),
		sprintf( 'A-Check $%s',  number_format( $data['a-check'], 0 ) ),
		sprintf( 'Profit/Hr $%s',  number_format( $data['profit_per_hour'], 0 ) ),
		sprintf( 'Layout %s', $data['layout'] ),
		sprintf( 'Total  %s', $data['total'] ),
		sprintf( 'Demand %s', $data['demand'] ),
		sprintf( 'Flight Time: %d', $data['flight_time'] ),
		sprintf( 'Flights/Day: %d', $data['flights_per_day'] ),
		sprintf( 'Income/Flight: $%s', number_format( $data['income_per_flight'], 0 ) ),
		sprintf( 'Income/Hour: $%s', number_format( $data['income_per_hour'] ) ),
		sprintf( 'Income/Day/Plane: $%s', number_format( $data['income_per_day'] ) ),
		sprintf( 'Total Income/Day: $%s', number_format( $data['income_per_day'] * $data['needed'] ) ),
	];

	$output = implode( PHP_EOL, $lines );

	echo $output . PHP_EOL . PHP_EOL; // phpcs:ignore
}
