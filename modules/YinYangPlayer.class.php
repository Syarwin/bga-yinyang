<?php

/*
 * YinYangPlayer: all utility functions concerning a player
 */
class YinYangPlayer extends APP_GameClass
{
  private $game;
  private $id;
  private $no; // natural order
  private $name;
  private $color;
  private $eliminated = false;
  private $zombie = false;

  public function __construct($game, $row)
  {
    $this->game = $game;
    $this->id = (int) $row['id'];
    $this->no = (int) $row['no'];
    $this->name = $row['name'];
    $this->color = $row['color'];
    $this->eliminated = $row['eliminated'] == 1;
    $this->zombie = $row['zombie'] == 1;
  }


  public function setupNewGame()
  {
    $sqlDominos = 'INSERT INTO domino (player_id, location) VALUES ';
    $values = [];
    for($i = 0; $i < 4; $i++){
      $values[] = "('" . $this->id . "','hand')";
    }
    self::DbQuery($sqlDominos . implode($values, ','));
  }


  public function getId(){ return $this->id; }
  public function getNo(){ return $this->no; }
  public function getName(){ return $this->name; }
  public function getColor(){ return $this->color; }
  public function isEliminated(){ return $this->eliminated; }
  public function isZombie(){ return $this->zombie; }

  public function getUiData($currentPlayerId = null)
  {
    return [
      'id'      => $this->id,
      'no'      => $this->no,
      'name'    => $this->name,
      'color'   => $this->color,
    ];
  }

  public function getDominosInHand()
  {
    return self::getObjectListFromDb("SELECT * FROM domino WHERE player_id = {$this->id} AND location = 'hand'");
  }

  public function getDominosIdsInHand()
  {
    return array_map(function($domino){ return $domino['id']; }, $this->getDominosInHand());
  }
}
