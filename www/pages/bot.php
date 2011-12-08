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
  private function _getBot($params) {
    list($id, ) = $params;
    if (!ctype_digit($id)) {
      throw new Exception('Bot Not Found', 404);
    }

    return new \Models\Bot($id);
  }

  public function index() {
    return 'index';
  }

  public function view($params) {
    $bot = $this->_getBot($params)->load();

    $this->title = "Bot &raquo; #{$obj->bot_id} {$obj->name} ({$obj->status})";
    return print_r($bot, true);
  }

  public function source($params) {
    $id = $this->_getId($params);

    // Load file
    $filename = $id . '.tar.gz';
    if (!is_file('bots/' . $filename)) {
      throw new Exception('Cannot find source for Bot #' . $id, 404);
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