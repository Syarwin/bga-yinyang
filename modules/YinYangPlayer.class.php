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

    // no == 1 => black
    // no == 2 => white

    // inside domino : 
    // cause00 = top left
    // cause01 = top right
    // cause10 = bottom left
    // cause11 = bottom right

    // value 0 = empty
    // value 1 = black
    // value 2 = white

    $me = intval($this->no);
    $ot = 3 - $me;
    $ze = 0;

    $cat11 = '("'.$this->id.'","adaptation","hand","'.$me.'","'.$ot.'","'.$me.'","'.$ot.'","'.$ze.'","'.$ze.'","'.$ze.'","'.$ze.'")';
    $cat12 = '("'.$this->id.'","adaptation","hand","'.$me.'","'.$me.'","'.$me.'","'.$me.'","'.$ze.'","'.$ze.'","'.$ze.'","'.$ze.'")';
    $cat13 = '("'.$this->id.'","adaptation","hand","'.$me.'","'.$ze.'","'.$me.'","'.$me.'","'.$ze.'","'.$ze.'","'.$ze.'","'.$ze.'")';
    $cat1 = array($cat11, $cat12, $cat13);
    shuffle($cat1);
    $cat21 = '("'.$this->id.'","destruction","hand","'.$me.'","'.$ot.'","'.$me.'","'.$me.'","'.$me.'","'.$ze.'","'.$me.'","'.$me.'")';
    $cat22 = '("'.$this->id.'","destruction","hand","'.$ot.'","'.$ot.'","'.$me.'","'.$ot.'","'.$ze.'","'.$ot.'","'.$me.'","'.$ot.'")';
    $cat23 = '("'.$this->id.'","destruction","hand","'.$ot.'","'.$ot.'","'.$ot.'","'.$ot.'","'.$ot.'","'.$ze.'","'.$ot.'","'.$ot.'")';
    $cat2 = array($cat21, $cat22, $cat23);
    shuffle($cat2);
    $cat31 = '("'.$this->id.'","creation","hand","'.$ze.'","'.$ot.'","'.$me.'","'.$ze.'","'.$me.'","'.$ot.'","'.$me.'","'.$me.'")';
    $cat32 = '("'.$this->id.'","creation","hand","'.$me.'","'.$ze.'","'.$me.'","'.$ze.'","'.$me.'","'.$me.'","'.$me.'","'.$me.'")';
    $cat33 = '("'.$this->id.'","creation","hand","'.$ze.'","'.$ze.'","'.$me.'","'.$me.'","'.$me.'","'.$me.'","'.$me.'","'.$me.'")';
    $cat3 = array($cat31, $cat32, $cat33);
    shuffle($cat3);
    $cat41 = '("'.$this->id.'","adaptation","hand","'.$me.'","'.$ot.'","'.$me.'","'.$ze.'","'.$ze.'","'.$ze.'","'.$ze.'","'.$ze.'")';
    $cat42 = '("'.$this->id.'","destruction","hand","'.$me.'","'.$ot.'","'.$ze.'","'.$ot.'","'.$me.'","'.$ze.'","'.$ze.'","'.$ot.'")';
    $cat43 = '("'.$this->id.'","creation","hand","'.$ze.'","'.$ot.'","'.$ot.'","'.$ze.'","'.$me.'","'.$ot.'","'.$ot.'","'.$me.'")';
    $cat4 = array($cat41, $cat42, $cat43);
    shuffle($cat4);

    $values[] = $cat4[0];
    $values[] = $cat3[0];
    $values[] = $cat2[0];
    $values[] = $cat1[0];
    
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
