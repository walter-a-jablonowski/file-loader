<?php

require_once 'file_loader.php';

// add an id "la" somewhere
// php add_sources.php DEBUG la ...

define('DEBUG', 'debug');

if( $argc < 3 ) {
  echo "Error: At least 2 arguments required: <base_constant> <id1> [id2] ...\n";
  exit(1);
}

if( ! defined( $argv[1] ) ) {
  echo "Error: First argument must be a defined constant (e.g., DEBUG)\n";
  exit(1);
}

$cache = 'debug/cache.json';
$base  = constant( $argv[1] );
$ids   = array_slice( $argv, 2 );

add_sources( [$base], $ids, $cache, [
  'msgCallback' => function( $type, $arg1 = null ) {
    echo "$type";
    if( $arg1 )
      echo ": $arg1";
    echo "\n";
  }
]);

?>
