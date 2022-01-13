<?php

if ( count( $argv ) < 2 ) {
	echo 'Pass a route';
	echo PHP_EOL;
	exit;
}

$rounding_disabled = false;
$based_on          = 'f';

$route        = strtoupper( $argv[1] );
$plane_filter = isset( $argv[2] ) ? strtoupper( $argv[2] ) : false;

echo '******';
echo PHP_EOL;
echo sprintf( 'Route: %s', $route );
echo PHP_EOL;

/**
 * Gets a list of airports.
 *
 * @return array
 */
function get_airports() {
	$airports = json_decode( file_get_contents( 'airports.json' ), true );

	foreach ( array_keys( $airports ) as $key ) {
		if ( ! isset( $airports[ $key ]['runway'] ) ) {
			$airports[ $key ]['runway'] = 0;
		}
	}

	return $airports;
}

/**
 * Gets a list of planes.
 *
 * @return array
 */
function get_planes() {
	$planes = json_decode( file_get_contents( 'planes.json' ), true );

	if ( empty( $planes ) ) {
		var_dump( 'Planes file is broken.' ); die();
	}

	$defaults = [
		'runway'  => 0,
		'cost'    => 0,
		'a-check' => 0,
	];

	foreach ( array_keys( $planes ) as $key ) {
		
		foreach ( $defaults as $default => $value ) {
			if ( ! isset( $planes[ $key ][ $default ] ) ) {
				$planes[ $key ][ $default ] = $value;
			}
		}
	}

	return $planes;
}

/**
 * Gets a list of all routes.
 *
 * @return array
 */
function get_routes() {

	$route_list = [];

	$files = scandir( 'routes' );
	$airports = get_airports();

	foreach ($files as $filename ) {

		$pathinfo = pathinfo( $filename );

		if ( 'json' === $pathinfo['extension'] ) {
			$hub = strtoupper( $pathinfo['filename'] );

			$filename = dirname( __FILE__ ) . '/routes/' . $filename;

			$routes = file_get_contents( $filename );
			$routes = json_decode( $routes, true );
			
			if ( empty( $routes ) ) {
				var_dump( $filename ); die();
			} else {

				foreach ( $routes as $destination => $data ) {

					$data['runway'] = 0;
					if ( isset( $airports[ $destination ]['runway'] ) ) {
						$data['runway'] = $airports[ $destination ]['runway'];
					}

					$route_list[ $hub . '-' . $destination ] = $data;
				}
			}
		}
	}

	return $route_list;
}

$list = [];

// Make sure each plane has the range.
$plane_keys = [];
$planes     = get_planes();
$routes     = get_routes();
$airports   = get_airports();

if ( ! isset( $routes[ $route ] ) ) {
	echo '******';
	echo PHP_EOL;
	echo 'Route not found';
	echo PHP_EOL;
	exit;
}

$distance = $routes[ $route ]['distance'];
$runway   = 0;

$hub_destionation = explode( '-', $route );
if ( isset( $airports[ $hub_destionation[1] ] ) ) {
	$runway = $airports[ $hub_destionation[1] ]['runway'];
}

echo sprintf( 'Distance: %s', $distance );
echo PHP_EOL;
echo sprintf( 'Runway: %s', $runway );
echo PHP_EOL;
echo '******';
echo PHP_EOL;

if ( ! empty( $plane_filter ) ) {
	$plane_keys = array_keys( $planes );
} else {

	foreach ( array_keys( $planes ) as $plane_key ) {

		$range        = $planes[ $plane_key ]['range'];
		$plane_runway = $planes[ $plane_key ]['runway'];

		$can_add = $distance <= $range;

		if ( ! empty( $runway ) && ! empty( $plane_runway ) && $plane_runway > $runway ) {
			$can_add = false;
		}

		if ( $can_add ) {
			$plane_keys[] = $plane_key;
		}
	}

}

