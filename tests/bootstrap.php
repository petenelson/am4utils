<?php
/**
 * Unit tests bootstrap.
 */

namespace AM4Utils\Tests;

/**
 * Setup the unit test environment
 *
 * @return void
 */
function bootstrap() {

	$root = get_install_root();

	require_once $root . 'vendor/autoload.php';
	require_once $root . 'includes/functions.php';
}

/**
 * Gets the current install root.
 *
 * @return string
 */
function get_install_root() {
	return dirname( dirname( __FILE__ ) ) . '/';
}

// Start up the unit tests env.
bootstrap();
