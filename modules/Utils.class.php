<?php

abstract class Utils extends APP_GameClass
{
  public static function filter(&$data, $filter)
  {
    $data = array_values(array_filter($data, $filter));
  }

  public static function cleanDominos(&$data)
  {
    self::filter($data['dominos'], function($domino){
      return count($domino['locations']) > 0;
    });
  }


  public static function checkApplyLaw($arg, $dominoId, $pos)
  {
    $dominos = array_values(array_filter($arg['dominos'], function ($domino) use ($dominoId) {
      return $domino['id'] == $dominoId;
    }));
    if (count($dominos) != 1) {
      throw new BgaUserException(_("This worker can't be used"));
    }

    $locations = array_values(array_filter($dominos[0]['locations'], function ($w) use ($pos) {
      return $w['x'] == $pos['x'] && $w['y'] == $pos['y'];
    }));
    if (count($locations) != 1) {
      throw new BgaUserException(_("You cannot reach this space with this worker"));
    }
  }

}
