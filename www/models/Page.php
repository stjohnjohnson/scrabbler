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

namespace Models;

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

  public function output($object, $isError = false) {
    $method = 'output' . $this->output;

    // Check if there's an error
    if ($isError) {
      // Set error code
      header($_SERVER['SERVER_PROTOCOL'] . ' ' . $object['code']);

      // Do something else?
    }

    return $this->$method($object);
  }

  private function outputHTML($object) {
    var_dump($object);
    die();
  }

  private function outputJSON($object) {
    ob_clean();

    // Converts all numbers to ints, etc
    if (is_array($object)) {
      array_walk_recursive($object, function(&$v) {
        if (ctype_digit($v)) {
          $v = (int) $v;
        }
      });
    }

    header('Content-type: application/json');
    die(json_encode($object));
  }

  public static function execute($params) {
    $class = get_called_class();
    $obj = new $class();
    $method = $params[0];

    // IDs are passed in
    if (ctype_digit($method)) {
      if (isset($params[1])) {
        $method = $params[1];
      } else {
        $method = 'view';
      }
    }

    // Default to index (and no private)
    if (!method_exists($obj, $method) || $method[0] === '_') {
      $method = 'index';
    }

    try {
      $obj->output($obj->$method($params));
    } catch (Exception $e) {
      $obj->output(array(
          'message' => $e->getMessage(),
             'code' => $e->getCode()
      ), true);
    }
  }

  public abstract function index();
}