<?php

require_once 'file_loader.php';

// add an id "la" somewhere
// php add_sources_debug.php la

$bases = ['debug'];
$ids   = $argc > 1 ? array_slice( $argv, 1 ) : ['*'];
$cache = 'debug/cache.json';

add_sources( $bases, $ids, $cache, [
  'msgCallback' => function( $type, $arg1 = null ) {
    echo "$type";
    if( $arg1 )
      echo ": $arg1";
    echo "\n";
  }
]);

?>
