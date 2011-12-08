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

use \Exception,
    \Helpers\Template;

abstract class Page {
  const OUTPUT_JSON = 'JSON';
  const OUTPUT_HTML = 'HTML';

  protected $output = self::OUTPUT_HTML;
  protected $title = '';

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

  public function output($object) {
    $method = 'output' . $this->output;

    // Check if there's an error
    if (is_a($object, 'Exception')) {
      // Set error code
      header($_SERVER['SERVER_PROTOCOL'] . ' ' . $object->getCode());
    }

    return $this->$method($object);
  }

  private function outputHTML($object) {
    if (is_a($object, 'Exception')) {
      $this->title = 'Error ' . $object['code'];
      $object = Template::error($object);
    }

    die(Template::base(array(
        'title' => $this->title,
         'body' => $object
    )));
  }

  private function outputJSON($object) {
    // Check for exceptions
    if (is_a($object, 'Exception')) {
      $object = array(
          'code' => $object->getCode(),
       'message' => $object->getMessage()
      );
    }

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
      $obj->output($e);
    }
  }

  public abstract function index();
}