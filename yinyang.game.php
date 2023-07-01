<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * YinYang implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * yinyang.game.php
 */

require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );
require_once('modules/constants.inc.php');
require_once('modules/Utils.class.php');
require_once("modules/YinYangLog.class.php");
require_once("modules/YinYangPlayerManager.class.php");
require_once("modules/YinYangBoard.class.php");


class yinyang extends Table
{
  public function __construct()
  {
    parent::__construct();
    self::initGameStateLabels([
//      'optionSetup'  => OPTION_SETUP,
      'currentRound' => CURRENT_ROUND,
      'firstPlayer'  => FIRST_PLAYER,
      'blackReserve' => BLACK_RESERVE,
      'whiteReserve' => WHITE_RESERVE,
    ]);

    // Initialize logger, board and cards
    $this->log   = new YinYangLog($this);
		$this->board = new YinYangBoard($this);
    $this->playerManager = new YinYangPlayerManager($this);
  }

  protected function getGameName()
  {
		return "yinyang";
  }


  /*
   * setupNewGame:
   *  This method is called only once, when a new game is launched.
   * params:
   *  - array $players
   *  - mixed $options
   */
  protected function setupNewGame($players, $options = [])
  {
		// Initialize board and cards
		$this->board->setupNewGame();

    // Initialize players
    $this->playerManager->setupNewGame($players);

		// Active first player to play
    $pId = $this->activeNextPlayer();
    self::setGameStateInitialValue('firstPlayer', $pId);
    self::setGameStateInitialValue('currentRound', 0);
    self::setGameStateInitialValue('blackReserve', 8);
    self::setGameStateInitialValue('whiteReserve', 10);

		$this->notifyAllPlayers('message', clienttranslate('${player_name} will play first'), [
      'player_name' => $this->getActivePlayerName(),
    ]);
  }

  public function getReserve()
  {
    $black = $this->getGameStateValue("blackReserve");
    $white = $this->getGameStateValue("whiteReserve");
    return ['black' => $black, 'white' => $white];
  }

  public function setReserve($reserve) {
    if($reserve){
      $this->setGameStateValue("blackReserve", $reserve['black']);
      $this->setGameStateValue("whiteReserve", $reserve['white']);
    }
  }

  /*
   * getAllDatas:
   *  Gather all informations about current game situation (visible by the current player).
   *  The method is called each time the game interface is displayed to a player, ie: when the game starts and when a player refreshes the game page (F5)
   */
  protected function getAllDatas($pId = null)
  {
    if (self::isSpectator()) {
      $spec = self::getUniqueValueFromDB("SELECT player_id FROM player WHERE player_no = 0");
      return [
        'fplayers' => $this->playerManager->getUiData($spec),
        'board' => $this->board->getUiData($spec),
        'player' => $this->playerManager->getPlayer($spec)->getVisibleDominos(),
        'opponent' => $this->playerManager->getPlayer($spec)->getVisibleDominos(false),
        'hand' => [],
        'cancelMoveIds' => $this->log->getCancelMoveIds(),
        'action' => $this->log->getLastLog(),
        'players' => self::getCollectionFromDB( "SELECT player_id id, player_name name, player_score score, player_no no FROM player"),
        'reserve' => $this->getReserve(),
      ];
    }
    $currentPlayerId = $pId ?? self::getCurrentPlayerId();
    return [
      'fplayers' => $this->playerManager->getUiData($currentPlayerId),
			'board' => $this->board->getUiData($currentPlayerId),
			'player' => $this->playerManager->getPlayer($currentPlayerId)->getVisibleDominos(),
			'opponent' => $this->playerManager->getPlayer($currentPlayerId)->getVisibleDominos(false),
			'hand' => $this->playerManager->getPlayer($currentPlayerId)->getDominosInHand(),
			'cancelMoveIds' => $this->log->getCancelMoveIds(),
      'action' => $this->log->getLastLog(),
      'players' => self::getCollectionFromDB( "SELECT player_id id, player_name name, player_score score, player_no no FROM player"),
      'reserve' => $this->getReserve(),
    ];
  }

  /*
   * getGameProgression:
   *  Compute and return the current game progression approximation
   *  This method is called each time we are in a game state with the "updateGameProgression" property set to true
   */
  public function getGameProgression()
  {
		return 50;
  }


	////////////////////////////////////
	/////////   Build dominos   ////////
	////////////////////////////////////
	public function stPreBuildDominos()
	{
    foreach($this->playerManager->getPlayers() as $player)
      $this->giveExtraTime($player->getId(), 500);
		$this->gamestate->setAllPlayersMultiactive();
    $this->gamestate->nextState('');
	}

