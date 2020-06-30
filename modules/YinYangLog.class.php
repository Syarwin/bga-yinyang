<?php

/*
 * YinYangLog: a class that allows to log some actions
 *   and then fetch these actions latter
 */
class YinYangLog extends APP_GameClass
{
  public $game;
  public function __construct($game)
  {
    $this->game = $game;
  }


////////////////////////////////
////////////////////////////////
//////////   Stats   ///////////
////////////////////////////////
////////////////////////////////

  /*
   * initStats: initialize statistics to 0 at start of game
   */
  public function initStats($players)
  {
    /*
    $this->game->initStat('table', 'move', 0);
    $this->game->initStat('table', 'buildBlock', 0);
    $this->game->initStat('table', 'buildDome', 0);
    $this->game->initStat('table', 'buildTower', 0);

    foreach ($players as $pId => $player) {
      $this->game->initStat('player', 'playerPower', 0, $pId);
      $this->game->initStat('player', 'usePower', 0, $pId);
      $this->game->initStat('player', 'move', 0, $pId);
      $this->game->initStat('player', 'moveUp', 0, $pId);
      $this->game->initStat('player', 'moveDown', 0, $pId);
      $this->game->initStat('player', 'buildBlock', 0, $pId);
      $this->game->initStat('player', 'buildDome', 0, $pId);
    }
    */
  }

  /*
   * gameEndStats: compute end-of-game statistics
   */
  public function gameEndStats()
  {
//    $this->game->setStat($this->game->board->getCompleteTowerCount(), 'buildTower');
  }

  public function incrementStats($stats, $value = 1)
  {
    foreach ($stats as $pId => $names) {
      foreach ($names as $name) {
        if ($pId == 'table') {
          $pId = null;
        }
        $this->game->incStat($value, $name, $pId);
      }
    }
  }


////////////////////////////////
////////////////////////////////
//////////   Adders   //////////
////////////////////////////////
////////////////////////////////

  /*
   * insert: add a new log entry
   * params:
   *   - $playerId: the player who is making the action
   *   - $dominoId : the piece whose is making the action
   *   - string $action : the name of the action
   *   - array $args : action arguments (eg space)
   */
  public function insert($playerId, $dominoId, $action, $args = [])
  {
    $playerId = $playerId == -1 ? $this->game->getActivePlayerId() : $playerId;
    $moveId = self::getUniqueValueFromDB("SELECT `global_value` FROM `global` WHERE `global_id` = 3");
    $round = $this->game->getGameStateValue("currentRound");

/*
    if ($action == 'move') {
      $args['stats'] = [
        'table' => ['move'],
        $playerId => ['move'],
      ];
      if ($args['to']['z'] > $args['from']['z']) {
        $args['stats'][$playerId][] = 'moveUp';
      } else if ($args['to']['z'] < $args['from']['z']) {
        $args['stats'][$playerId][] = 'moveDown';
      }
    } else if ($action == 'build') {
      $statName = $args['to']['arg'] == 3 ? 'buildDome' : 'buildBlock';
      $args['stats'] = [
        'table' => [$statName],
        $playerId => [$statName],
      ];
    }
*/
    if (array_key_exists('stats', $args)) {
      $this->incrementStats($args['stats']);
    }

    $actionArgs = json_encode($args);

    self::DbQuery("INSERT INTO log (`round`, `move_id`, `player_id`, `domino_id`, `action`, `action_arg`) VALUES ('$round', '$moveId', '$playerId', '$dominoId', '$action', '$actionArgs')");
  }


  /*
   * starTurn: logged whenever a player start its turn, very useful to fetch last actions
   */
  public function startTurn()
  {
    $this->insert(-1, 0, 'startTurn');
  }


  public function addApplyLaw($piece, $to)
  {
    $args = [
      'to'   => $to,
    ];
    $this->insert(-1, $piece['id'], 'applyLaw', $args);
  }



/////////////////////////////////
/////////////////////////////////
//////////   Getters   //////////
/////////////////////////////////
/////////////////////////////////

