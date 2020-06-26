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
}
