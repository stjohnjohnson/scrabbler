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
    \Models\Move,
    \Models\Board,
    \Models\Series,
    \Exception,
    \PDO;

class Api extends \Models\Page {
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
            accepted_time < TIMESTAMPADD(MINUTE, -15, NOW())
      LIMIT 1");
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
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function post() {
    // Check for JSON data sent
    $data = @json_decode(file_get_contents("php://input"), true);

    if (is_array($data)) {
      foreach ($data as $key => $value) {
        $_REQUEST[$key] = $value;
      }
    }

    // Need better validation
    try {
      // Expecting three items: game, players and request
      $this->_validateArrayHasKeys($_REQUEST, array('game','players','moves'));

      // Validate Game
      $this->_validateArrayHasKeys($_REQUEST['game'], array('id'));
      // Validate Players
      if (!isset($_REQUEST['players'][0]) || !isset($_REQUEST['players'][1])) {
        throw new Exception('Incorrect Players Object');
      }
      $this->_validateArrayHasKeys($_REQUEST['players'][0], array('id','score'));
      $this->_validateArrayHasKeys($_REQUEST['players'][1], array('id','score'));
      // Validate Moves
      if (!is_array($_REQUEST['moves'])) {
        throw new Exception('Incorrect Moves Object');
      }
      foreach ($_REQUEST['moves'] as $key => $move) {
        $this->_validateArrayHasKeys($move, array('player','move','rack','score','time'));
      }
    } catch (Exception $e) {
      throw new Exception('Invalid Format: ' . $e->getMessage(), 400);
    }

    $game  = $_REQUEST['game'];
    $moves = $_REQUEST['moves'];
    list($player1, $player2) = $_REQUEST['players'];

    // Start Transaction
    try {
      DB::beginTransaction();

      // Load Game
      $stmt = DB::prepare("SELECT game_id, series_id, bot1_id, bot2_id, outcome
                           FROM game WHERE game_id = ?");
      $stmt->execute(array($game['id']));
      $game_db = $stmt->fetch(PDO::FETCH_ASSOC);

      // Check game exists
      if ($game_db === null) {
        throw new Exception('Game ID #' . $game['id'] . ' Not Found');
      }
      // Check game is active
      if ($game_db['outcome'] !== 'pending') {
        throw new Exception('Game ID #' . $game['id'] . ' Already Completed');
      }
      // Check players are correct
      if ($game_db['bot1_id'] !== (string)$player1['id'] ||
          $game_db['bot2_id'] !== (string)$player2['id']) {
        throw new Exception("Game ID #{$game['id']} Expected Players {$game_db['bot1_id']},{$game_db['bot2_id']} got {$player1['id']},{$player2['id']}");
      }

      // Simulate all the moves
      $board = new Board();
      foreach ($moves as $index => $move) {
        // Ensure the right player made a move
        if ((string)$move['player'] !== $game_db['bot' . (($index % 2) + 1) . '_id']) {
          throw new Exception('Expected Move from Player ' .
                  $game_db['bot' . (($index % 2) + 1) . '_id'] . ' not ' . $move['player']);
        }

        $obj = Move::fromString($move['move'], $board);
        $words = $board->play($obj);

        $stmt = DB::prepare("INSERT INTO move (game_id,bot_id,sequence,command,
                             rack,points,time,is_trade) VALUES (:game,:bot,
                             :sequence,:command,:rack,:points,:time,:trade)");
        $stmt->execute(array(
            ':game' => $game['id'],
             ':bot' => $move['player'],
        ':sequence' => $index,
         ':command' => $move['move'],
            ':rack' => $move['rack'],
          ':points' => $move['score'],
            ':time' => $move['time'],
           ':trade' => $obj->is_trade
        ));
        $move_id = DB::lastInsertId();

        $stmt = DB::prepare("INSERT INTO word (move_id,bot_id,word,coord) VALUES (:move,:bot,:word,:coord)");
        foreach ($words as $word) {
          $stmt->execute(array(
              ':move' => $move_id,
               ':bot' => $move['player'],
              ':word' => strtoupper($word->word),
             ':coord' => $word->position()
          ));
        }
      }

      // Update the game
      $winner = 0;
      $outcome = 'complete';

      // Check for disqualification
      $disqualify = 'none';
      $disqualify_id = 0;
      if (isset($player1['disqualify']) && $player1['disqualify'] !== 'none') {
        $disqualify_id = $player1['id'];
        $disqualify = $player1['disqualify'];
      } elseif (isset($player2['disqualify']) && $player2['disqualify'] !== 'none') {
        $disqualify_id = $player2['id'];
        $disqualify = $player2['disqualify'];
      } else {
        // No disqualifications
        if ($player1['score'] > $player2['score']) {
          $winner = $player1['id'];
        } elseif ($player1['score'] < $player2['score']) {
          $winner = $player2['id'];
        } else {
          $outcome = 'tie';
        }
      }
      $stmt = DB::prepare("UPDATE game SET score1 = :bot1, score2 = :bot2,
        winner_id = :winner, outcome = :outcome, disqualify = :disqualify,
        disqualify_id = :disqualify_id, completed_time = NOW()
        WHERE game_id = :game");
      $stmt->execute(array(
            'bot1' => $player1['score'],
            'bot2' => $player2['score'],
          'winner' => $winner,
         'outcome' => $outcome,
      'disqualify' => $disqualify,
   'disqualify_id' => $disqualify_id,
            'game' => $game['id']
      ));

      $series = new Series($game_db['series_id']);
      $series->gameCompleted();
      DB::commit();
    } catch (Exception $e) {
      DB::rollback();
      throw $e;
    }

    return true;
  }

  private function _validateArrayHasKeys($array, $keys) {
    if (!is_array($array)) {
      throw new Exception('Expected Array/Object');
    }
    foreach ($keys as $key) {
      if (!array_key_exists($key, $array)) {
        throw new Exception('Missing Expected Key ' . $key);
      }
    }
  }
}