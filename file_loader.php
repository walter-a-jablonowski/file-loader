<?php

function preload_sources( $bases, $id_strings, $cache_fil, $config = [])
{
  $loader = new FileLoader( $cache_fil, $config );
  return $loader->preload( $bases, $id_strings );
}

function source( $base, $id_string, $cache_fil, $config = [])
{
  $loader = new FileLoader( $cache_fil, $config );
  return $loader->source( $base, $id_string );
}

function find_desc( $dir, $descPattern = '/^-\s*DESC/')
{
  foreach( scandir($dir) as $file) {
    if( preg_match($descPattern, $file))
      return "$dir/$file";
  }

  return null;
}

class FileLoader
{
  private array  $config;
  private string $cacheFil;

  public function __construct( $cacheFil, $config = [] )
  {
    $this->cacheFil = $cacheFil;

    $config = array_merge([
      'prefixes'    => ['#', 'id-'],
      'loadFolder'  => false,
      'descPattern' => '/^-\s*DESC/',
      'sharedCache' => null,
      'msgCallback' => null
    ], $config );

    $config['prefixes'] = is_null( $config['prefixes']) ? [''] : $config['prefixes'];  // enables ['prefixes' => null]

    $this->config = $config;
  }

  /*

  ARGS
    bases:     string or array of strings
    idStrings: * or array of strings

  */
  public function preload( $bases, $idStrings )
  {
    if( is_string($bases))
      $bases = [$bases];

    foreach( $idStrings as &$idString )
    {
      $idString = str_replace('id-', '', $idString );
      $idString = ltrim( $idString, "# ");  // maybe problems
      // $idString = preg_replace('/^[#\s]+/', '', $idString);
    }

    // Load

    $r = [];
    foreach( $bases as $base)
      // TASK: AI sees problems when keeping much data in mem ins of writing it in file
      $r = array_merge( $r, $this->recurse( $base, $idStrings ));

    file_put_contents( $this->cacheFil, json_encode( $r, JSON_PRETTY_PRINT));

    // Shared cache (update)

    if( isset($this->config['sharedCache']) ) {
      $sharedCache = is_file($this->config['sharedCache']) 
                   ? json_decode( file_get_contents($this->config['sharedCache']), true )
                   : [];
      
      foreach ($r as $idString => $fil) {
        $sharedCache[$idString] = $fil;
      }
      file_put_contents( $this->config['sharedCache'], json_encode( $sharedCache, JSON_PRETTY_PRINT));
    }
  }

  public function source( $base, $idString )
  {
    $idString = str_replace('id-', '', $idString );
    $idString = ltrim( $idString, "# ");  // maybe problems
    // $idString = preg_replace('/^[#\s]+/', '', $idString);

    // Sub fil behind id string

    $sub = null;

    if( strpos( $idString, '/') !== false )  // sub fil behind id
    {
      $a   = explode('/', $idString);
      $idString = array_shift($a);
      $sub = implode('/', $a);
    }

    // Load cache and return fil if still same place

    $cache = is_file( $this->cacheFil)
           ? json_decode( file_get_contents( $this->cacheFil), true)
           : null;
  
    if( isset( $cache[$idString]) && file_exists( $cache[$idString]))  // may be fil or fld
    {
      if( $sub )
      {
        $r = "$cache[$idString]/$sub";

        if( is_dir($r) )
          return find_desc( $r, $this->config['descPattern'] );
        else
        {
          $r .= ( strpos( $sub, '.') !== false ? '' : '.md');
    
          if( file_exists($r) )
            return $r;
          else
            return null;
        }
      }
      elseif( is_dir($cache[$idString]))
        if( ! $this->config['loadFolder'])
          return find_desc( $cache[$idString], $this->config['descPattern'] );
        else
          return $cache[$idString];
      else
        return $cache[$idString];
    }

    // Shared cache

    if( isset($this->config['sharedCache']) && is_file($this->config['sharedCache']))
    {
      $sharedCache = json_decode( file_get_contents($this->config['sharedCache']), true );
      
      if( isset($sharedCache[$idString]) && file_exists($sharedCache[$idString]) ) {
        
        // Found in shared cache, update local cache
        
        if( ! is_array($cache))
          $cache = [];
        
        $cache[$idString] = $sharedCache[$idString];
        file_put_contents( $this->cacheFil, json_encode( $cache, JSON_PRETTY_PRINT));
        
        // Return the file with same logic as above
        
        if( $sub )
        {
          $r = "$cache[$idString]/$sub";
          
          if( is_dir($r) )
            return find_desc( $r, $this->config['descPattern'] );
          else {
            $r .= ( strpos( $sub, '.') !== false ? '' : '.md');
            
            if( file_exists($r) )
              return $r;
            else
              return null;
          }
        }
        elseif( is_dir($cache[$idString])) {
          if( ! $this->config['loadFolder'])
            return find_desc( $cache[$idString], $this->config['descPattern'] );
          else
            return $cache[$idString];
        }
        else
          return $cache[$idString];
      }
    }

    // Fil moved: upd cache

    if( isset($this->config['msgCallback']) && is_callable($this->config['msgCallback']) ) {
      call_user_func($this->config['msgCallback'], 'update_cache_start', $idString);
    }

    if( ! isset( $cache[$idString]))  // was missing in cache
    {
      $r = $this->recurse( $base, $idString );
      $fil = $r[$idString] ?? null;
    }
    else                              // was in cache but file missing
      $fil = $this->relativeSrc( dirname($cache[$idString]), $idString, $base );

    if( isset($this->config['msgCallback']) && is_callable($this->config['msgCallback']) ) {
      call_user_func($this->config['msgCallback'], 'update_cache_end');
    }

    // Save cache

    if( ! is_null($fil))
    {
      $cache[$idString] = $fil;

      file_put_contents( $this->cacheFil, json_encode( $cache, JSON_PRETTY_PRINT));
      
      // Shared cache (update)

      if( isset($this->config['sharedCache']) ) {
        $sharedCache = is_file($this->config['sharedCache']) 
                     ? json_decode( file_get_contents($this->config['sharedCache']), true )
                     : [];
        
        $sharedCache[$idString] = $fil;
        file_put_contents( $this->config['sharedCache'], json_encode( $sharedCache, JSON_PRETTY_PRINT));
      }
    }

    // Return

    if( ! $fil )
      return null;
    elseif( $sub )
    {
      $r = "$fil/$sub";

      if( is_dir($r) )
        return find_desc( $r, $this->config['descPattern'] );
      else
      {
        $r .= ( strpos( $sub, '.') !== false ? '' : '.md');
  
        if( file_exists($r) )
          return $r;
        else
          return null;
      }
    }
    elseif( is_dir($fil))
    {
      if( ! $this->config['loadFolder'])
        return find_desc( $fil, $this->config['descPattern'] );
      else
        return $fil;
    }
    else
      return $fil;
  }

