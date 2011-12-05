DROP DATABASE IF EXISTS scrabbler;
CREATE database scrabbler;
USE scrabbler;

-- Fix internal time_zone
SET GLOBAL time_zone = 'UTC';


-- +---------------------------------------------------------------------------+
-- | TABLE scrabbler.bot
-- +---------------------------------------------------------------------------+
DROP TABLE IF EXISTS bot;
CREATE TABLE bot (
  bot_id          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  username        VARCHAR(100) NOT NULL DEFAULT '',
  name            VARCHAR(100) NOT NULL DEFAULT '',
  rank            INT(10) UNSIGNED NOT NULL DEFAULT 0,
  status          ENUM('pending','active','retired','disqualified') NOT NULL DEFAULT 'pending',
  language        ENUM('javascript','perl','php','python') NOT NULL DEFAULT 'javascript',
  m_time          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  c_time          TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (bot_id),
  UNIQUE KEY (name),
  KEY c_time (c_time),
  KEY m_time (m_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE DEFINER=`root`@`localhost` TRIGGER bot_bi_trg
  BEFORE INSERT ON bot
  FOR EACH ROW SET
    NEW.c_time = NOW();

-- +---------------------------------------------------------------------------+
-- | TABLE scrabbler.series
-- +---------------------------------------------------------------------------+
DROP TABLE IF EXISTS series;
CREATE TABLE series (
  series_id       INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  bot1_id         INT(10) UNSIGNED NOT NULL DEFAULT 0,
  bot2_id         INT(10) UNSIGNED NOT NULL DEFAULT 0,
  outcome         ENUM('pending', 'bot1', 'bot2', 'tie') NOT NULL DEFAULT 'pending',
  type            ENUM('challenge','training','ranked') NOT NULL DEFAULT 'challenge',
  m_time          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  c_time          TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (series_id),
  KEY c_time (c_time),
  KEY m_time (m_time),
  CONSTRAINT series_fk_1 FOREIGN KEY (bot1_id) REFERENCES bot (bot_id) ON DELETE CASCADE,
  CONSTRAINT series_fk_2 FOREIGN KEY (bot2_id) REFERENCES bot (bot_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE DEFINER=`root`@`localhost` TRIGGER set_bi_trg
  BEFORE INSERT ON series
  FOR EACH ROW SET
    NEW.c_time = NOW();


-- +---------------------------------------------------------------------------+
-- | TABLE scrabbler.game
-- +---------------------------------------------------------------------------+
DROP TABLE IF EXISTS game;
CREATE TABLE game (
  game_id         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  series_id       INT(10) UNSIGNED NOT NULL DEFAULT 0,
  bot1_id         INT(10) UNSIGNED NOT NULL DEFAULT 0,
  bot2_id         INT(10) UNSIGNED NOT NULL DEFAULT 0,
  score1          INT(10) UNSIGNED NOT NULL DEFAULT 0,
  score2          INT(10) UNSIGNED NOT NULL DEFAULT 0,
  outcome         ENUM('pending', 'bot1', 'bot2', 'tie') NOT NULL DEFAULT 'pending',
  disqualify      ENUM('none', 'overtime', 'invalid move', 'crash') NOT NULL DEFAULT 'none',
  m_time          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  c_time          TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  accepted_time   TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (game_id),
  KEY c_time (c_time),
  KEY m_time (m_time),
  CONSTRAINT game_fk_1 FOREIGN KEY (series_id) REFERENCES series (series_id) ON DELETE CASCADE,
  CONSTRAINT game_fk_2 FOREIGN KEY (bot1_id) REFERENCES bot (bot_id) ON DELETE CASCADE,
  CONSTRAINT game_fk_3 FOREIGN KEY (bot2_id) REFERENCES bot (bot_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE DEFINER=`root`@`localhost` TRIGGER game_bi_trg
  BEFORE INSERT ON game
  FOR EACH ROW SET
    NEW.c_time = NOW();

-- +---------------------------------------------------------------------------+
-- | TABLE scrabbler.move
-- +---------------------------------------------------------------------------+
DROP TABLE IF EXISTS move;
CREATE TABLE move (
  move_id         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  game_id         INT(10) UNSIGNED NOT NULL DEFAULT 0,
  bot_id          INT(10) UNSIGNED NOT NULL DEFAULT 0,
  sequence        INT(10) UNSIGNED NOT NULL DEFAULT 0,
  command         VARCHAR(20) NOT NULL DEFAULT '',
  rack            VARCHAR(7) NOT NULL DEFAULT '',
  points          INT(10) UNSIGNED NOT NULL DEFAULT 0,
  time            DECIMAL(5,3) UNSIGNED NOT NULL DEFAULT 0.0,
  is_trade        BOOL NOT NULL DEFAULT 0,
  m_time          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  c_time          TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (move_id),
  KEY c_time (c_time),
  KEY m_time (m_time),
  CONSTRAINT move_fk_1 FOREIGN KEY (game_id) REFERENCES game (game_id) ON DELETE CASCADE,
  CONSTRAINT move_fk_2 FOREIGN KEY (bot_id) REFERENCES bot (bot_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE DEFINER=`root`@`localhost` TRIGGER move_bi_trg
  BEFORE INSERT ON move
  FOR EACH ROW SET
    NEW.c_time = NOW();


-- +---------------------------------------------------------------------------+
-- | TABLE scrabbler.word
-- +---------------------------------------------------------------------------+
DROP TABLE IF EXISTS word;
CREATE TABLE word (
  move_id         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  bot_id          INT(10) UNSIGNED NOT NULL DEFAULT 0,
  word            INT(10) UNSIGNED NOT NULL DEFAULT 0,
  points          INT(10) UNSIGNED NOT NULL DEFAULT 0,
  coord           VARCHAR(3) NOT NULL DEFAULT '',
  m_time          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  c_time          TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  KEY c_time (c_time),
  KEY m_time (m_time),
  CONSTRAINT word_fk_1 FOREIGN KEY (move_id) REFERENCES move (move_id) ON DELETE CASCADE,
  CONSTRAINT word_fk_2 FOREIGN KEY (bot_id) REFERENCES bot (bot_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE DEFINER=`root`@`localhost` TRIGGER word_bi_trg
  BEFORE INSERT ON word
  FOR EACH ROW SET
    NEW.c_time = NOW();