<?php // phpcs:ignore

namespace AM4Utils\Tests;

use PHPUnit\Framework\TestCase;
use function AM4Utils\Functions\add_seat;
use function AM4Utils\Functions\get_seat_types;
use function AM4Utils\Functions\get_empty_seat_layout;
use function AM4Utils\Functions\calculate_all_seat_layouts;
use function AM4Utils\Functions\calculate_f_seats_available;

/**
 * Seat tests
 */
class Seat_Tests extends TestCase {

	/**
	 * Tests the seat types.
	 *
	 * @return void
	 */
	public function test_get_seat_types() {

		$seat_types = get_seat_types();

		$this->assertIsArray( $seat_types );
		$this->assertCount( 3, $seat_types );

		$this->assertContains( 'y', $seat_types );
		$this->assertContains( 'j', $seat_types );
		$this->assertContains( 'f', $seat_types );
	}

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
		$layout = get_empty_seat_layout();
		$layout['y'] = $plane['seats'];

		// Add one j seat.
		$layout = add_seat( $layout, 'j' );

		$this->assertIsArray( $layout );

		$this->assertSame( 98, $layout['y'] );
		$this->assertSame( 1, $layout['j'] );
		$this->assertSame( 0, $layout['f'] );

		$layout = get_empty_seat_layout();
		$layout['y'] = $plane['seats'];

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

	/**
	 * Tests the calculation for the number of f seats availale
	 *
	 * @return void
	 */
	public function test_calculate_f_seats_available() {

		$num_seats = 10;

		// $layout = [
		// 	'y' => 0,
		// 	'j' => 0,
		// ];
		
		// $available = calculate_f_seats_available( $num_seats, $layout );
		// $this->assertSame( 3 , $available );

		// 10 - 2, 8 seats, should be two.
		$layout = [
			'y' => 0,
			'j' => 1
		];

		$available = calculate_f_seats_available( $num_seats, $layout );
		$this->assertSame( 2 , $available );

		$layout = [
			'y' => 2,
			'j' => 2,
		];

		$available = calculate_f_seats_available( $num_seats, $layout );
		$this->assertSame( 1 , $available );

		$layout = [
			'y' => 3,
			'j' => 0,
		];

		$available = calculate_f_seats_available( $num_seats, $layout );
		$this->assertSame( 2 , $available );

		$layout = [
			'y' => 7,
			'j' => 0,
		];

		$available = calculate_f_seats_available( $num_seats, $layout );
		$this->assertSame( 1 , $available );

		$layout = [
			'y' => 5,
			'j' => 1,
		];

		$available = calculate_f_seats_available( $num_seats, $layout );
		$this->assertSame( 1 , $available );

		$num_seats = 37;

		$layout = [
			'y' => 12,
			'j' => 6,
		];

		$available = calculate_f_seats_available( $num_seats, $layout );
		$this->assertSame( 4 , $available );
	}

	/**
	 * Tests getting a list of all possible seat layouts.
	 *
	 * @return void
	 */
	public function test_calculate_all_seat_layouts() {

		$make_list = function( $layout ) {
			return sprintf( '%s/%s/%s', $layout['y'], $layout['j'], $layout['f'] );
		};

		$layout_list = array_map( $make_list, calculate_all_seat_layouts( 10 ) );

		$this->assertContains( '10/0/0', $layout_list );
		$this->assertContains( '8/1/0', $layout_list );
		$this->assertContains( '0/5/0', $layout_list );
		$this->assertContains( '0/0/3', $layout_list );
		$this->assertContains( '0/1/2', $layout_list );
		$this->assertContains( '0/2/2', $layout_list );
		$this->assertContains( '1/2/1', $layout_list );

		$layout_list = array_map( $make_list, calculate_all_seat_layouts( 37 ) );

		$this->assertContains( '37/0/0', $layout_list );
		$this->assertContains( '19/9/0', $layout_list );
		$this->assertContains( '19/5/2', $layout_list );
		$this->assertContains( '10/0/9', $layout_list );
		$this->assertContains( '0/9/6', $layout_list );

	}
}
