<?php
/**
 * Bot Page
 *
 * Here we can view / edit / create bots
 *
 * @link https://github.com/stjohnjohnson/Scrabbler
 * @author St. John Johnson <st.john.johnson@gmail.com>
 */

namespace Pages;

use \Helpers\DB,
    \Exception,
    \PDO;

class Bot extends \Models\Page {
  private function _getId($params) {
    list($id, ) = $params;
    if (!ctype_digit($id)) {
      throw new Exception('Bot Not Found', 404);
    }

    return $id;
  }

  public function index() {
    return 'index';
  }

  public function view($params) {
    $id = $this->_getId($params);

    $stmt = DB::prepare("SELECT * FROM bot WHERE bot_id = ?");
    $stmt->execute(array($id));

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function source($params) {
    $id = $this->_getId($params);

    // Load file
    $filename = $id . '.tar.gz';
    if (!is_file('bots/' . $filename)) {
      throw new Exception('Cannot find source for Bot #' . $id);
    }

    // Download the file
    if (isset($params[2]) && $params[2] == 'raw') {
      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Length: ' . filesize('bots/' . $filename));
      header('Content-Disposition: attachment; filename="' . $filename . '"');
      readfile('bots/' . $filename);
      exit(1);
    }

    return 'source' . $id;
  }
}