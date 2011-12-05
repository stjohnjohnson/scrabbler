<?php
/**
 * API Page
 *
 * This is the internal API for the Referee to communicate back and forth with
 * the webapp
 *
 * @link https://github.com/stjohnjohnson/Scrabbler
 * @author St. John Johnson <st.john.johnson@gmail.com>
 */

namespace Pages;

use \Helpers\DB,
    \Exception,
    \PDO;

class Api extends \Helpers\Page {
  protected $output = self::OUTPUT_JSON;

  public function index() {
    return array(
        'methods' => array('get','post')
    );
  }

  public function get() {
    // Only get pending games that haven't been picked up for 15 minutes
    $stmt = DB::prepare("UPDATE game
      SET accepted_time = NOW(),
          game_id = LAST_INSERT_ID(game_id)
      WHERE outcome = 'pending' AND
            accepted_time < TIMESTAMPADD(MINUTE, -15, NOW())");
    $stmt->execute();

    // Get Game ID
    $id = DB::lastInsertId();

    // If we don't have an ID, bail
    if ($id == 0) {
      throw new Exception('No Games Pending', 400);
    }

    // Otherwise, load the details
    $stmt = DB::prepare('SELECT game_id, bot1_id, bot2_id FROM game WHERE game_id = ?');
    $stmt->execute(array($id));
    return $stmt->fetchObject();
  }

  public function post() {
    // Check for JSON data sent
    $data = @json_decode(file_get_contents("php://input"), true);
    if (is_array($data)) {
      foreach ($data as $key => $value) {
        $_REQUEST[$key] = $value;
      }
    }

    // Expecting three items: game, players and request
    foreach (array('game','players','moves') as $key) {
      if (!isset($_REQUEST[$key])) {
        throw new Exception('Missing Expected Field: ' . $key, 400);
      }
    }
    $game = $_REQUEST['game'];
    $players = $_REQUEST['players'];
    $moves = $_REQUEST['moves'];


  }
}