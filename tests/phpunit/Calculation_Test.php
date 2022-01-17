<?php // phpcs:ignore

namespace AM4Utils\Tests;

use PHPUnit\Framework\TestCase;
use function AM4Utils\Functions\calculate_flight_time;
use function AM4Utils\Functions\calculate_flights_per_day;
use function AM4Utils\Functions\calculate_demand_ratio;

/**
 * Calculation tests
 */
class Calculation_Tests extends TestCase {

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
}
