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
 * yinyang.view.php
 */

require_once( APP_BASE_PATH."view/common/game.view.php" );

class view_yinyang_yinyang extends game_view
{
  function getGameName() {
    return "yinyang";
  }

  function build_page( $viewArgs )
  {
    // Get players & players number
    $players = $this->game->loadPlayersBasicInfos();
    $players_nbr = count( $players );

    $this->page->begin_block( "yinyang_yinyang", "square" );
    for($x = 0; $x < 4; $x++){
    for($y = 0; $y < 4; $y++){
      $this->page->insert_block("square", [
        'X' => $x,
        'Y' => $y,
      ]);
    }}
  }
}
