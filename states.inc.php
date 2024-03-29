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
 * states.inc.php
 *
 * YinYang game states description
 *
 */

$machinestates = [
  /*
   * BGA framework initial state. Do not modify.
   */
  ST_GAME_SETUP => [
    'name' => 'gameSetup',
    'description' => '',
    'type' => 'manager',
    'action' => 'stGameSetup',
    'transitions' => [
      '' => ST_PRE_BUILD_DOMINOS,
    ],
  ],

  ST_PRE_BUILD_DOMINOS => [
    'name' => 'preBuildDominos',
    'type' => 'game',
    'action' => 'stPreBuildDominos',
    'transitions' => [
      '' => ST_BUILD_DOMINOS,
    ],
  ],

  ST_BUILD_DOMINOS => [
    'name' => 'buildDominos',
    'description' => clienttranslate('Waiting for the other player to finish building its dominos'),
    'descriptionmyturn' => clienttranslate('${you} must build your dominos'),
    'type' => 'multipleactiveplayer',
    'args' => 'argBuildDominos',
    'transitions' => [
      'start' => ST_START_PLAYING,
      'endGame' => ST_GAME_END,
    ],
  ],

  ST_START_PLAYING => [
    'name' => 'startPlaying',
    'type' => 'game',
    'action' => 'stStartPlaying',
    'transitions' => [
      '' => ST_START_OF_TURN,
    ],
  ],


  ST_NEXT_PLAYER => [
    'name' => 'nextPlayer',
    'description' => '',
    'type' => 'game',
    'action' => 'stNextPlayer',
    'transitions' => [
      'next' => ST_NEXT_PLAYER,
      'start' => ST_START_OF_TURN,
      'endGame' => ST_GAME_END,
    ],
    'updateGameProgression' => true,
  ],

  ST_START_OF_TURN => [
    'name' => 'startOfTurn',
    'description' => clienttranslate('${actplayer} may move a piece or apply a law'),
    'descriptionmyturn' => clienttranslate('${you} may move a piece or apply a law'),
    'descriptiondraw' => clienttranslate('${actplayer} must accept or deny a draw proposal'),
    'descriptionmyturndraw' => clienttranslate('${you} must accept or deny a draw proposal'),
    'type' => 'activeplayer',
    'action' => 'stStartOfTurn',
    'args' => 'argStartOfTurn',
    'transitions' => [
      'move' => ST_MOVE,
      'applyLaw' => ST_APPLY_LAW,
      'suggestDraw' => ST_PRE_END_OF_TURN,
      'acceptDraw' => ST_PRE_END_OF_TURN,
      'declineDraw' => ST_PRE_END_OF_TURN,
      'endGame' => ST_GAME_END,
    ],
  ],

  ST_MOVE => [
    'name' => 'movePiece',
    'description' => clienttranslate('${actplayer} must move a piece'),
    'descriptionmyturn' => clienttranslate('${you} must move a piece'),
    'descriptionskippable' => clienttranslate('${actplayer} may move a piece'),
    'descriptionmyturnskippable' => clienttranslate('${you} may move a piece'),
    'type' => 'activeplayer',
    'args' => 'argMovePiece',
    'possibleactions' => ['movePiece', 'skip', 'cancel'],
    'transitions' => [
      'applyLaw' => ST_APPLY_LAW,
      'skip' => ST_PRE_END_OF_TURN,
      'endTurn' => ST_PRE_END_OF_TURN,
      'endGame' => ST_GAME_END,
      'cancel'  => ST_START_OF_TURN,
    ],
  ],


  ST_APPLY_LAW => [
    'name' => 'applyLaw',
    'description' => clienttranslate('${actplayer} must apply a law'),
    'descriptionmyturn' => clienttranslate('${you} must apply a law'),
    'descriptionskippable' => clienttranslate('${actplayer} may apply a law'),
    'descriptionmyturnskippable' => clienttranslate('${you} may apply a law'),
    'type' => 'activeplayer',
    'args' => 'argApplyLaw',
    'possibleactions' => ['applyLaw', 'skip', 'cancel'],
    'transitions' => [
      'adaptation' => ST_ADAPT,
      'skip' => ST_PRE_END_OF_TURN,
      'endTurn' => ST_PRE_END_OF_TURN,
      'endGame' => ST_GAME_END,
      'cancel'     => ST_START_OF_TURN,
    ],
  ],

  ST_ADAPT => [
    'name' => 'adaptDomino',
    'description' => clienttranslate('${actplayer} may change one of its law'),
    'descriptionmyturn' => clienttranslate('${you} may change one of your law'),
    'type' => 'activeplayer',
    'args' => 'argBuildDominos',
    'transitions' => [
      'endTurn' => ST_PRE_END_OF_TURN,
    ],
  ],



  ST_PRE_END_OF_TURN => [
    'name' => 'confirmTurn',
    'description' => clienttranslate('${actplayer} must confirm or restart their turn'),
    'descriptionmyturn' => clienttranslate('${you} must confirm or restart your turn'),
    'type' => 'activeplayer',
    'possibleactions' => ['confirm','cancel'],
    'transitions' => [
      'zombiePass' => ST_END_OF_TURN,
      'endturn'    => ST_END_OF_TURN,
      'confirm'    => ST_END_OF_TURN,
      'cancel'     => ST_START_OF_TURN,
    ],
  ],


  ST_END_OF_TURN => [
    'name' => 'endOfTurn',
    'description' => '',
    'type' => 'game',
    'action' => 'stEndOfTurn',
    'transitions' => [
      'next' => ST_NEXT_PLAYER,
      'endGame' => ST_GAME_END,
    ],
  ],

  /*
   * BGA framework final state. Do not modify.
   */
  ST_GAME_END => [
    'name' => 'gameEnd',
    'description' => clienttranslate('End of game'),
    'type' => 'manager',
    'action' => 'stGameEnd',
    'args' => 'argGameEnd'
  ]

];
