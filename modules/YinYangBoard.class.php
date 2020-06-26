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
}
