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

namespace Models;

use \Helpers\DB,
    \Exception,
    \PDO;

class Series {
  const TYPE_RANKED = 'ranked';
  const TYPE_CHALLENGE = 'challenge';
  const OUTCOME_PENDING = 'pending';
  const OUTCOME_COMPLETE = 'complete';

  public $id = null;

  public function __construct($series_id) {
    $this->id = $series_id;
  }

  public function gameCompleted() {
    // Check if series is completed
    $stmt = DB::prepare("SELECT count(*) FROM game WHERE series_id = ? AND outcome = 'pending'");
    $stmt->execute(array($this->id));
    $games_left = $stmt->fetchColumn(0);

    // If there are games left, get out
    if ($games_left > 0) {
      return;
    }

    // Load Series
    $stmt = DB::prepare("SELECT bot1_id, bot2_id, type FROM series WHERE series_id = ?");
    $stmt->execute(array($this->id));
    $series = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get current bot ranks
    $stmt = DB::prepare("SELECT rank FROM bot WHERE bot_id = ?");
    $stmt->execute(array($series['bot1_id']));
    $bot1_rank = $stmt->fetchColumn(0);

    $stmt->execute(array($series['bot2_id']));
    $bot2_rank = $stmt->fetchColumn(0);

    // If ranked, ensure Rank1 < Rank2, but no more than 3 diff
    if ($series['type'] === self::TYPE_RANKED &&
       ($bot1_rank < $bot2_rank || $bot1_rank - $bot2_rank > 3)) {
      $series['type'] = self::TYPE_CHALLENGE;
    }

    // Determine Winner
    $stmt = DB::prepare("SELECT winner_id as id, count(*) as games FROM game WHERE series_id = ? GROUP BY winner_id ORDER BY games LIMIT 1");
    $stmt->execute(array($this->id));
    $winner = $stmt->fetchColumn(0);

    // Update Series
    $stmt = DB::prepare("UPDATE series SET bot1_rank = :bot1, bot2_rank = :bot2,
      winner_id = :winner, outcome = :outcome, type = :type,
      completed_time = NOW() WHERE series_id = :series");
    $stmt->execute(array(
          'bot1' => $bot1_rank,
          'bot2' => $bot2_rank,
        'winner' => $winner,
       'outcome' => 'complete',
          'type' => $series['type'],
        'series' => $this->id
    ));

    // Update Bot Ranks
    if ($series['type'] === self::TYPE_RANKED &&
       (($bot1_rank > $bot2_rank && $winner === $series['bot1_id']) ||
        ($bot1_rank < $bot2_rank && $winner === $series['bot2_id']))) {
      $stmt = DB::prepare("UPDATE bot SET rank = :rank WHERE bot_id = :bot");
      // Swap ranks
      $stmt->execute(array(
          ':rank' => $bot2_rank,
           ':bot' => $series['bot1_id']
      ));
      $stmt->execute(array(
          ':rank' => $bot1_rank,
           ':bot' => $series['bot2_id']
      ));
    }
  }
}