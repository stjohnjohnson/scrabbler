<?php
/**
 * Scrabbler Launch Point
 *
 * @link https://github.com/stjohnjohnson/Scrabbler
 * @author St. John Johnson <st.john.johnson@gmail.com>
 */

// Start Output Buffering
// @todo Generalize this
ob_start();

// Autoloader
spl_autoload_register(function($class) {
  $file = implode('/', explode('\\', lcfirst($class))) . '.php';
  if (is_file($file)) {
    require_once $file;
  }
});

// Set Exception Handler
set_exception_handler(function($e) {
  // Clean output
  ob_clean();

  // @todo Something or something
  var_dump($e->getMessage());
  var_dump($e->getTraceAsString());

  return;
});

// Setup Database Access
// @todo Store in a configuration file
\Helpers\DB::setup('mysql:dbname=scrabbler;host=127.0.0.1;port=3300', 'root', '');

// Figure out the path /bot/12345/edit = array('bot','12345','edit')
$path = trim(strtolower($_SERVER['REQUEST_URI']), '/');
// Find ? and stop there
if (strpos($path, '?') !== false) {
  $path = substr($path, 0, strpos($path, '?'));
}
$_SERVER['path'] = array_pad(explode('/', $path), 2, '');

// Figure out what page to load
$class = 'pages\\' . $_SERVER['path'][0];

// Default to Dashboard
if (!class_exists($class)) {
  $class = 'pages\\dashboard';
}

$class::execute($_SERVER['path'][1]);