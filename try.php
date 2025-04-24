<?php

// Remove te /debug folder and run this 2 times
// second run: move a file before (see case 6 in html page)

// Helper function to create test directory structure
function create_test_structure() 
{
  $debug_dir = 'debug';
  if( ! is_dir($debug_dir))
    mkdir($debug_dir);

  // Create base directories with unique ID ranges
  $bases = [
    'base_1' => ['start_id' => 100, 'special_cases' => true],
    'base_2' => ['start_id' => 200, 'special_cases' => false]
  ];

  foreach( $bases as $base => $config ) 
  {
    $base_path = "$debug_dir/$base";
    if( ! is_dir($base_path))
      mkdir($base_path);

    // Create nested structure with 5 levels, passing the ID start range
    create_nested_structure($base_path, 1, 5, $config['start_id']);

    // Create special test cases only in the first base
    if( $config['special_cases'])
      create_special_test_cases($base_path);
  }
}

function create_nested_structure( $parent, $current_depth, $max_depth, $id_start ) 
{
  if( $current_depth > $max_depth )
    return;

  // Create files with unique IDs in current directory
  // Each depth level gets its own range: depth 1 = 1xx, depth 2 = 2xx, etc.
  $id_base = $id_start + ($current_depth * 10);
  
  // Create a file with id- prefix
  $file_content = "Test content for depth $current_depth";
  file_put_contents("$parent/id-test$id_base.md", $file_content);
  
  // Create a file with # prefix
  file_put_contents("$parent/#tag$id_base.md", $file_content);
  
  // Create a subdirectory with description file
  $sub_dir = "$parent/folder$current_depth";
  if( ! is_dir($sub_dir)) 
  {
    mkdir($sub_dir);
    file_put_contents("$sub_dir/- DESC.md", "Description for folder$current_depth");
  }

  create_nested_structure($sub_dir, $current_depth + 1, $max_depth, $id_start);
}

function create_special_test_cases( $base_path ) 
{
  // Case 3.1: Folder with ID containing subfiles
  $test_folder = "$base_path/id-folder1";
  if( ! is_dir($test_folder))
  {
    mkdir($test_folder);
    mkdir("$test_folder/sub");
    file_put_contents("$test_folder/sub/file.md", "Test content for sub file");
    file_put_contents("$test_folder/sub/misc.txt", "Test content for misc file");
  }

  // Case 3.2: Folder with ID containing DESC file in subfolder
  $test_folder = "$base_path/id-folder2";
  if( ! is_dir($test_folder))
  {
    mkdir($test_folder);
    mkdir("$test_folder/subfolder");
    file_put_contents("$test_folder/subfolder/- DESC.md", "Description for subfolder");
  }
}

require_once 'file_loader.php';

if( ! file_exists('debug'))
{
  $first_run = true;
  create_test_structure();
}
else
  $first_run = false;

$cache_file = 'debug/cache.json';

