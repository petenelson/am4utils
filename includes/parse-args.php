<?php

namespace AM4Utils\Functions;

/**
 * Parse command-line args.
 *
 * @param  array $args The argvs param.
 * @return array
 */
function parseArgs($args) {
	// Copied from php-parse.
	$operations = [];
	$files = [];
	$attributes = [
		'with-column-info' => false,
		'with-positions' => false,
		'with-recovery' => false,
	];

	array_shift( $args );
	$parseOptions = true;
	foreach ( $args as $arg ) {
		if ( ! $parseOptions ) {
			$files[] = $arg;
			continue;
		}

		var_dump( $arg );

		switch ( $arg ) {
			case '--plane':
			case '-p':
				$operations[] = 'plane';
				break;
			case '--':
				$parseOptions = false;
				break;
			default:
				if ($arg[0] === '-') {
					showHelp("Invalid operation $arg.");
				} else {
					$files[] = $arg;
				}
		}
	}

	return [
		'operations' => $operations,
		'files'      => $files,
		'attributes' => $attributes,
	];
}

function showHelp($error = '') {
	if ($error) {
		fwrite(STDERR, $error . "\n\n");
	}
	fwrite($error ? STDERR : STDOUT, <<<OUTPUT
Usage: php-parse [operations] file1.php [file2.php ...]
   or: php-parse [operations] "<?php code"
Turn PHP source code into an abstract syntax tree.

Operations is a list of the following options (--dump by default):

	-d, --dump              Dump nodes using NodeDumper
	-p, --pretty-print      Pretty print file using PrettyPrinter\Standard
	-j, --json-dump         Print json_encode() result
		--var-dump          var_dump() nodes (for exact structure)
	-N, --resolve-names     Resolve names using NodeVisitor\NameResolver
	-c, --with-column-info  Show column-numbers for errors (if available)
	-P, --with-positions    Show positions in node dumps
	-r, --with-recovery     Use parsing with error recovery
	-h, --help              Display this page

Example:
	php-parse -d -p -N -d file.php

	Dumps nodes, pretty prints them, then resolves names and dumps them again.


OUTPUT
	);
	exit($error ? 1 : 0);
}