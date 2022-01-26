<?php

namespace AM4Utils\Functions;

/**
 * Gets the contents of a file.
 *
 * @param  string $filename The file name.
 * @return string
 */
function get_file_contents( $filename ) {
	$dir = dirname( dirname( __FILE__ ) );
	$file = $dir . '/data/' . $filename;
	return file_get_contents( $file );
}

/**
 * Gets a list of airports.
 *
 * @return array
 */
function get_airports() {
	$airports = json_decode( get_file_contents( 'airports.json' ), true );

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
	$planes = json_decode( get_file_contents( 'planes.json' ), true );

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
 * Gets a list of flights.
 *
 * @return array
 */
function get_flights() {
	$flights = json_decode( get_file_contents( 'flights.json' ), true );

	if ( empty( $flights ) ) {
		var_dump( 'Flights file is broken.' ); die();
	}

	$defaults = [];

	foreach ( array_keys( $flights ) as $key ) {
		
		foreach ( $defaults as $default => $value ) {
			if ( ! isset( $flights[ $key ][ $default ] ) ) {
				$flights[ $key ][ $default ] = $value;
			}
		}
	}

	return $flights;
}

/**
 * Gets a list of all routes.
 *
 * @return array
 */
function get_routes() {

	$route_list = [];

	$route_dir = dirname( dirname( __FILE__ ) ) . '/data/routes';

	$files = scandir( $route_dir );
	$airports = get_airports();

	foreach ($files as $filename ) {

		$pathinfo = pathinfo( $filename );

		if ( 'json' === $pathinfo['extension'] ) {
			$hub = strtoupper( $pathinfo['filename'] );

			$filename = $route_dir . '/' . $filename;

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

/**
 * Gets a route.
 *
 * @param  string $route The route (ex: KDFW-KIAH)
 * @return array
 */
function get_route( $route ) {

	$routes = get_routes();

	if ( isset( $routes[ $route ] ) ) {
		return $routes[ $route ];
	} else {
		return false;
	}
}

/**
 * Gets a plane.
 *
 * @param  string $plane The plane name (ex: B727-800).
 * @return array
 */
function get_plane( $plane ) {

	$planes = get_planes();

	if ( isset( $planes[ $plane ] ) ) {
		return $planes[ $plane ];
	} else {
		return false;
	}
}

/**
 * Adds a seat and adjusts the layout.
 *
 * @param array  $layout The seat layout.
 * @param string $type   The seat type.
 * @return array
 */
function add_seat( $layout, $type ) {

	// -1 Business Class seat (J-Class) takes the space of 2 Economy Class seats (Y-Class)
	// -1 First Class seat (F-Class) takes the space of 3 Economy Class seats (Y-Class)
	switch ( $type ) {
		case 'y':
			$layout['y']++;
			$layout['j'] = $layout['j'] - 0.5;
			break;

		case 'j':
			$layout['y'] = $layout['y'] - 2;
			$layout['j']++;
			break;

		case 'f':
			$layout['j'] = $layout['j'] - 1.5;
			$layout['f']++;
			break;
	}

	return $layout;
}

/**
 * Gets a list of seat types.
 *
 * @return array
 */
function get_seat_types() {
	return [ 'y', 'j', 'f' ];
}

/**
 * Gets an empty seat layout.
 *
 * @return array
 */
function get_empty_seat_layout() {
	$layout = [];

	foreach ( get_seat_types() as $type ) {
		$layout[ $type ] = 0;
	}

	return $layout;
}

/**
 * Calculates the flight time in minutes for a plane on a route.
 *
 * @param  array $plane The plane data.
 * @param  array $route The route data.
 * @return int
 */
function calculate_flight_time( $plane, $route ) {

	$distance   = $route['distance'];
	$speed      = $plane['speed'];
	$km_per_min = $speed / 60;
	$minutes    = $distance / $km_per_min;
	$minutes    = intval( ceil ( $minutes ) );

	return $minutes;
}

/**
 * Calculates the flights per day.
 *
 * @param  array $plane The plane data.
 * @param  array $route The route data.
 * @param  int   $day   The hours used for each day, defaults to 16 (playing
 *                      8am to midnight).
 * @return int
 */
function calculate_flights_per_day( $plane, $route, $day = 16 ) {

	$flight_time = calculate_flight_time( $plane, $route );
	if ( empty( $flight_time ) ) {
		var_dump( $plane, $route ); die();
	}

	$flights_per_day = ( $day * 60 ) / $flight_time;
	$flights_per_day = intval( ceil( $flights_per_day ) );

	return $flights_per_day;
}

/**
 * Calculates the demand ratio.
 *
 * @param  array $demand Array with y/j/f demand (number of pax).
 * @return array
 */
function calculate_demand_ratio( $demand ) {

	$total_pax = $demand['y'] + $demand['j'] + $demand['f'];

	$ratio = [];

	foreach ( get_seat_types() as $type ) {
		$ratio[ $type ] = round( $demand[ $type ] / $total_pax, 2 );
	}

	return $ratio;
}

/**
 * Calculates the initial seat layout for a plane based on the demand ratio.
 *
 * @param  array  $demand_ratio The demand ratio.
 * @param  string $plane        The plane name (ex: B737-800).
 * @return arrary
 */
function calculate_initial_seat_layout( $demand_ratio, $plane ) {

	$plane  = get_plane( $plane );
	$layout = get_empty_seat_layout();

	// Allocate all of the initial seats to y.
	$layout['y'] = $plane['seats'];

	return $layout;
}

/**
 * Calculates the pax per day based on the layout and the number of planes.
 *
 * @param  int   $flights_per_day Number of flights per day.
 * @param  array $layout          The seat layout.
 * @param  float $num_planes      The number of plans.
 * @return array
 */
function calculate_pax_per_day( $flights_per_day, $layout, $num_planes, $pax_adjust = 1 ) {

	$pax_per_day = [
		'y' => $layout['y'] * ceil( $flights_per_day * $num_planes * $pax_adjust ),
		'j' => $layout['j'] * ceil( $flights_per_day * $num_planes * $pax_adjust ),
		'f' => $layout['f'] * ceil( $flights_per_day * $num_planes * $pax_adjust ),
	];

	return $pax_per_day;
}

/**
 * Calculates if the pax per day meets the demand.
 *
 * @param array $pax_per_day List of pax per day.
 * @param array $demand      List of pax demand.
 * @return array
 */
function calculate_meets_demand( $pax_per_day, $demand ) {

	$meets_demand = [];
	$all_met      = true;

	foreach( get_seat_types() as $seat_type ) {

		$demand_plus_buffer = $demand[ $seat_type ] * 1.15;

		$within_range = $pax_per_day[ $seat_type ] >= $demand[ $seat_type ] && // meets base demand
			$pax_per_day[ $seat_type ] <= $demand_plus_buffer;

		$meets_demand[ $seat_type ] = $within_range;

		if ( ! $meets_demand[ $seat_type ] ) {
			$all_met = false;
		}
	}

	$meets_demand['all'] = $all_met;

	return $meets_demand;
}

/**
 * Calculates the number of f seats available.
 *
 * @param  int   $num_seats The total number of seats.
 * @param  array $layout    The modified seat layout.
 * @return int
 */
function calculate_f_seats_available( $num_seats, $layout ) {

	$seats_available = $num_seats - $layout['y'] - ( $layout['j'] * 2 );
	$seats_available = intval( floor( $seats_available / 3 ) );

	return $seats_available;
}

/**
 * Calculates all possible seat layouts.
 *
 * @param  int $num_seats The number of seats.
 * @return array
 */
function calculate_all_seat_layouts( $num_seats ) {

	$layouts = [];
	$layout  = get_empty_seat_layout();

	$layout['y'] = $num_seats;

	// Make a list of possible y/j combos.
	$y_seats = $num_seats;
	$j_seats = 0;

	while ( $y_seats >= 0 ) {

		$layout = [
			'y' => $y_seats,
			'j' => $j_seats,
			'f' => 0,
		];

		$layouts[]     = $layout;
		$y_j_layouts[] = $layout;

		$y_seats--;

		$j_seats = floor( ( $num_seats - $y_seats ) / 2 );

	}

	foreach ( array_reverse( $layouts ) as $y_j_layout ) {

		while ( $y_j_layout['j'] >= 0 ) {

			$y_j_layout['j']--;

			if ( $y_j_layout['j'] >= 0 ) {

				$f_seats_available = calculate_f_seats_available( $num_seats, $y_j_layout );
				if ( $f_seats_available > 0 ) {

					$f_layout = [
						'y' => $y_j_layout['y'],
						'j' => $y_j_layout['j'],
						'f' => $f_seats_available,
					];

					$layouts[] = $f_layout;
				}
			}
		}
	}

	return $layouts;
}

/**
 * Calculates the number of planes required, based on the route's demand.
 *
 * @param  string $route_name The route name (ex: KDFW-KIAH).
 * @param  string $plane_name The plane name( ex: B727-800).
 * @return array
 */
function calculate_planes_required( $route_name, $plane_name, $pax_adjust = 1 ) {
	global $rounding_disabled;
	global $num_planes;

	$debug      = false;
	$max_planes = 8.2;

	$additonal_cache_keys = [
		strval( $pax_adjust ),
	];

	$cache_key = "{$route_name}:{$plane_name}:" . md5( json_encode( $additonal_cache_keys ) );
	$cache_dir = dirname( dirname( __FILE__ )  ) . '/.cache';

	if ( ! file_exists( $cache_dir ) ) {
		mkdir( $cache_dir ); // phpcs:ignore
	}

	$cache_file = $cache_dir . '/' . $cache_key . '.json';

	if ( file_exists( $cache_file ) ) {
		$results = file_get_contents( $cache_file );
		$results = json_decode( $results, true );

		if ( ! empty( $results['required'] ) && $rounding_disabled || ! $rounding_disabled ) {
			return $results;
		}
	}

	$route = get_route( $route_name );
	$plane = get_plane( $plane_name );

	$demand_ratio = calculate_demand_ratio( $route['demand'] );
	$layout       = calculate_initial_seat_layout( $demand_ratio, $plane_name );

	$results = [
		'required'        => 0,
		'route'           => $route_name,
		'demand'          => $route['demand'],
		'plane'           => $plane_name,
		'flights_per_day' => calculate_flights_per_day( $plane, $route ),
		'pax_per_day'     => get_empty_seat_layout(),
		'ratio'           => $demand_ratio,
		'layout'          => $layout,
	];

	$meets_demand = [
		'y'   => false,
		'j'   => false,
		'f'   => false,
		'all' => false,
	];

	$flights_per_day = $results['flights_per_day'];
	$demand          = $route['demand'];

	// Base number of planes to start with.
	// $num_planes = 0.9;
	$plane_incr = 0.01;
	$loop       = 0;

	$all_layouts = calculate_all_seat_layouts( $plane['seats'] );

	while ( $num_planes <= $max_planes ) {

		foreach ( $all_layouts as $layout ) {

			$pax_per_day = calculate_pax_per_day( $flights_per_day, $layout, $num_planes, $pax_adjust );

			$results['pax_per_day'] = $pax_per_day;

			// Does this layout meet demand?
			$meets_demand = calculate_meets_demand( $pax_per_day, $demand );

			if ( $debug ) {
				echo_debug( $plane_name, $num_planes, $layout, $pax_per_day, $demand, $meets_demand );
			}

			if ( $meets_demand['all'] ) {
				break;
			}
		}

		if ( $meets_demand['all'] ) {
			$results['required'] = $num_planes;
			break;
		}

		$num_planes = $num_planes + $plane_incr;

		if ( ! $rounding_disabled ) {
			if ( $num_planes > 1.1 && $num_planes < 1.9 ) {
				$num_planes  = 1.9;
			} else if ( $num_planes > 2.1 && $num_planes < 2.9 ) {
				$num_planes  = 2.9;
			} else if ( $num_planes > 3.1 && $num_planes < 3.9 ) {
				$num_planes  = 3.9;
			} else if ( $num_planes > 4.1 && $num_planes < 4.9 ) {
				$num_planes  = 4.9;
			} else if ( $num_planes > 5.1 && $num_planes < 5.9 ) {
				$num_planes  = 5.9;
			}

		}
	}

	$results['layout']       = $layout;
	$results['meets_demand'] = $meets_demand;

	file_put_contents( $cache_file, json_encode( $results, JSON_PRETTY_PRINT ) );

	return $results;
}

function echo_debug( $plane_name, $num_planes, $layout, $pax_per_day, $demand, $meets_demand ) {
	$line = sprintf(
			'%s, Required %s, Layout %s/%s/%s, Pax %s/%s/%s, Demand %s/%s/%s, Meets %s/%s/%s/%s',
			$plane_name,
			number_format( $num_planes, 2 ),
			$layout['y'],
			$layout['j'],
			$layout['f'],

			$pax_per_day['y'],
			$pax_per_day['j'],
			$pax_per_day['f'],
			$demand['y'],
			$demand['j'],
			$demand['f'],
			$meets_demand['y'] ? 'Yes' : 'No',
			$meets_demand['j'] ? 'Yes' : 'No',
			$meets_demand['f'] ? 'Yes' : 'No',
			$meets_demand['all'] ? 'Yes' : 'No',
		);

	echo $line; // phpcs:ignore
	echo PHP_EOL;
}