?>
<!DOCTYPE html>
<html>
<head>
  <title>File Loader</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      max-width: 1000px;
      margin: 20px auto;
      padding: 0 20px;
      line-height: 1.6;
    }
    .test-section {
      background: #f5f5f5;
      border-radius: 5px;
      padding: 15px;
      margin: 15px 0;
    }
    .test-header {
      color: #2c3e50;
      border-bottom: 2px solid #3498db;
      padding-bottom: 5px;
      margin-bottom: 10px;
    }
    .file-path {
      font-family: monospace;
      background: #e8e8e8;
      padding: 2px 5px;
      border-radius: 3px;
    }
    .success { color: #27ae60; }
    .error { color: #c0392b; }
    .info {
      background: #fff3cd;
      border-left: 4px solid #ffc107;
      padding: 10px;
      margin: 10px 0;
    }
  </style>
</head>
<body>
  <h1>File Loader</h1>
<?php

// Define message callback function
function message_handler( $type, $arg1 = null ) {  // or use the msgs lib
  if( ! ob_get_level()) ob_start();
  
  switch( $type ) {
    case 'update_cache_start':
      echo "<b>Updating cache for $arg1 ...</b><br><br>\n\n";
      break;
    case 'update_cache_end':
      echo "<br><br>\n\n";
      break;
    case 'processing_file':
      echo "<b>Processing:</b> $arg1<br>\n";
      break;
    case 'processing_file_relative':
      echo "<b>Processing (relative):</b> $arg1<br>\n";
      break;
  }
  
  ob_flush();
  flush();
}

$config = [
  'prefixes'    => ['#', 'id-'],
  'msgCallback' => 'message_handler',
  'descPattern' => '/^-\s*DESC/'  // Default pattern for description files
];

// Case 1: Preload specific IDs

?>
<div class="test-section">
  <h2 class="test-header">Case 1: Preload specific IDs</h2>
<?php

if( $first_run )
{
  ob_start();
  preload_sources(['debug/base_1', 'debug/base_2'], ['test110', 'test210'], $cache_file, $config);
  $output = ob_get_clean();
  echo "<pre>$output</pre>";
}
else
  echo "<pre>Skipped in second run (cause must be done in case 2 for relative search)</pre>";
  
?>
</div>

<!-- Case 2: Load a cached ID -->

<div class="test-section">
  <h2 class="test-header">Case 2: Load a cached ID</h2>
<?php

$file = source('debug/base_1', 'test110', $cache_file, $config);
echo $file 
  ? "<div class='success'>Found file: <span class='file-path'>" . htmlspecialchars($file) . "</span></div>"
  : "<div class='error'>File missing</div>";

?>
</div>

<!-- Case 3: Load a non-cached ID -->

<div class="test-section">
  <h2 class="test-header">Case 3: Load a non-cached ID</h2>
<?php

if( $first_run )
{
  $file = source('debug/base_1', 'test130', $cache_file, $config);
  echo $file 
    ? "<div class='success'>Found file: <span class='file-path'>" . htmlspecialchars($file) . "</span></div>"
    : "<div class='error'>File missing</div>";
}
else
  echo "<pre>Skipped in second run (makes no sense)</pre>";
?>

</div>

<!-- Case 3.1: Load ID with sub path -->

<div class="test-section">
  <h2 class="test-header">Case 3.1: Load ID with sub path (default .md extension)</h2>
  <div class="info">
    Tests finding a folder via ID 'folder1' then accessing 'sub/file' which gets .md extension
  </div>
<?php

$file = source('debug/base_1', 'folder1/sub/file', $cache_file, $config);
echo $file 
  ? "<div class='success'>Found file: <span class='file-path'>" . htmlspecialchars($file) . "</span></div>"
  : "<div class='error'>File missing</div>";
?>

</div>

<!-- Case 3.2: Load ID with subfolder (find DESC) -->

<div class="test-section">
  <h2 class="test-header">Case 3.2: Load ID with subfolder (find DESC file)</h2>
  <div class="info">
    Tests finding a folder via ID 'folder2' then looking for - DESC file in subfolder
  </div>
<?php

$file = source('debug/base_1', 'folder2/subfolder', $cache_file, $config);
echo $file 
  ? "<div class='success'>Found file: <span class='file-path'>" . htmlspecialchars($file) . "</span></div>"
  : "<div class='error'>File missing</div>";
?>

</div>

<!-- Case 4: Load a non-existent ID -->

<div class="test-section">
  <h2 class="test-header">Case 4: Load a non-existent ID</h2>
<?php
$file = source('debug/base_1', 'nonexistent', $cache_file, $config);
echo $file 
  ? "<div class='success'>Found file: <span class='file-path'>" . htmlspecialchars($file) . "</span></div>"
  : "<div class='error'>File missing</div>";
?>

</div>

<!-- Case 5: Find description file -->

<div class="test-section">
  <h2 class="test-header">Case 5: Find description file</h2>
<?php

$desc_file = find_desc('debug/base_1/folder1', $config['descPattern']);
echo $desc_file 
  ? "<div class='success'>Found description file: <span class='file-path'>" . htmlspecialchars($desc_file) . "</span></div>"
  : "<div class='error'>Description file missing</div>";
?>

</div>

<!-- Case 6: Test file relocation -->

<div class="test-section">
  <h2 class="test-header">Case 6: Test file relocation</h2>
  <div class="info">
    <strong>Instructions for second run:</strong><br>
    1. Move a file (e.g., <span class='file-path'>id-test110.md</span>) to a different location<br>
    2. Run this script again to test the relocation functionality<br>
    3. The FileLoader will search for the new location (most likely case 2)
  </div>
</div>

</body>
</html>
