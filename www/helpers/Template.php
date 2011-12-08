<?php
/**
 * Template class
 *
 * Simple file loading and pattern replacing
 *
 * @link https://github.com/stjohnjohnson/Scrabbler
 * @author St. John Johnson <st.john.johnson@gmail.com>
 */

namespace Helpers;

use \Exception;

class Template {
  /**
   * Static Caller
   *
   * @param string $name
   * @param array $arguments
   * @return string
   */
  public static function __callStatic($name, $arguments) {
    $filename = 'templates/' . $name . '.phtml';

    // Check if template exists
    if (!is_file($filename)) {
      throw new Exception('Unable to find Template: ' . $name, 500);
    }

    list($replacement, $that) = array_pad($arguments, 2, new \stdClass());

    // Load/Parse Template
    ob_start();
    include($filename);
    $template = trim(ob_get_clean());

    // Replace if array is provided
    if (is_array($replacement) || is_object($replacement)) {
      $replace = array();
      foreach ($replacement as $key => $value) {
        $replace['{' . $key . '}'] = $value;
      }

      $template = strtr($template, $replace);
    }

    return $template;
  }
}