	public function argBuildDominos()
	{
		return $this->playerManager->argBuildDominos();
	}

	public function updateDomino($dominoId, $type, $cause, $effect)
	{
		self::DbQuery("UPDATE domino SET location = 'hand', type = '{$type}', cause00 = {$cause[0]}, cause01 = {$cause[1]}, cause10 = {$cause[2]}, cause11 = {$cause[3]}, effect00 = {$effect[0]}, effect01 = {$effect[1]}, effect10 = {$effect[2]}, effect11 = {$effect[3]} WHERE id = {$dominoId}");
    $player = $this->playerManager->getPlayer(self::getCurrentPlayerId());
    self::notifyPlayer($player->getId(), 'updateDomino', '', [
      'dominos' => $player->getDominos(),
    ]);
	}


	public function confirmDominos($playerId)
	{
		$pendingDominos = self::getObjectListFromDB("SELECT * FROM domino WHERE player_id = '$playerId' AND type = 'empty'");
		if(count($pendingDominos) > 0)
			throw new BgaUserException(_("You still have dominos to build!"));

		$this->gamestate->setPlayerNonMultiactive($playerId, 'start');
	}

	public function stStartPlaying()
	{
    foreach($this->playerManager->getPlayers() as $player){
      self::notifyPlayer($player->getId(), 'updateDomino', '', [
        'dominos' => $player->getDominos(),
      ]);
    }


		$this->gamestate->changeActivePlayer(self::getGamestateValue("firstPlayer"));
		$this->gamestate->nextState('');
	}

  ////////////////////////////////////////////////
  ////////////   Next player / Win   ////////////
  ////////////////////////////////////////////////

  /*
   * stNextPlayer: go to next player
   */
  public function stNextPlayer()
  {
    $pId = $this->activeNextPlayer();
    self::giveExtraTime($pId);
    if (self::getGamestateValue("firstPlayer") == $pId) {
      $n = (int) self::getGamestateValue('currentRound') + 1;
      self::setGamestateValue("currentRound", $n);
    }
    $this->gamestate->nextState('start');
  }


  /*
   * stStartOfTurn: called at the beggining of each player turn
   */
  public function stStartOfTurn()
  {
    $this->log->startTurn();
    $arg = $this->argStartOfTurn();
    $canApplyLaw = array_reduce($arg['_private']['active']['dominos'], function($carry, $domino){ return $carry || $domino['compatible']; }, false);
    if(!$canApplyLaw && empty($arg['pieces'])){
      self::DbQuery("UPDATE player SET player_score = 1 WHERE player_id != ". self::getActivePlayerId());
      $this->gamestate->nextState('endGame');
    }
  }

	public function argStartOfTurn()
  {
		return array_merge($this->argDrawStatus(), $this->argApplyLaw(), $this->argMovePiece());
  }

  public function debugTxt()
  {
    $arg = $this->argStartOfTurn();
    self::dump( 'arg', $arg );
  }


  /*
   * stEndOfTurn: called at the end of each player turn
   */
  public function stEndOfTurn()
  {
    $this->stCheckEndOfGame();
    $this->gamestate->nextState('next');
  }


  /*
   * stCheckEndOfGame: check if the game is finished
   */
  public function stCheckEndOfGame()
  {
    if ($this->log->getIsAcceptDraw()){
      self::DbQuery("UPDATE player SET player_score = 1");
      $this->gamestate->nextState('endGame');
      return;
    }

		foreach($this->playerManager->getPlayers() as $player){
			$pos = ($player->getNo() == 1)? ['x' => 0, 'y' => 3] : ['x' => 3, 'y' => 0];
			$piece = self::getObjectFromDB("SELECT piece FROM board WHERE x = {$pos['x']} AND y = {$pos['y']}");
			if($piece['piece'] == $player->getNo()){
	      self::notifyAllPlayers('message', clienttranslate('${player_name} wins!'), [
	        'player_name' => $player->getName(),
	      ]);
		    self::DbQuery("UPDATE player SET player_score = 1 WHERE player_id = {$player->getId()}");
		    $this->gamestate->nextState('endGame');
			}
		}
  }


	/*
	 * cancelPreviousWorks: called when a player decide to go back at the beggining of the turn
	 */
	public function cancelPreviousWorks()
	{
		self::checkAction('cancel');

		if ($this->log->getLastActions() == null) {
			throw new BgaUserException(_("You have nothing to cancel"));
		}

		// Undo the turn
		$moveIds = $this->log->cancelTurn();
		foreach($this->playerManager->getPlayers() as $player){
			$arg = $this->getAllDatas($player->getId());
			$arg['player_name'] = self::getActivePlayerName();
			$arg['moveIds'] = $moveIds;
			self::notifyPlayer($player->getId(),'cancel', clienttranslate('${player_name} restarts their turn'), $arg);
    }

		$this->gamestate->nextState('cancel');
	}

