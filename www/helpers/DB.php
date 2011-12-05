<?php
/**
 * DB class
 *
 * Simple Singleton access to a PDO instance
 *
 * @link https://github.com/stjohnjohnson/Scrabbler
 * @author St. John Johnson <st.john.johnson@gmail.com>
 */

namespace Helpers;

use \Exception,
    \PDO;

class DB {
  private static $obj = null;

  /**
   * Initializes the PDO object
   *
   * @param string $dsn
   * @param string $username
   * @param string $password
   */
  public static function setup($dsn, $username, $password) {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    self::$obj = $pdo;
  }

  /**
   * Static Caller
   *
   * @param string $name
   * @param array $arguments
   * @return varied
   */
  public static function __callStatic($name, $arguments) {
    if (self::$obj === null) {
      throw new Exception('PDO Class Not Initialized');
    }

    return call_user_func_array(array(self::$obj, $name), $arguments);
  }
}