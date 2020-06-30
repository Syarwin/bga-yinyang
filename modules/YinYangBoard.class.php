<?php

/*
 * YinYangBoard: all utility functions concerning space on the board are here
 */
class YinYangBoard extends APP_GameClass
{
  public $game;
  public function __construct($game)
  {
    $this->game = $game;
  }

  public function getUiData()
  {
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



  /*
   * getPlacedPieces: return all pieces on the board
   */
  public function getPlacedPieces()
  {
    return self::getObjectListFromDb("SELECT * FROM board WHERE piece != 0");
  }



  /*
   * getBoard:
   *   return a 3d matrix reprensenting the board with all the placed pieces
   */
  public function getBoard()
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
    $pieces = $this->getPlacedPieces();
    for ($i = 0; $i < count($pieces); $i++) {
      $p = $pieces[$i];
      $board[$p['x']][$p['y']] = $p['piece'];
    }

    return $board;
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
    for($i = 0; $i < 2; $i++){
    for($j = 0; $j < 2; $j++){
      $x = $i + (int) $pos['x'];
      $y = $j + (int) $pos['y'];
      $val = $domino['effect'.$i.$j];
      self::DbQuery("UPDATE board SET piece = {$val} WHERE x = {$x} AND y = {$y}");
    }}

    $this->game->log->addApplyLaw($domino, $pos);
    $this->game->notifyAllPlayers('lawApplied', clienttranslate('${player_name} applied a law'), [
      'player_name' => $this->game->getActivePlayerName(),
      'board' => $this->getBoard(),
    ]);
  }
}
