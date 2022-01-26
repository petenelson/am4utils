<?php

require_once dirname( __FILE__) . '/../vendor/autoload.php'; 

$files = [
	'functions.php',
];

foreach ( $files as $file ) {
	require_once $file;
}
