<?php

/*
 * YinYangBoard: all utility functions concerning space on the board are here
 */
class YinYangBoard extends APP_GameClass
{
  public $game;
  public $flipped = false;
  public function __construct($game)
  {
    $this->game = $game;
  }

  public function getUiData($playerId)
  {
    $this->flipped = $this->game->playerManager->getPlayer($playerId)->isFlipped();
    return $this->getBoard();
  }

  public function setupNewGame()
  {
    $sql = "INSERT INTO board (piece, x, y) VALUES";
    $values = [];
    for($x = 0; $x < 4; $x++){
    for($y = 0; $y < 4; $y++){
      $threshold = $x < 2? 1 : 3;
      $values[] = "(" . ($y < $threshold? BLACK : WHITE). ", $x, $y)";
    }}
    self::DbQuery($sql . implode($values, ','));
  }


  public static function getCoords($piece)
  {
    return ['x' => (int) $piece['x'], 'y' => (int) $piece['y']];
  }


  public static function flipCoords(&$piece)
  {
    $piece['x'] = 3 - (int) $piece['x'];
    $piece['y'] = 3 - (int) $piece['y'];
  }

  public static function compareCoords($a, $b)
  {
    $dx = (int) $b['x'] - (int) $a['x'];
    $dy = (int) $b['y'] - (int) $a['y'];
    if($dx != 0) return $dx;
    return $dy;
  }

  public static function compareCauses($a, $b, $flipped)
  {
    if($a['id'] == $b['id'])
      return false;

    if(!$flipped)
      return ($a['cause00'] == $b['cause00']) && ($a['cause01'] == $b['cause01']) && ($a['cause10'] == $b['cause10']) && ($a['cause11'] == $b['cause11']);
    else
      return ($a['cause00'] == $b['cause11']) && ($a['cause01'] == $b['cause10']) && ($a['cause10'] == $b['cause01']) && ($a['cause11'] == $b['cause00']);
  }

  public static function isCompatible($dom, $doms, $flipped)
  {
    foreach($doms as $dom2){
      if(YinYangBoard::compareCauses($dom, $dom2, $flipped))
        return false;
    }
    return true;
  }

  public static function hasReserve($dom, $reserve)
  {
    if($dom['type'] == "creation"){
      $black_reserve_change = 0;
      $white_reserve_change = 0;

      for($x = 0; $x < 2; $x++){
      for($y = 0; $y < 2; $y++){
        if(!$dom['cause'.$x.$y]){
          $piece = $dom['effect'.$x.$y];
          if($piece == BLACK) $black_reserve_change -= 1;
          elseif($piece == WHITE) $white_reserve_change -= 1;
        }
      }}

      return $reserve['black'] + $black_reserve_change >= 0
          && $reserve['white'] + $white_reserve_change >= 0;
    }
    return true;
  }

  /*
   * getPlacedPieces: return all pieces on the board
   */
  public function getPlacedPieces()
  {
    return self::getObjectListFromDb("SELECT * FROM board WHERE piece != 0");
  }


  public function getFreeSpaces($intersect = null)
  {
    $spaces = array_map('YinYangBoard::getCoords', self::getObjectListFromDb("SELECT x, y FROM board WHERE piece = 0"));
    if($this->flipped)
      array_walk($spaces, 'YinYangBoard::flipCoords');
    if(!is_null($intersect) && is_array($intersect))
      $spaces = array_values(array_uintersect($spaces, $intersect, array('YinYangBoard','compareCoords')));
    return $spaces;
  }



  /*
   * getBoard:
   *   return a 3d matrix reprensenting the board with all the placed pieces
   */
  public function getBoard($flipped = null)
  {
    // Create an empty 4*4 board
    $board = [];
    for ($x = 0; $x < 4; $x++) {
      $board[$x] = [];
      for ($y = 0; $y < 4; $y++) {
        $board[$x][$y] = 0;
      }
    }

    // Add all placed pieces
    $flip = is_null($flipped)? $this->flipped : $flipped;
    $pieces = $this->getPlacedPieces();
    for ($i = 0; $i < count($pieces); $i++) {
      $p = $pieces[$i];
      $x = $flip? (3 -$p['x']) : $p['x'];
      $y = $flip? (3 -$p['y']) : $p['y'];
      $board[$x][$y] = $p['piece'];
    }

    return $board;
  }

  public function getNeighbours($s)
  {
    $spaces = [
      ['x' => $s['x'] - 1, 'y' => $s['y']],
      ['x' => $s['x'] + 1, 'y' => $s['y']],
      ['x' => $s['x'], 'y' => $s['y'] - 1],
      ['x' => $s['x'], 'y' => $s['y'] + 1],
    ];

    Utils::filter($spaces, function($space){
      return $space['x'] >= 0 && $space['x'] < 4 && $space['y'] >= 0 && $space['y'] < 4;
    });
    return $spaces;
  }

  /*
   * getMovablePieces:
   */
  public function getMovablePieces($color)
  {
    $pieces = self::getObjectListFromDb("SELECT * FROM board WHERE piece = {$color}");
    $movables = [];
    foreach($pieces as &$piece){
      if($this->flipped){
        $piece['x'] = 3 - $piece['x'];
        $piece['y'] = 3 - $piece['y'];
      }

      $freeNeighbours = $this->getFreeSpaces($this->getNeighbours($piece));
      if(count($freeNeighbours) > 0){
        $piece['moves'] = $freeNeighbours;
        array_push($movables, $piece);
      }
    }

    return $movables;
  }