	/*
	 * confirmTurn: called whenever a player confirm their turn
	 */
	public function confirmTurn()
	{
		$this->gamestate->nextState('confirm');
	}

	/*
	 * skip: called whenever a player skip an action
	 */
	public function skip()
	{
		$args = $this->gamestate->state()['args'];
    if (!$args['skippable']) {
      throw new BgaUserException(_("You can't skip this action"));
    }

		$this->gamestate->nextState('skip');
	}


	//////////////////////////////////
	/////////   Apply a law   ////////
	//////////////////////////////////
	public function argApplyLaw()
	{
		return [
			'cancelable' => $this->log->getLastActions() != null,
			'skippable' => !is_null($this->log->getLastMove()),
			'_private' => [
				'active' => $this->playerManager->getPlayer()->getPlayableLaws()
			]
		];
	}


	public function applyLaw($dominoId, $pos)
	{
		$arg = $this->argApplyLaw();
		Utils::checkApplyLaw($arg, $dominoId, $pos);

		$domino = self::getObjectFromDB("SELECT * FROM domino WHERE id = {$dominoId}");
		$this->board->applyLaw($domino, $pos);

		$state = "endTurn";
		if($domino['type'] == "adaptation")
			$state = "adaptation";

		$this->gamestate->nextState($state);
	}


	public function adaptDomino($dominoId, $type, $cause, $effect)
	{
		$this->board->adaptDomino($dominoId, $type, $cause, $effect);
		$this->gamestate->nextState("endTurn");
	}



	//////////////////////////////////
	/////////   Move a piece  ////////
	//////////////////////////////////
	public function argMovePiece()
	{
		return $this->playerManager->getPlayer()->getMovablePieces();
	}

	public function movePiece($pieceId, $pos)
	{
		$arg = $this->argMovePiece();
		Utils::checkMovePiece($arg, $pieceId, $pos);

		$piece = self::getObjectFromDB("SELECT * FROM board WHERE id = {$pieceId}");
		$this->board->movePiece($piece, $pos);

		$state = "endTurn";
		if(is_null($this->log->getLastLaw()) && !empty($this->argApplyLaw()['_private']['active']['dominos']))
			$state = "applyLaw";
		$this->gamestate->nextState($state);
	}


	//////////////////////////////////
	/////////      Draws      ////////
	//////////////////////////////////
	public function argDrawStatus()
	{
		return [
			'drawStatus' => $this->log->getIsSuggestDraw(),
      'recentDecline' => $this->log->getIsRecentDeclineDraw()
		];
	}

  public function suggestDraw()
	{
    $this->board->suggestDraw();
		$this->gamestate->nextState("suggestDraw");
	}

  public function acceptDraw()
	{
    if (!$this->log->getIsSuggestDraw()) {
      throw new BgaUserException(_("No draw proposal at this time"));
    }
    $this->board->acceptDraw();
		$this->gamestate->nextState("acceptDraw");
	}

  public function declineDraw()
	{
    if (!$this->log->getIsSuggestDraw()) {
      throw new BgaUserException(_("No draw proposal at this time"));
    }
    $this->board->declineDraw();
		$this->gamestate->nextState("declineDraw");
	}


  ////////////////////////////////////
  ////////////   Zombie   ////////////
  ////////////////////////////////////
  /*
   * zombieTurn:
   *   This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
   *   You can do whatever you want in order to make sure the turn of this player ends appropriately
   */
  public function zombieTurn($state, $activePlayer)
  {
    if (array_key_exists('zombiePass', $state['transitions'])) {
      $this->playerManager->eliminate($activePlayer);
      $this->gamestate->nextState('zombiePass');
    } else {
      throw new BgaVisibleSystemException('Zombie player ' . $activePlayer . ' stuck in unexpected state ' . $state['name']);
    }
  }

  /////////////////////////////////////
  //////////   DB upgrade   ///////////
  /////////////////////////////////////
  // You don't have to care about this until your game has been published on BGA.
  // Once your game is on BGA, this method is called everytime the system detects a game running with your old Database scheme.
  // In this case, if you change your Database scheme, you just have to apply the needed changes in order to
  //   update the game database and allow the game to continue to run with your new version.
  /////////////////////////////////////
  /*
   * upgradeTableDb
   *  - int $from_version : current version of this game database, in numerical form.
   *      For example, if the game was running with a release of your game named "140430-1345", $from_version is equal to 1404301345
   */
  public function upgradeTableDb($from_version)
  {
  }
}