foreach ( $plane_keys as $plane ) {

	if ( 'j' === $based_on || 'f' === $based_on ) {

		$adjustment = 0;

		$results = calculate_demand( $plane, $route, $based_on, $adjustment );

		$demand_check = 'y';

		if ( 'f' === $based_on ) {
			$demand_check = 'j';
		}

		while ( ! empty( $results ) && $results['pax_per_day'][ $demand_check ] > ( $results['demand'][ $demand_check ] + ( $results['demand'][ $demand_check ] * .10 ) ) && $adjustment < 200 ) {
			$adjustment++;
			$results = calculate_demand( $plane, $route, $based_on, $adjustment );
		}
	}

	if ( ! empty( $results ) ) {
		$needed = $results['needed'];

		if ( empty( $plane_filter ) ) {
			if ( $needed > 5.1 ) {
				$results = false;
			}

			if ( $needed < 0.95  ) {
				$results = false;
			}

			if ( ! $rounding_disabled ) {
				for ( $i = 1; $i <= 5; $i++ ) {
					if ( $needed > $i + 0.12 && $needed < $i + 0.95 ) {
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

		$floor_needed = floor( $needed );

		$needed_exploded = explode( '.', $needed );
		if ( count( $needed_exploded ) >=2  ){
			$needed_exploded[1] = intval( $needed_exploded[1] );
			if ( $needed_exploded[1] >= 8 && $needed_exploded[1] <= 99 ) {
				$floor_needed = ceil( $needed );
			}
		}

		$seats_left = $planes[ $plane ]['seats'] - ( $results['layout']['j'] * 2 ) - ( $results['layout']['f'] * 3 );

		if ( $seats_left > 0 ) {
			// $results['layout']['y'] = $seats_left;
			// $results['pax_per_day']['y']
		}

		$list[ $plane ] = [
			'needed'  => $results['needed'],
			'runway'  => $planes[ $plane ]['runway'],
			'range'   => $planes[ $plane ]['range'],
			'cost'    => ! empty( $planes[ $plane ]['cost'] ) ? $planes[ $plane ]['cost'] * $floor_needed : 0,
			'fuel'    => ! empty( $planes[ $plane ]['fuel'] ) ? $planes[ $plane ]['fuel'] * $floor_needed * $distance : 0,
			'a-check' => ! empty( $planes[ $plane ]['a-check'] ) ? $planes[ $plane ]['a-check'] * $floor_needed: 0,
			'layout'  => sprintf( '%d/%d/%d', $results['layout']['y'], $results['layout']['j'], $results['layout']['f'] ),
			'total'   => sprintf( '%d/%d/%d', $results['pax_per_day']['y'], $results['pax_per_day']['j'], $results['pax_per_day']['f'] ),
			'demand'  => sprintf( '%d/%d/%d', $results['demand']['y'], $results['demand']['j'], $results['demand']['f'] ),
		];
	}

	uasort(
		$list,
		function( $a, $b ) {
			return $a['cost'] > $b['cost'] ? -1 : 1;
		}
	);
}

foreach ( $list as $plane => $data ) {

	$lines = [
		sprintf( '%s, Need %.2f', $plane, $data['needed'] ),
		sprintf( 'Range %s', number_format( $data['range'], 0 ) ),
		sprintf( 'Cost $%s',  number_format( $data['cost'], 0 ) ),
		sprintf( 'Fuel %s lbs',  number_format( $data['fuel'], 0 ) ),
		sprintf( 'Runway %s', number_format( $data['runway'], 0 ) ),
		sprintf( 'A-Check $%s',  number_format( $data['a-check'], 0 ) ),
		sprintf( 'Layout %s', $data['layout'] ),
		sprintf( 'Total  %s', $data['total'] ),
		sprintf( 'Demand %s', $data['demand'] ),
	];

	$output = implode( PHP_EOL, $lines );

	echo $output . PHP_EOL . PHP_EOL;
}

exit;

function calculate_demand( $plane, $route, $based_on = 'y', $based_on_adjustment = 10 ) {

	$routes = get_routes();
	$planes = get_planes();

	$pax_adjust = 0.88;

	$seats = $planes[ $plane ]['seats'];
	$range = $planes[ $plane ]['range'];
	$speed = $planes[ $plane ]['speed'];

	$distance = $routes[ $route ]['distance'];
	$demand   = $routes[ $route ]['demand'];

	$results = [
		'plane'           => $plane,
		'distance'        => $distance,
		'needed'          => 0,
		'flights_per_day' => 0,
		'pax_per_day'     => 0,
		'layout'          => [],
		'ratio'           => [],
		'total_demand'    => 0,
		'demand'          => $demand,
		'has_range'       => true,
	];

	$ratio = [
		'y' => 0,
		'j' => 0,
		'f' => 0,
	];
	
	$total_demand = $demand['y'] + $demand['j'] + $demand['f'];

	if ( 'f' === $based_on ) {
		$total_demand = $demand['j'] + $demand['f'];
	}

	$results['total_demand'] = $total_demand;

	if ( 'f' === $based_on ) {
		$ratio['y'] = 0; $demand['y'] / $total_demand;
	} else {
		$ratio['y'] = $demand['y'] / $total_demand;
	}

	$ratio['j'] = $demand['j'] / $total_demand;
	$ratio['f'] = $demand['f'] / $total_demand;

	$results['ratio'] = $ratio;

	// -1 Business Class seat (J-Class) takes the space of 2 Economy Class seats (Y-Class)
	// -1 First Class seat (F-Class) takes the space of 3 Economy Class seats (Y-Class)
	$target = [
		'y' => $seats, // floor( $seats * $ratio['y'] ),
		'j' => floor( floor( $seats * $ratio['j'] ) / 2 ),
		'f' => floor( floor( $seats * $ratio['f'] ) / 3 ),
	];

	if ( 'f' === $based_on ) {
		$target['y'] = 0;
	}

	if ( 'j' === $based_on ) {
		for ( $i=0; $i <= $based_on_adjustment ; $i++ ) { 
			$target = add_j_seat( $target );
		}
	}

	if ( 'f' === $based_on ) {
		for ( $i=0; $i <= $based_on_adjustment ; $i++ ) { 
			$target = add_f_seat( $target );
		}
	}

	$time            = $distance / $speed;
	$flights_per_day = 16 / $time;

	$results['flights_per_day'] = $flights_per_day;

	$seats_left = $seats - ( $target['j'] * 2 ) - ( $target['f'] * 3 );

	if ( 'y' !== $based_on && $seats_left > 0 ) {
		$target['y'] = $seats_left;
	}

	$results['layout'] = $target;

	$pax_per_day = [
		'y' => $target['y'] * $flights_per_day * $pax_adjust,
		'j' => $target['j'] * $flights_per_day * $pax_adjust,
		'f' => $target['f'] * $flights_per_day * $pax_adjust,
	];

	$needed = $demand[ $based_on ] / $pax_per_day[ $based_on ];

	$needed = round( $needed, 2 );

	$results['pax_per_day'] = $pax_per_day;
	$results['needed']      = $needed;

	$results['pax_per_day']['y'] = $results['pax_per_day']['y'] * $needed;
	$results['pax_per_day']['j'] = $results['pax_per_day']['j'] * $needed;
	$results['pax_per_day']['f'] = $results['pax_per_day']['f'] * $needed;

	// if ( $results['pax_per_day']['y'] <= 0 ) {
	// 	return false;
	// }

	return $results;
}

/**
 * Adds one J seat and removes two Y seats.
 *
 * @param array $layout Seat layout.
 * @return array
 */
function add_j_seat( $layout ) {

	$layout['j']++;
	$layout = remove_y_seat( $layout );
	$layout = remove_y_seat( $layout );

	return $layout;
}

/**
 * Adds one F seat and removes two J seats.
 *
 * @param array $layout Seat layout.
 * @return array
 */
function add_f_seat( $layout ) {

	$layout['f']++;
	$layout['j']--;
	$layout['j']--;

	$layout = remove_y_seat( $layout );
	$layout = remove_y_seat( $layout );
	$layout = remove_y_seat( $layout );

	return $layout;
}

/**
 * Removes one y seat.
 *
 * @param array $layout Seat layout.
 * @return array
 */
function remove_y_seat( $layout ) {
	if ( $layout['y'] > 0 ) {
		$layout['y'] --;
	}

	return $layout;
}

/**
 * Calculates a layout to meet specific demand.
 *
 * @param  string $plane    The plane ID (ex: CRJ 100)
 * @param  string $route    The route (ex: KDFW-CYYZ)
 * @return array
 */
function calculate_layout( $plane, $route, $based_on = 'y' ) {

	$debug = true;


	// If we have all f seats, and we 










}


function add_seat( layout, type ) {

}
