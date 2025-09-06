<?php

require_once 'file_loader.php';

define('DEBUG', 'debug');

// add an id "la" somewhere
// php add_sources_debug.php la

$cache = 'debug/cache.json';
$base  = constant( $argv[1] );

$ids   = $argc > 2 ? array_slice( $argv, 2 ) : ['*'];

add_sources( [$base], $ids, $cache, [
  'msgCallback' => function( $type, $arg1 = null ) {
    echo "$type";
    if( $arg1 )
      echo ": $arg1";
    echo "\n";
  }
]);

?>
