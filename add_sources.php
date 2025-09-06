<?php

require_once 'file_loader.php';

// add an id "la" somewhere
// php add_sources_debug.php la

$bases = [
  'DEBUG' => 'debug'
];

$cache    = 'debug/cache.json';

$base_key = $argc > 1 ? $argv[1] : 'DEBUG';
$base     = $bases[$base_key] ?? '.';
$ids      = $argc > 2 ? array_slice( $argv, 2 ) : ['*'];

add_sources( [$base], $ids, $cache, [
  'msgCallback' => function( $type, $arg1 = null ) {
    echo "$type";
    if( $arg1 )
      echo ": $arg1";
    echo "\n";
  }
]);

?>
