/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * YinYang implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * yinyang.js
 *
 * YinYang user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

//# sourceURL=yinyang.js
//@ sourceURL=yinyang.js
var isDebug = true;
var debug = isDebug ? console.info.bind(window.console) : function () { };
define(["dojo", "dojo/_base/declare", "ebg/core/gamegui", "ebg/counter"], function (dojo, declare) {
  return declare("bgagame.yinyang", ebg.core.gamegui, {

/*
 * Constructor
 */
constructor: function () { },

/*
 * Setup:
 *  This method set up the game user interface according to current game situation specified in parameters
 *  The method is called each time the game interface is displayed to a player, ie: when the game starts and when a player refreshes the game page (F5)
 *
 * Params :
 *  - mixed gamedatas : contains all datas retrieved by the getAllDatas PHP method.
 */
setup: function (gamedatas) {
  var _this = this;
  debug('SETUP', gamedatas);

  // Setup player's board
  gamedatas.fplayers.forEach(function(player){
 //    dojo.place( _this.format_block( 'jstpl_player_panel', player) , 'overall_player_board_' + player.id );
 //    player.tiles.forEach(_this.addTile.bind(_this));
  });

  // Setup board
  for(var i = 0; i < 4; i++)
  for(var j = 0; j < 4; j++){
    dojo.attr('square-' + i + "-" + j, "data-token", gamedatas.board[i][j]);
  }

  // Setup hand
  gamedatas.hand.forEach(function(domino){
    dojo.place( _this.format_block( 'jstpl_domino', domino) , 'player-private-hand' );
    dojo.query("#domino-" + domino.id + " .square").forEach(function(square){
      dojo.connect(square, 'onclick', function(ev){ _this.onClickDominoSquare(domino.id, square); });
    })
    dojo.query("#domino-" + domino.id + " .domino-types div").forEach(function(type){
      dojo.connect(type, 'onclick', function(ev){ _this.onClickDominoType(domino.id, type); });
    })
  });

  // Setup game notifications
  this.setupNotifications();
},



/*
 * onEnteringState:
 * 	this method is called each time we are entering into a new game state.
 * params:
 *  - str stateName : name of the state we are entering
 *  - mixed args : additional information
 */
onEnteringState: function (stateName, args) {
  debug('Entering state: ' + stateName, args);

   // Stop here if it's not the current player's turn for some states
 //  if (["playerBuild"].includes(stateName) && !this.isCurrentPlayerActive()) return;

  // Call appropriate method
  var methodName = "onEnteringState" + stateName.charAt(0).toUpperCase() + stateName.slice(1);
  if (this[methodName] !== undefined)
    this[methodName](args.args);
},



/*
 * onLeavingState:
 * 	this method is called each time we are leaving a game state.
 *
 * params:
 *  - str stateName : name of the state we are leaving
 */
onLeavingState: function (stateName) {
  debug('Leaving state: ' + stateName);
  this.clearPossible();
},


/*
 * onUpdateActionButtons:
 * 	called by BGA framework before onEnteringState
 *  in this method you can manage "action buttons" that are displayed in the action status bar (ie: the HTML links in the status bar).
 */
onUpdateActionButtons: function (stateName, args, suppressTimers) {
  debug('Update action buttons: ' + stateName, args); // Make sure it the player's turn

  if (!this.isCurrentPlayerActive())
    return;

 /*
   if (stateName == "confirmTurn") {
     this.addActionButton('buttonConfirm', _('Confirm'), 'onClickConfirm', null, false, 'blue');
     this.addActionButton('buttonCancel', _('Restart turn'), 'onClickCancel', null, false, 'gray');
     if (!suppressTimers)
       this.startActionTimer('buttonConfirm');
   }
 */
},




onEnteringStateBuildDominos: function(args){
  this.makeDominosEditable(args._private.dominos);
},


makeDominosEditable: function(dominos){
  this._editableDominos = dominos;
  dominos.forEach(function(dominoId){
    dojo.addClass('domino-' + dominoId, 'editable');
  })
  this.checkAllDominos();
},


onClickDominoSquare: function(dominoId, square){
  if(!dojo.hasClass('domino-' + dominoId, 'editable'))
    return;

  var token = parseInt(dojo.attr(square, 'data-token'));
  dojo.attr(square, 'data-token', (token + 1) % 3);
  this.checkAllDominos();
},

onClickDominoType: function(dominoId, type){
  if(!dojo.hasClass('domino-' + dominoId, 'editable'))
    return;

  dojo.attr('domino-' + dominoId, 'data-type', type.className.substr(12));
  this.checkAllDominos();
},

checkAllDominos: function(){
  var _this = this;
  this.removeActionButtons();
  if(this._editableDominos.reduce(function(carry, dominoId){ return carry && _this.checkDomino(dominoId); }, true))
    this.addActionButton('buttonConfirmDominos', _('Confirm'), 'onClickConfirmDominos', null, false, 'blue');
},

checkDomino: function(dominoId){
  var dom = "domino-" + dominoId;
  var type = dojo.attr(dom, 'data-type');

  var cause = dojo.query("#"+dom + " .domino-cause div").map(function(square){ return dojo.attr(square, 'data-token'); });
  var effect = dojo.query("#"+dom + " .domino-effect div").map(function(square){ return dojo.attr(square, 'data-token'); });

  var okCause = true, okEffect = true;
  var nCause = 0, nEffect = 0, newEffect = 0, newCause = 0, diff = 0;
  for(var i = 0; i < 4; i++){
    if(cause[i] == 0 && effect[i] != 0) newEffect++;
    if(effect[i] == 0 && cause[i] != 0) newCause++;

    if(cause[i] != 0) nCause++;
    if(effect[i] != 0) nEffect++;

    if(cause[i] != 0 && effect[i] != 0 && cause[i] != effect[i]) diff++;
  }

  if(type == "creation"){
    okCause = (nCause <= 2);
    okEffect = (diff == 0) && (newEffect == 2);
  } else if(type == "destruction"){
    okCause = (nCause > 0);
    okEffect = (diff == 0) && (newCause == 1) && (newEffect == 0);
  }
  else if(type == "empty"){
    okCause = false;
    okEffect = false;
  }

  if(okCause && okEffect){
    dojo.addClass(dom, 'valid');
    this.ajaxcall("/yinyang/yinyang/updateDomino.html", {
      dominoId: dominoId,
      type:type,
      cause:cause.join(','),
      effect:effect.join(','),
    }, this, function(res){});
  }
  else
    dojo.removeClass(dom, 'valid');

  if(okCause) dojo.query("#"+dom + " .domino-cause").removeClass("invalid");
  else dojo.query("#"+dom + " .domino-cause").addClass("invalid");

  if(okEffect) dojo.query("#"+dom + " .domino-effect").removeClass("invalid");
  else dojo.query("#"+dom + " .domino-effect").addClass("invalid");

  return (okCause && okEffect);
},


onClickConfirmDominos: function(){
  this.takeAction("confirmDominos", {
    playerId: this.getActivePlayerId(),
  });
},


 ////////////////////////////////
 ////////////////////////////////
 /////////    Utils    //////////
 ////////////////////////////////
 ////////////////////////////////


 /*
  * clearPossible:	clear every clickable space
  */
 clearPossible: function clearPossible() {
   this.removeActionButtons();
   this.onUpdateActionButtons(this.gamedatas.gamestate.name, this.gamedatas.gamestate.args);
 },


 /*
  * takeAction: default AJAX call with locked interface
  */
 takeAction: function (action, data, callback) {
   data = data || {};
   data.lock = true;
   callback = callback || function (res) { };
   this.ajaxcall("/yinyang/yinyang/" + action + ".html", data, this, callback);
 },


 /*
  * slideTemporary: a wrapper of slideTemporaryObject using Promise
  */
 slideTemporary: function (template, data, container, sourceId, targetId, duration, delay) {
   var _this = this;
   return new Promise(function (resolve, reject) {
     var animation = _this.slideTemporaryObject(_this.format_block(template, data), container, sourceId, targetId, duration, delay);
     setTimeout(function(){
       resolve();
     }, duration + delay)
   });
 },


 ///////////////////////////////////////////////////
 //////   Reaction to cometD notifications   ///////
 ///////////////////////////////////////////////////

 /*
  * setupNotifications:
  *  In this method, you associate each of your game notifications with your local method to handle it.
  *	Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" in the santorini.game.php file.
  */
 setupNotifications: function () {
   var notifs = [
 //    ['build', 1000],
   ];

   var _this = this;
   notifs.forEach(function (notif) {
     dojo.subscribe(notif[0], _this, "notif_" + notif[0]);
     _this.notifqueue.setSynchronous(notif[0], notif[1]);
   });
 }

    });
 });
