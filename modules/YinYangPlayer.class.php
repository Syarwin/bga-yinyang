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
//    $sqlDominos = 'INSERT INTO domino (player_id, location) VALUES ';
    $sqlDominos = 'INSERT INTO `domino`(`player_id`, `type`, `location`, `cause00`, `cause01`, `cause10`, `cause11`, `effect00`, `effect01`, `effect10`, `effect11`) VALUES';
    $values = [];

    if($this->no == 2){
      $values[] = '("'.$this->id.'","destruction","hand","2","1","2","2","2","0","2","2")';
      $values[] = '("'.$this->id.'","destruction","hand","1","1","1","1","1","1","0","1")';
      $values[] = '("'.$this->id.'","adaptation","hand","2","1","2","1","0","0","0","0")';
      $values[] = '("'.$this->id.'","creation","hand","2","0","2","0","2","1","2","1")';
    } else {
      $values[] = '("'.$this->id.'","destruction","hand","1","1","1","1","1","0","1","1")';
      $values[] = '("'.$this->id.'","destruction","hand","1","2","1","2","1","2","1","0")';
      $values[] = '("'.$this->id.'","creation","hand","0","0","1","1","1","1","1","1")';
      $values[] = '("'.$this->id.'","adaptation","hand","2","2","1","2","0","0","0","0")';
    }
/*
    for($i = 0; $i < 4; $i++){
      $values[] = "('" . $this->id . "','hand')";
    }
*/
    self::DbQuery($sqlDominos . implode($values, ','));


    $msg = $this->getNo() == 1? clienttranslate('${player_name} will play the black pieces') : clienttranslate('${player_name} will play the white pieces');
    $this->game->notifyAllPlayers('message', $msg, [
      'player_name' => $this->getName(),
    ]);
  }

  public function isFlipped(){ return $this->no == 2; }
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
      'no'      => $this->no,
    ];
  }

  public function getDominos()
  {
    return self::getObjectListFromDb("SELECT * FROM domino WHERE player_id = {$this->id}");
  }

  public function getVisibleDominos($mine = true)
  {
    $op = $mine? "=" : "!=";
    return self::getObjectListFromDb("SELECT * FROM domino WHERE player_id {$op} {$this->id} AND location = 'board'");
  }

  public function getDominosInHand()
  {
    return self::getObjectListFromDb("SELECT * FROM domino WHERE player_id = {$this->id} AND location = 'hand'");
  }

  public function getDominosIdsInHand()
  {
    return array_map(function($domino){ return $domino['id']; }, $this->getDominosInHand());
  }

  public function getPlayableLaws()
  {
    $this->game->board->flipped = $this->isFlipped();

    $args = [
      'dominos' => $this->getDominos(),
    ];

    $myDominos = $this->getVisibleDominos(true);
    $oppDominos = $this->getVisibleDominos(false);
    $reserve = $this->game->getReserve();
    foreach($args['dominos'] as &$domino){
      $domino['locations'] = $this->game->board->getAvailableLocations($domino);
      $domino['compatible'] = YinYangBoard::isCompatible($domino, $myDominos, false) && YinYangBoard::isCompatible($domino, $oppDominos, true) && YinYangBoard::hasReserve($domino, $reserve);
    }
    Utils::cleanDominos($args);

    return $args;
  }

  public function getMovablePieces()
  {
    $this->game->board->flipped = $this->isFlipped();

    return [
      'cancelable' => $this->game->log->getLastActions() != null,
      'pieces' => $this->game->board->getMovablePieces($this->no),
      'skippable' => !is_null($this->game->log->getLastLaw())
    ];
  }
}
