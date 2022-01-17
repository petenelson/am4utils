<?php

namespace AM4Utils\Functions;

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

	$flights_per_day = ( $day * 60 ) / calculate_flight_time( $plane, $route );
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
