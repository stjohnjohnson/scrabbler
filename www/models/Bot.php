<?php
/**
 * Bot Model
 *
 * @link https://github.com/stjohnjohnson/Scrabbler
 * @author St. John Johnson <st.john.johnson@gmail.com>
 */

namespace Models;

use \Helpers\DB,
    \Exception,
    \PDO;

class Bot {
  public $id = null;

  public function __construct($bot_id) {
    $this->id = $bot_id;
  }

  public function load() {
    $stmt = DB::prepare("SELECT * FROM game WHERE game_id = ?");
    $stmt->execute(array($this->id));
    $obj = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($obj === null) {
      throw new Exception('Unable to find Game #' . $this->id, 404);
    }

    foreach ($obj as $key => $value) {
      $this->$key = $value;
    }

    return $this;
  }
}