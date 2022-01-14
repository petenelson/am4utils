<?php // phpcs:ignore

namespace AM4Utils\Tests;

use PHPUnit\Framework\TestCase;
use function AM4Utils\Functions\add_seat;

/**
 * Seat tests
 */
class Seat_Tests extends TestCase {

	/**
	 * Tests the seat layout.
	 *
	 * @return void
	 */
	public function test_add_seats() {

		$plane = [
			'seats' => 100,
		];

		// Set an initial layout.
		$layout = [
			'y' => $plane['seats'],
			'j' => 0,
			'f' => 0,
		];

		// Add one j seat.
		$layout = add_seat( $layout, 'j' );

		$this->assertIsArray( $layout );

		$this->assertSame( 98, $layout['y'] );
		$this->assertSame( 1, $layout['j'] );
		$this->assertSame( 0, $layout['f'] );

		$layout = [
			'y' => $plane['seats'],
			'j' => 0,
			'f' => 0,
		];

		// Add 26 j seats.
		for ( $i = 0; $i < 26; $i++ ) {
			$layout = add_seat( $layout, 'j' );
		}

		$this->assertSame( 48, $layout['y'] );
		$this->assertSame( 26, $layout['j'] );
		$this->assertSame( 0, $layout['f'] );

		// Add 10 f seats.
		for ( $i = 0; $i < 10; $i++ ) {
			$layout = add_seat( $layout, 'f' );
		}

		$this->assertSame( 48, $layout['y'] );
		$this->assertSame( 11, intval( floor( $layout['j'] ) ) );
		$this->assertSame( 10, $layout['f'] );

		// Add 4 y seats.
		for ( $i = 0; $i < 4; $i++ ) {
			$layout = add_seat( $layout, 'y' );
		}

		$this->assertSame( 52, $layout['y'] );
		$this->assertSame( 9, intval( floor( $layout['j'] ) ) );
		$this->assertSame( 10, $layout['f'] );

		// Add 2 f seats.
		for ( $i = 0; $i < 2; $i++ ) {
			$layout = add_seat( $layout, 'f' );
		}

		$this->assertSame( 52, $layout['y'] );
		$this->assertSame( 6, intval( floor( $layout['j'] ) ) );
		$this->assertSame( 12, $layout['f'] );
	}
}