  /*
   * getLastActions : get works and actions of player (used to cancel previous action)
   */
  public function getLastActions($actions = ['build', 'usedPower', 'useTile'], $pId = null, $offset = null)
  {
    $pId = $pId ?? $this->game->getActivePlayerId();
    $offset = $offset ?? 0;
    $actionsNames = "'" . implode("','", $actions) . "'";

    return self::getObjectListFromDb("SELECT * FROM log WHERE `action` IN ($actionsNames) AND `player_id` = '$pId' AND `round` = (SELECT round FROM log WHERE `player_id` = $pId AND `action` = 'startTurn' ORDER BY log_id DESC LIMIT 1) - $offset ORDER BY log_id DESC");
  }


  public function getLastAction($action, $pId = null, $offset = null)
  {
    $actions = $this->getLastActions([$action], $pId, $offset);
    return count($actions) > 0 ? json_decode($actions[0]['action_arg'], true) : null;
  }


  public function getLastMove()
  {
    return $this->getLastAction('move');
  }

////////////////////////////////
////////////////////////////////
//////////   Cancel   //////////
////////////////////////////////
////////////////////////////////
  /*
   * cancelTurn: cancel the last actions of active player of current turn
   */
  public function cancelTurn()
  {
    $pId = $this->game->getActivePlayerId();
    $logs = self::getObjectListFromDb("SELECT * FROM log WHERE `player_id` = '$pId' AND `round` = (SELECT round FROM log WHERE `player_id` = $pId AND `action` = 'startTurn' ORDER BY log_id DESC LIMIT 1) ORDER BY log_id DESC");

    $ids = [];
    $moveIds = [];
    foreach ($logs as $log) {
      $args = json_decode($log['action_arg'], true);

/*
      switch($log['action']){
        // Build : remove piece from board
        case 'build':
        case 'tileBuild':
          self::DbQuery("UPDATE piece SET x = NULL, y = NULL, location = 'hand' WHERE id = {$log['piece_id']}");
          break;

        // ObtainTile : put tile back on board
        case 'obtainTile':
          self::DbQuery("UPDATE piece SET location = 'board', player_id = NULL WHERE id = {$log['piece_id']}");
          break;

        // UseTile : put tile back in hand
        case 'useTile':
          self::DbQuery("UPDATE piece SET location = 'hand' WHERE id = {$log['piece_id']}");
          break;

        // move : move back
        case 'move':
          self::DbQuery("UPDATE piece SET x = {$args['from']['x']}, y = {$args['from']['y']} WHERE id = {$log['piece_id']}");
          break;
      }
*/

      // Undo statistics
      if (array_key_exists('stats', $args)) {
        $this->incrementStats($args['stats'], -1);
      }

      $ids[] = intval($log['log_id']);
      if ($log['action'] != 'startTurn') {
        $moveIds[] = array_key_exists('move_id', $log)? intval($log['move_id']) : 0; // TODO remove the array_key_exists
      }
    }

    // Remove the logs
    self::DbQuery("DELETE FROM log WHERE `player_id` = '$pId' AND `log_id` IN (" . implode(',', $ids) . ")");

    // Cancel the game notifications
    self::DbQuery("UPDATE gamelog SET `cancel` = 1 WHERE `gamelog_move_id` IN (" . implode(',', $moveIds) . ")");
    return $moveIds;
  }

  /*
   * getCancelMoveIds : get all cancelled move IDs from BGA gamelog, used for styling the notifications on page reload
   */
  public function getCancelMoveIds()
  {
    $moveIds = self::getObjectListFromDb("SELECT `gamelog_move_id` FROM gamelog WHERE `cancel` = 1 ORDER BY 1", true);
    return array_map('intval', $moveIds);
  }
}
