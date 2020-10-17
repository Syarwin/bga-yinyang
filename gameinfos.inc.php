<?php

/*
    From this file, you can edit the various meta-information of your game.

    Once you modified the file, don't forget to click on "Reload game informations" from the Control Panel in order in can be taken into account.

    See documentation about this file here:
    http://en.doc.boardgamearena.com/Game_meta-information:_gameinfos.inc.php

*/

$gameinfos = [
  'game_name' => "Yin Yang",
  'designer' => 'Gauthier Fourcade',
  'artist' => '',
  'year' => 2018,
  'publisher' => 'Prise de tête',
  'publisher_website' => 'https://www.kstete.com/',
  'publisher_bgg_id' => 0,
  'bgg_id' => 5236,
  'players' => [2],
  'suggest_player_number' => null,
  'not_recommend_player_number' => null,

  'estimated_duration' => 30,
  'fast_additional_time' => 30,
  'medium_additional_time' => 40,
  'slow_additional_time' => 50,

  'tie_breaker_description' => "",
  'losers_not_ranked' => false,

  'is_beta' => 1,
  'is_coop' => 0,

  'complexity' => 3,
  'luck' => 0,
  'strategy' => 5,
  'diplomacy' => 3,

  'player_colors' => ["ff0000", "008000", "0000ff", "ffa500", "773300"],
  'favorite_colors_support' => true,
  'disable_player_order_swap_on_rematch' => false,
  'game_interface_width' => [
    'min' => 740,
    'max' => null
  ],

  'presentation' => array(
  //    totranslate("This wonderful game is about geometric shapes!"),
  //    totranslate("It was awarded best triangle game of the year in 2005 and nominated for the Spiel des Jahres."),
  //    ...
  ),

// Games categories
//  You can attribute a maximum of FIVE "tags" for your game.
//  Each tag has a specific ID (ex: 22 for the category "Prototype", 101 for the tag "Science-fiction theme game")
//  Please see the "Game meta information" entry in the BGA Studio documentation for a full list of available tags:
//  http://en.doc.boardgamearena.com/Game_meta-information:_gameinfos.inc.php
//  IMPORTANT: this list should be ORDERED, with the most important tag first.
//  IMPORTANT: it is mandatory that the FIRST tag is 1, 2, 3 and 4 (= game category)
  'tags' => [2],


//////// BGA SANDBOX ONLY PARAMETERS (DO NOT MODIFY)
'is_sandbox' => false,
'turnControl' => 'simple'
////////
];
