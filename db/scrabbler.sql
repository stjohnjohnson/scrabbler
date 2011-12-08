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

-- Sample Data
INSERT INTO bot (username,rank,status,language,name) VALUES
  ('stjohn',1,'active','php','SJ-Training'),
  ('stjohn',2,'active','php','SJ-MostWords'),
  ('stjohn',3,'active','php','SJ-MostLetters'),
  ('stjohn',4,'active','php','SJ-LeastLetters'),
  ('stjohn',5,'active','php','SJ-LongestWord'),
  ('stjohn',6,'active','php','SJ-ShortestWord'),
  ('stjohn',7,'active','php','SJ-HighestScore'),
  ('stjohn',8,'active','php','SJ-LowestScore')
;

-- +---------------------------------------------------------------------------+
-- | TABLE scrabbler.series
-- +---------------------------------------------------------------------------+
DROP TABLE IF EXISTS series;
CREATE TABLE series (
  series_id       INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  bot1_id         INT(10) UNSIGNED NOT NULL DEFAULT 0,
  bot2_id         INT(10) UNSIGNED NOT NULL DEFAULT 0,
  bot1_rank       INT(10) UNSIGNED NOT NULL DEFAULT 0,
  bot2_rank       INT(10) UNSIGNED NOT NULL DEFAULT 0,
  winner_id       INT(10) UNSIGNED NOT NULL DEFAULT 0,
  outcome         ENUM('pending', 'complete') NOT NULL DEFAULT 'pending',
  type            ENUM('challenge','training','ranked') NOT NULL DEFAULT 'challenge',
  completed_time  TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
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

-- Sample Data
INSERT INTO series (bot1_id,bot2_id,type,series_id) VALUES
  (8,5,'ranked',1),
  (8,6,'ranked',2),
  (8,7,'ranked',3),
  (7,4,'ranked',4),
  (7,5,'ranked',5),
  (7,6,'ranked',6),
  (6,3,'ranked',7),
  (6,4,'ranked',8),
  (6,5,'ranked',9),
  (5,2,'ranked',10),
  (5,3,'ranked',11),
  (5,4,'ranked',12),
  (4,1,'ranked',13),
  (4,2,'ranked',14),
  (4,3,'ranked',15),
  (3,1,'ranked',16),
  (3,2,'ranked',17),
  (2,1,'ranked',18)
;

-- +---------------------------------------------------------------------------+
-- | TABLE scrabbler.game
-- +---------------------------------------------------------------------------+
DROP TABLE IF EXISTS game;
CREATE TABLE game (
  game_id         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  series_id       INT(10) UNSIGNED NOT NULL DEFAULT 0,
  bot1_id         INT(10) UNSIGNED NOT NULL DEFAULT 0,
  bot2_id         INT(10) UNSIGNED NOT NULL DEFAULT 0,
  score1          INT(10) NOT NULL DEFAULT 0,
  score2          INT(10) NOT NULL DEFAULT 0,
  winner_id       INT(10) UNSIGNED NOT NULL DEFAULT 0,
  outcome         ENUM('pending', 'complete', 'draw', 'fail') NOT NULL DEFAULT 'pending',
  disqualify      ENUM('none', 'overtime', 'invalid move', 'crash') NOT NULL DEFAULT 'none',
  disqualify_id   INT(10) UNSIGNED NOT NULL DEFAULT 0,
  m_time          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  c_time          TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  accepted_time   TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  completed_time  TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
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

-- Sample Data
INSERT INTO game (bot1_id,bot2_id,series_id) VALUES
  (8,5,1),(8,5,1),(8,5,1),(8,5,1),(8,5,1),(5,8,1),(5,8,1),(5,8,1),(5,8,1),
  (8,6,2),(8,6,2),(8,6,2),(8,6,2),(8,6,2),(6,8,2),(6,8,2),(6,8,2),(6,8,2),
  (8,7,3),(8,7,3),(8,7,3),(8,7,3),(8,7,3),(7,8,3),(7,8,3),(7,8,3),(7,8,3),
  (4,7,4),(4,7,4),(4,7,4),(4,7,4),(4,7,4),(7,4,4),(7,4,4),(7,4,4),(7,4,4),
  (7,5,5),(7,5,5),(7,5,5),(7,5,5),(7,5,5),(5,7,5),(5,7,5),(5,7,5),(5,7,5),
  (7,6,6),(7,6,6),(7,6,6),(7,6,6),(7,6,6),(6,7,6),(6,7,6),(6,7,6),(6,7,6),
  (6,3,7),(6,3,7),(6,3,7),(6,3,7),(6,3,7),(3,6,7),(3,6,7),(3,6,7),(3,6,7),
  (6,4,8),(6,4,8),(6,4,8),(6,4,8),(6,4,8),(4,6,8),(4,6,8),(4,6,8),(4,6,8),
  (6,5,9),(6,5,9),(6,5,9),(6,5,9),(6,5,9),(5,6,9),(5,6,9),(5,6,9),(5,6,9),
  (5,2,10),(5,2,10),(5,2,10),(5,2,10),(5,2,10),(2,5,10),(2,5,10),(2,5,10),(2,5,10),
  (5,3,11),(5,3,11),(5,3,11),(5,3,11),(5,3,11),(3,5,11),(3,5,11),(3,5,11),(3,5,11),
  (5,4,12),(5,4,12),(5,4,12),(5,4,12),(5,4,12),(4,5,12),(4,5,12),(4,5,12),(4,5,12),
  (4,1,13),(4,1,13),(4,1,13),(4,1,13),(4,1,13),(1,4,13),(1,4,13),(1,4,13),(1,4,13),
  (4,2,14),(4,2,14),(4,2,14),(4,2,14),(4,2,14),(2,4,14),(2,4,14),(2,4,14),(2,4,14),
  (4,3,15),(4,3,15),(4,3,15),(4,3,15),(4,3,15),(3,4,15),(3,4,15),(3,4,15),(3,4,15),
  (3,1,16),(3,1,16),(3,1,16),(3,1,16),(3,1,16),(1,3,16),(1,3,16),(1,3,16),(1,3,16),
  (3,2,17),(3,2,17),(3,2,17),(3,2,17),(3,2,17),(2,3,17),(2,3,17),(2,3,17),(2,3,17),
  (2,1,18),(2,1,18),(2,1,18),(2,1,18),(2,1,18),(1,2,18),(1,2,18),(1,2,18),(1,2,18)
;

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
  time            INT(10) UNSIGNED NOT NULL DEFAULT 0,
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
  move_id         INT(10) UNSIGNED NOT NULL DEFAULT 0,
  bot_id          INT(10) UNSIGNED NOT NULL DEFAULT 0,
  word            VARCHAR(15) NOT NULL DEFAULT '',
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