  private function recurse( $dir, $idStrings = '*' )
  {
    // Unify args

    if( $idStrings == '*')          // all ids
      $idStrings = ['*'];
    elseif( is_string($idStrings))  // single id as string
      $idStrings = [$idStrings];

    // Proc

    $r = [];

    foreach( scandir($dir) as $fil)
    {
      if( in_array( $fil, ['.', '..']))
        continue;

      if( isset($this->config['msgCallback']) && is_callable($this->config['msgCallback']) ) {
        call_user_func($this->config['msgCallback'], 'processing_file', "$dir/$fil");
      }

      // id = * => get all ids
      
      $rg = array_map('preg_quote', $this->config['prefixes']);
      $rg = '/' . implode('([\w-]+)|', $rg) . '([\w-]+)/';  // /#([\w-]+)|id-([\w-]+)/, [\w-] any word char [a-zA-Z0-9_-]

      if( in_array('*', $idStrings) && preg_match( $rg, $fil, $a))
      {
        $ident = isset($a[2]) ? $a[2] : $a[1];
        $r[$ident] = "$dir/$fil";
      }

      // Get given ids only

      elseif( ! in_array('*', $idStrings))
      {
        foreach( $idStrings as $id )
        {
          // look through all id prefix variants + id-

          foreach( $this->config['prefixes'] as $prefix )
            // if( strpos( $fil, "$prefix$id") !== false )
            if( str_contains($fil, "$prefix$id") )  // may be fil or fld
            {
              $r[$id] = "$dir/$fil";
              break;
            }

          // stop if given ids only and have all

          if( count($r) === count($idStrings))
            return $r;
        }
      }

      if( is_dir("$dir/$fil"))
      {
        $r = array_merge( $r, $this->recurse("$dir/$fil", $idStrings));

        if( ! in_array('*', $idStrings) && count($r) === count($idStrings))
          return $r;
      }
    }

    // Shared cache (update)

    if( isset($this->config['sharedCache']) ) {
      $sharedCache = is_file($this->config['sharedCache']) 
                   ? json_decode( file_get_contents($this->config['sharedCache']), true )
                   : [];
      
      foreach ($r as $idString => $fil) {
        $sharedCache[$idString] = $fil;
      }
      file_put_contents( $this->config['sharedCache'], json_encode( $sharedCache, JSON_PRETTY_PRINT));
    }

    return $r;
  }

  private function relativeSrc( string $dir, string $idString, string $baseDir, array $seenDirs = [] ) : ?string
  {
    // Find first existing dir

    while( ! is_dir($dir) && $dir !== $baseDir )
      $dir = dirname($dir);

    if( ! is_dir($dir) )
      return null;

    // Look in current dir

    foreach( scandir($dir) as $fil )
    {
      if( in_array( $fil, ['.', '..']))
        continue;

      if( isset($this->config['msgCallback']) && is_callable($this->config['msgCallback']) ) {
        call_user_func($this->config['msgCallback'], 'processing_file_relative', "$dir/$fil");
      }

      // Check if file or folder has id

      foreach( $this->config['prefixes'] as $prefix )
      {
        if( str_contains($fil, "$prefix$idString") )
          return "$dir/$fil";
      }

      // Check subdirs (except those that we already have)
      // TASK: gets a few dirs double but is in processed so might make no difference for performace, alternative: use recurse()

      if( is_dir("$dir/$fil") && ! in_array("$dir/$fil", $seenDirs) ) {

        $result = $this->relativeSrc("$dir/$fil", $idString, $baseDir, $seenDirs); 

        if( $result )
          return $result;
      }
    }

    // If we haven't found the file and we're in no base dir, go up one level

    if( $dir !== $baseDir ) {
      $seenDirs[] = $dir;  // TASK: maybe less good for performance
      return $this->relativeSrc( dirname($dir), $idString, $baseDir, $seenDirs);
    }

    return null;
  }
}

?>
