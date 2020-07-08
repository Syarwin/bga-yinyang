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
    $dominos = array_values(array_filter($arg['_private']['active']['dominos'], function ($domino) use ($dominoId) {
      return $domino['id'] == $dominoId;
    }));
    if (count($dominos) != 1) {
      throw new BgaUserException(_("Error when trying to apply domino"));
    }

    $locations = array_values(array_filter($dominos[0]['locations'], function ($w) use ($pos) {
      return $w['x'] == $pos['x'] && $w['y'] == $pos['y'];
    }));
    if (count($locations) != 1) {
      throw new BgaUserException(_("You cannot reach use the domino on this space"));
    }
  }


  public static function checkMovePiece($arg, $pieceId, $pos)
  {
    $pieces = array_values(array_filter($arg['pieces'], function ($piece) use ($pieceId) {
      return $piece['id'] == $pieceId;
    }));
    if (count($pieces) != 1) {
      throw new BgaUserException(_("Error when trying to move piece"));
    }

    $locations = array_values(array_filter($pieces[0]['moves'], function ($w) use ($pos) {
      return $w['x'] == $pos['x'] && $w['y'] == $pos['y'];
    }));
    if (count($locations) != 1) {
      throw new BgaUserException(_("You cannot reach this space with this piece"));
    }
  }
}