  /*
   * getAvailableLocations:
   */
  public function getAvailableLocations($domino)
  {
    $board = $this->getBoard();
    $locations = [];
    for ($x = 0; $x < 3; $x++) {
      for ($y = 0; $y < 3; $y++) {
        if($board[$x][$y] == $domino['cause00'] && $board[$x][$y+1] == $domino['cause01'] && $board[$x+1][$y] == $domino['cause10'] && $board[$x+1][$y+1] == $domino['cause11'])
          array_push($locations, ['x' => $x, 'y' => $y]);
      }
    }

    return $locations;
  }


  public function applyLaw($domino, $pos)
  {
    if($domino['type'] != "adaptation"){
      $black_reserve_change = 0;
      $white_reserve_change = 0;

      for($i = 0; $i < 2; $i++){
      for($j = 0; $j < 2; $j++){
        $x = $i + (int) $pos['x'];
        $y = $j + (int) $pos['y'];
        if($this->flipped){
          $x = 3 - $x;
          $y = 3 - $y;
        }
        $val = $domino['effect'.$i.$j];

        if(!$domino['cause'.$i.$j]){
          if($val == BLACK) $black_reserve_change -= 1;
          elseif($val == WHITE) $white_reserve_change -= 1;
        }

        self::DbQuery("UPDATE board SET piece = {$val} WHERE x = {$x} AND y = {$y}");
      }}

      if($black_reserve_change)
        $this->game->incGameStateValue('blackReserve', $black_reserve_change);
      if($white_reserve_change)
        $this->game->incGameStateValue('whiteReserve', $white_reserve_change);
    }

    self::DbQuery("UPDATE domino SET location = 'board' WHERE id = {$domino['id']}");

    $this->game->log->addApplyLaw($domino, $pos);
    foreach($this->game->playerManager->getPlayers() as $player){
      $this->game->notifyPlayer($player->getId(), 'lawApplied', clienttranslate('${player_name} applied a law'), [
        'player_name' => $this->game->getActivePlayerName(),
        'pId' => $this->game->getActivePlayerId(),
        'board' => $this->getBoard($player->isFlipped()),
        'domino' => $domino,
        'pos' => $pos,
        'reserve' => $this->game->getReserve(),
      ]);
    }
  }

  public function adaptDomino($dominoId, $type, $cause, $effect)
  {
		self::DbQuery("UPDATE domino SET location = 'hand', type = '{$type}', cause00 = {$cause[0]}, cause01 = {$cause[1]}, cause10 = {$cause[2]}, cause11 = {$cause[3]}, effect00 = {$effect[0]}, effect01 = {$effect[1]}, effect10 = {$effect[2]}, effect11 = {$effect[3]} WHERE id = {$dominoId}");
		$this->game->notifyAllPlayers('dominoAdapted', clienttranslate('${player_name} adapted a law'), [
			'player_name' => $this->game->getActivePlayerName(),
			'dominoId' => $dominoId,
		]);

		$domino = self::getObjectFromDB("SELECT * FROM domino WHERE id = {$dominoId}");
    $this->game->notifyPlayer($domino['player_id'], 'newDomino', '', [
      'domino' => $domino,
    ]);
    $this->game->log->addAdaptation($domino);
  }


  public function movePiece($piece, $pos)
  {
    if($this->flipped){
      self::flipCoords($pos);
    }
    self::DbQuery("UPDATE board SET piece = {$piece['piece']} WHERE x = {$pos['x']} AND y = {$pos['y']}");
    self::DbQuery("UPDATE board SET piece = 0 WHERE x = {$piece['x']} AND y = {$piece['y']}");

    $this->game->log->addMovePiece($piece, $pos);
    foreach($this->game->playerManager->getPlayers() as $player){
      $this->game->notifyPlayer($player->getId(), 'pieceMoved', clienttranslate('${player_name} moved a piece'), [
        'player_name' => $this->game->getActivePlayerName(),
        'pId' => $this->game->getActivePlayerId(),
        'board' => $this->getBoard($player->isFlipped()),
        'piece' => $piece,
        'pos' => $pos,
      ]);
    }
  }

  public function suggestDraw()
  {
    $this->game->log->addSuggestDraw();
    foreach($this->game->playerManager->getPlayers() as $player){
      $this->game->notifyPlayer($player->getId(), 'drawSuggested', clienttranslate('${player_name} proposed a draw'), [
        'player_name' => $this->game->getActivePlayerName(),
      ]);
    }
  }

  public function acceptDraw()
  {
    $this->game->log->addAcceptDraw();
    foreach($this->game->playerManager->getPlayers() as $player){
      $this->game->notifyPlayer($player->getId(), 'drawAccepted', clienttranslate('${player_name} agreed to a draw'), [
        'player_name' => $this->game->getActivePlayerName(),
      ]);
    }
  }

  public function declineDraw()
  {
    $this->game->log->addDeclineDraw();
    foreach($this->game->playerManager->getPlayers() as $player){
      $this->game->notifyPlayer($player->getId(), 'drawDeclined', clienttranslate('${player_name} declined a draw'), [
        'player_name' => $this->game->getActivePlayerName(),
      ]);
    }
  }
}
