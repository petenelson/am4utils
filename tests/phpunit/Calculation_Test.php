<?php // phpcs:ignore

namespace AM4Utils\Tests;

use PHPUnit\Framework\TestCase;
use function AM4Utils\Functions\flight_time;

/**
 * Calculation tests
 */
class Calculation_Tests extends TestCase {

	/**
	 * Tests the flight time.
	 *
	 * @return void
	 */
	public function test_flight_time() {

		// 60 minutes.
		$flight_time = flight_time( [ 'speed' => 800 ], [ 'distance' => 800 ] );
		$this->assertSame( 60, $flight_time );
	
		// 25 minutes.
		$flight_time = flight_time( [ 'speed' => 752 ], [ 'distance' => 303 ] );
		$this->assertSame( 25, $flight_time );

		// 17.85 hours
		$flight_time = flight_time( [ 'speed' => 813 ], [ 'distance' => 14500 ] );
		$this->assertSame( 1071, $flight_time );
	}
}
