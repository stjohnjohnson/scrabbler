<?php
/**
 * Page class
 *
 * Basic abstract class for all pages in this site.  Provides built in exception
 * handling and various output formats.
 *
 * @link https://github.com/stjohnjohnson/Scrabbler
 * @author St. John Johnson <st.john.johnson@gmail.com>
 */

namespace Helpers;

use \Exception;

abstract class Page {
  const OUTPUT_JSON = 'JSON';
  const OUTPUT_HTML = 'HTML';

  protected $output = self::OUTPUT_HTML;

  /**
   * Changes the method's output type
   *
   * @param string $type
   */
  public function changeOutput($type) {
    if (method_exists($this, 'output' . $type)) {
      $this->output = $type;
    } else {
      throw new Exception('Invalid Output Type: ' . $type);
    }
  }

  public function output($array, $isError = false) {
    $method = 'output' . $this->output;

    // Check if there's an error
    if ($isError) {
      // Set error code
      header($_SERVER['SERVER_PROTOCOL'] . ' ' . $array['code']);

      // Do something else?
    }

    return $this->$method($array);
  }

  private function outputHTML($array) {
    var_dump($array);
    die();
  }

  private function outputJSON($array) {
    ob_clean();

    // Converts all numbers to ints, etc
    array_walk_recursive($array, function(&$v) {
      if (ctype_digit($v)) {
        $v = (int) $v;
      }
    });

    header('Content-type: application/json');
    die(json_encode($array));
  }

  public static function execute($method) {
    $class = get_called_class();
    $obj = new $class();

    // Default to index
    if (!method_exists($obj, $method)) {
      $method = 'index';
    }

    try {
      $obj->output($obj->$method());
    } catch (Exception $e) {
      $obj->output(array(
          'message' => $e->getMessage(),
             'code' => $e->getCode()
      ), true);
    }
  }

  public abstract function index();
}