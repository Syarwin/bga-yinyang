<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * YinYang implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 *
 * yinyang.action.php
 *
 * YinYang main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/yinyang/yinyang/myAction.html", ...)
 *
 */


class action_yinyang extends APP_GameAction
{
  // Constructor: please do not modify
  public function __default()
  {
    if( self::isArg( 'notifwindow') )
    {
      $this->view = "common_notifwindow";
      $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
    }
    else
    {
      $this->view = "yinyang_yinyang";
      self::trace( "Complete reinitialization of board game" );
    }
  }

  public function updateDomino()
  {
    self::setAjaxMode();
    $dominoId = self::getArg( "dominoId", AT_posint, true );
    $type = self::getArg("type", AT_alphanum, true);
    $cause  = explode(',', self::getArg("cause", AT_numberlist, true ));
    $effect = explode(',', self::getArg("effect", AT_numberlist, true ));
    $this->game->updateDomino($dominoId, $type, $cause, $effect);
    self::ajaxResponse();
  }

  public function confirmDominos()
  {
    self::setAjaxMode();
    $playerId = self::getArg( "playerId", AT_posint, true );
    $this->game->confirmDominos($playerId);
    self::ajaxResponse();
  }

  public function chooseMove()
  {
    self::setAjaxMode();
    $this->game->gamestate->nextState('move');
    self::ajaxResponse();
  }

  public function chooseApplyLaw()
  {
    self::setAjaxMode();
    $this->game->gamestate->nextState('applyLaw');
    self::ajaxResponse();
  }


  public function applyLaw()
  {
    self::setAjaxMode();
    $dominoId = self::getArg( "dominoId", AT_posint, true );
    $x = self::getArg( "x", AT_posint, true );
    $y = self::getArg( "y", AT_posint, true );
    $this->game->applyLaw($dominoId, ['x' => $x, 'y' => $y]);
    self::ajaxResponse();
  }

  public function adaptDomino()
  {
    self::setAjaxMode();
    $dominoId = self::getArg( "dominoId", AT_posint, true );
    $type = self::getArg("type", AT_alphanum, true);
    $cause  = explode(',', self::getArg("cause", AT_numberlist, true ));
    $effect = explode(',', self::getArg("effect", AT_numberlist, true ));
    $this->game->adaptDomino($dominoId, $type, $cause, $effect);
    self::ajaxResponse();
  }


  public function movePiece()
  {
    self::setAjaxMode();
    $pieceId = self::getArg( "pieceId", AT_posint, true );
    $x = self::getArg( "x", AT_posint, true );
    $y = self::getArg( "y", AT_posint, true );
    $this->game->movePiece($pieceId, ['x' => $x, 'y' => $y]);
    self::ajaxResponse();
  }



  public function cancelPreviousWorks()
  {
    self::setAjaxMode();
    $this->game->cancelPreviousWorks();
    self::ajaxResponse();
  }


  public function confirmTurn()
  {
    self::setAjaxMode();
    $this->game->confirmTurn();
    self::ajaxResponse();
  }

  public function skip()
  {
    self::setAjaxMode();
    $this->game->skip();
    self::ajaxResponse();
  }

}
