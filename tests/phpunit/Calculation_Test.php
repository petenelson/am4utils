<?php // phpcs:ignore

namespace AM4Utils\Tests;

use PHPUnit\Framework\TestCase;
use function AM4Utils\Functions\calculate_flight_time;
use function AM4Utils\Functions\calculate_flights_per_day;
use function AM4Utils\Functions\calculate_demand_ratio;
use function AM4Utils\Functions\calculate_planes_required;
use function AM4Utils\Functions\calculate_initial_seat_layout;
use function AM4Utils\Functions\get_empty_seat_layout;
use function AM4Utils\Functions\get_route;
use function AM4Utils\Functions\get_routes;
use function AM4Utils\Functions\get_plane;
use function AM4Utils\Functions\get_planes;

/**
 * Calculation tests
 */
class Calculation_Tests extends TestCase {

	/**
	 * Tests the calculation for the number of planes needed for a route.
	 *
	 * @return void
	 */
	public function test_calculate_planes_required() {

		$this->assertTrue( true ); // TODO.
		return;


		$route_name = 'KDFW-KSEA';
		$plane_name = 'ERJ 190-200';

		$planes_required = calculate_planes_required( $route_name, $plane_name );

		// var_dump( $planes_required );

		$this->assertIsArray( $planes_required );
		$this->assertSame( 'KDFW-KSEA', $planes_required['route'] );
		$this->assertSame( 'ERJ 190-200', $planes_required['plane'] );
		$this->assertSame( 1567, $planes_required['demand']['y'] );
		$this->assertSame( 597, $planes_required['demand']['j'] );
		$this->assertSame( 156, $planes_required['demand']['f'] );







	}

	/**
	 * Tests the flight time.
	 *
	 * @return void
	 */
	public function test_calculate_flight_time() {

		// 60 minutes.
		$flight_time = calculate_flight_time( [ 'speed' => 800 ], [ 'distance' => 800 ] );
		$this->assertSame( 60, $flight_time );
	
		// 25 minutes.
		$flight_time = calculate_flight_time( [ 'speed' => 752 ], [ 'distance' => 303 ] );
		$this->assertSame( 25, $flight_time );

		// 17.85 hours
		$flight_time = calculate_flight_time( [ 'speed' => 813 ], [ 'distance' => 14500 ] );
		$this->assertSame( 1071, $flight_time );
	}

	/**
	 * Tests the flights per day.
	 *
	 * @return void
	 */
	public function test_calculate_flights_per_day() {

		// 1 flight per day.
		$flights_per_day = calculate_flights_per_day( [ 'speed' => 813 ], [ 'distance' => 14500 ] );
		$this->assertSame( 1, $flights_per_day );
	}

	/**
	 * Tests the demand ratio calculation.
	 *
	 * @return void
	 */
	public function test_demand_ratio() {

		// This is 1570.
		// y = 64%.
		// j = 29%.
		// f = 8%.
		$demand = [
			'y' => '1000',
			'j' => '450',
			'f' => '120',
		];

		$ratio = calculate_demand_ratio( $demand );

		$this->assertIsArray( $ratio );
		
		$this->assertSame( 0.64, $ratio['y'] );
		$this->assertSame( 0.29, $ratio['j'] );
		$this->assertSame( 0.08, $ratio['f'] );

		// DFW to JFK, 1319.
		$demand = [
			'y' => '574',
			'j' => '623',
			'f' => '122',
		];

		$ratio = calculate_demand_ratio( $demand );

		$this->assertIsArray( $ratio );
		
		$this->assertSame( 0.44, $ratio['y'] );
		$this->assertSame( 0.47, $ratio['j'] );
		$this->assertSame( 0.09, $ratio['f'] );

	}

	/**
	 * Tests the calculation for the number of planes needed for a route.
	 *
	 * @return void
	 */
	public function test_get_routes_and_planes() {

		// First, run a test with invalid data.
		$route = get_route( 'Spruce Goose' );
		$plane = get_route( 'WKRP-KAUS' );

		$this->assertFalse( $route );
		$this->assertFalse( $plane );

		$routes = get_routes();

		$this->assertIsArray( $routes );
		$this->assertNotEmpty( $routes );

		$planes = get_planes();

		$this->assertIsArray( $planes );
		$this->assertNotEmpty( $planes );

		$route_name = 'KDFW-KSEA';
		$plane_name = 'ERJ 190-200';

		$this->assertArrayHasKey( $route_name, $routes );
		$this->assertArrayHasKey( $plane_name, $planes );

		$route = get_route( $route_name );
		$plane = get_plane( $plane_name );

		$this->assertIsArray( $route );
		$this->assertIsArray( $plane );

		$this->assertSame( 2669, $route['distance'] );
		$this->assertSame( 1567, $route['demand']['y'] );

		$this->assertSame( 122, $plane['seats'] );
		$this->assertSame( 2593, $plane['range'] );
		$this->assertSame( 845, $plane['speed'] );
	}

	/**
	 * Tests the calculation for the initial seat layout.
	 *
	 * @return void
	 */
	public function test_calculate_initial_seat_layout() {

		$demand = get_empty_seat_layout();
		$demand['y'] = 1000;
	
		$demand_ratio = calculate_demand_ratio( $demand );

		$layout = calculate_initial_seat_layout( $demand_ratio, 'B737-800' );

		$this->assertSame( 184, $layout['y'] );
		$this->assertSame( 0, $layout['j'] );
		$this->assertSame( 0, $layout['f'] );
	}

}
