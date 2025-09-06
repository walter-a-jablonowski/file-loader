<?php

require_once 'file_loader.php';

$bases = [ ... ];
$ids   = $argc > 1 ? array_slice( $argv, 1 ) : ['*'];
$cache = ... ;

add_sources( $bases, $ids, $cache, [
  'msgCallback' => function( $type, $arg1 = null ) {
    echo "$type";
    if( $arg1 )
      echo ": $arg1";
    echo "\n";
  }
]);

?>
