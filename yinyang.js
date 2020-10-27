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
  function override_addMoveToLog(logId, moveId) {
    // [Undocumented] Called by BGA framework on new log notification message
    // Handle cancelled notifications
    this.inherited(override_addMoveToLog, arguments);
    if (this.gamedatas.cancelMoveIds && this.gamedatas.cancelMoveIds.includes(+moveId)) {
      debug('Cancel notification message for move ID ' + moveId + ', log ID ' + logId);
      dojo.addClass('log_' + logId, 'cancel');
    }
  }

  return declare("bgagame.yinyang", ebg.core.gamegui, {
    addMoveToLog: override_addMoveToLog,

/*
 * Constructor
 */
constructor () {
  this._editableDominos = [];
  this.default_viewport = 'width=900, user-scalable=yes';
},

/*
 * Setup:
 *  This method set up the game user interface according to current game situation specified in parameters
 *  The method is called each time the game interface is displayed to a player, ie: when the game starts and when a player refreshes the game page (F5)
 *
 * Params :
 *  - mixed gamedatas : contains all datas retrieved by the getAllDatas PHP method.
 */
setup (gamedatas) {
  debug('SETUP', gamedatas);

  // Setup board
  this.setBoard(gamedatas.board);
  var squares = [];
  for(var i = 0; i < 4; i++)
  for(var j = 0; j < 4; j++){
    squares.push({x:i, y:j});
  }
  squares.forEach(square => {
    dojo.connect($('square-' + square.x + "-" + square.y), 'onclick', ev => this.onClickSquare(square.x, square.y));
  });

  // Setup overlay for applying laws
  var positions = [];
  for(var i = 0; i < 3; i++)
  for(var j = 0; j < 3; j++){
    positions.push({x:i, y:j});
  }
  positions.forEach(pos => {
    var overlay = $('overlay-' + pos.x + "-" + pos.y);
    dojo.connect(overlay, "onmouseenter", ev =>  this.onMouseEnterOverlay(pos.x, pos.y));
    dojo.connect(overlay, "onmouseout", ev => this.onMouseOutOverlay());
    dojo.connect(overlay, "onclick", ev => this.onClickOverlay(pos.x, pos.y));
  })


  // Setup dominos
  gamedatas.hand.forEach(domino => this.addDomino(domino, 'player-private-hand'));
  gamedatas.player.forEach(domino => this.addDomino(domino, 'dominos-player'));
  gamedatas.opponent.forEach(domino => this.addDomino(domino, 'dominos-opponent'));

  // Highlight last action
  if(gamedatas.action){
    setTimeout( () => {
      if(gamedatas.action.action == "applyLaw"){
        this.highlightDomino("domino-" + gamedatas.action.domino_id);
        let zone = gamedatas.action.action_arg.to;
        this.highlightZone(zone.x, zone.y, gamedatas.action.player_id != this.player_id);
      } else if(gamedatas.action.action == "movePiece"){
        let arg = gamedatas.action.action_arg;
        this.highlightSquare(arg.from.x, arg.from.y, gamedatas.action.player_id != this.player_id);
        this.highlightSquare(arg.to.x, arg.to.y, gamedatas.action.player_id != this.player_id);
      }

    }, 2000);
  }
  // Setup game notifications
  this.setupNotifications();
},


setBoard(board){
  for(var i = 0; i < 4; i++)
  for(var j = 0; j < 4; j++){
    dojo.attr('square-' + i + "-" + j, "data-token", board[i][j]);
  }
},

addDomino(domino, container, place){
  place = place || "first";
  dojo.place(this.format_block( 'jstpl_domino', domino) , container, place);
  dojo.connect($("domino-" + domino.id), 'onclick', ev => this.onClickDomino(domino.id));
  dojo.query("#domino-" + domino.id + " .square").forEach(square => {
    dojo.connect(square, 'onclick', ev => this.onClickDominoSquare(domino.id, square));
  })
  dojo.query("#domino-" + domino.id + " .domino-types div").forEach(type => {
    dojo.connect(type, 'onclick', ev => this.onClickDominoType(domino.id, type));
  })
},

/*
 * onEnteringState:
 * 	this method is called each time we are entering into a new game state.
 * params:
 *  - str stateName : name of the state we are entering
 *  - mixed args : additional information
 */
onEnteringState (stateName, args) {
  debug('Entering state: ' + stateName, args);

  // Update gamestate description when skippable
  if (args && args.args && args.args.skippable && this.gamedatas.gamestate.descriptionskippable) {
    this.gamedatas.gamestate.description = this.gamedatas.gamestate.descriptionskippable;
    this.gamedatas.gamestate.descriptionmyturn = this.gamedatas.gamestate.descriptionmyturnskippable;
    this.updatePageTitle();
  }

  // Stop here if it's not the current player's turn for some states
  if (["startOfTurn", "applyLaw", "movePiece", "adaptDomino"].includes(stateName) && (!this.isCurrentPlayerActive())) return;

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
onLeavingState (stateName) {
  debug('Leaving state: ' + stateName);
  this.clearPossible();
},


/*
 * onUpdateActionButtons:
 * 	called by BGA framework before onEnteringState
 *  in this method you can manage "action buttons" that are displayed in the action status bar (ie: the HTML links in the status bar).
 */
onUpdateActionButtons (stateName, args, suppressTimers) {
  debug('Update action buttons: ' + stateName, args); // Make sure it the player's turn

  if (!this.isCurrentPlayerActive())
    return;

  if (stateName == "buildDominos"){
    this.checkAllDominos(false);
  }

  if (stateName == "confirmTurn") {
    this.addActionButton('buttonConfirm', _('Confirm'), 'onClickConfirm', null, false, 'blue');
    this.addActionButton('buttonCancel', _('Restart turn'), 'onClickCancel', null, false, 'gray');
  }

  if (stateName == "applyLaw" || stateName == "movePiece") {
    if (args.skippable) {
      this.addActionButton('buttonSkip', _('Skip'), 'onClickSkip', null, false, 'gray');
    }
    if (args.cancelable) {
      this.addActionButton('buttonCancel', _('Restart turn'), 'onClickCancel', null, false, 'gray');
    }
  }
},


////////////////////////////////
////////////////////////////////
///////  Confirm/Cancel  ///////
////////////////////////////////
////////////////////////////////

onEnteringStateConfirmTurn(args){
  this.startActionTimer('buttonConfirm');
},


/*
 * Add a timer to an action and trigger action when timer is done
 */
startActionTimer (buttonId) {
  if(!$(buttonId))
    return;
  this.actionTimerLabel = $(buttonId).innerHTML;
  this.actionTimerSeconds = 15;
  this.actionTimerFunction = () => {
    var button = $(buttonId);
    if (button == null) {
      this.stopActionTimer();
    } else if (this.actionTimerSeconds-- > 1) {
      debug('Timer ' + buttonId + ' has ' + this.actionTimerSeconds + ' seconds left');
      button.innerHTML = this.actionTimerLabel + ' (' + this.actionTimerSeconds + ')';
    } else {
      debug('Timer ' + buttonId + ' execute');
      button.click();
    }
  };
  this.actionTimerFunction();
  this.actionTimerId = window.setInterval(this.actionTimerFunction, 1000);
  debug('Timer #' + this.actionTimerId + ' ' + buttonId + ' start');
},

stopActionTimer() {
  if (this.actionTimerId != null) {
    debug('Timer #' + this.actionTimerId + ' stop');
    window.clearInterval(this.actionTimerId);
    delete this.actionTimerId;
  }
},


/*
 * onClickSkip: is called when the active player decide to skip work
 */
onClickSkip() {
  if (!this.checkAction('skip')) {
    return;
  }
  this.takeAction("skip");
  this.clearPossible();
},


/*
 * onClickCancel: is called when the active player decide to cancel previous works
 */
onClickCancel() {
  if (!this.checkAction('cancel')) {
    return;
  }
  this.takeAction("cancelPreviousWorks");
  this.clearPossible();
},


/*
 * onClickConfirm: is called when the active player decide to confirm their turn
 */
onClickConfirm() {
  if (!this.checkAction('confirm')) {
    return;
  }
  this.takeAction("confirmTurn");
},


notif_cancel(n) {
  debug('Notif: cancel turn', n.args);

  // Reset board
  this.setBoard(n.args.board);
  // Setup dominos
  dojo.query(".domino").forEach(dojo.destroy);
  n.args.hand.forEach(domino => this.addDomino(domino, 'player-private-hand'));
  n.args.player.forEach(domino => this.addDomino(domino, 'dominos-player'));
  n.args.opponent.forEach(domino => this.addDomino(domino, 'dominos-opponent'));

  this.cancelNotifications(n.args.moveIds);
},

/*
 * cancelNotifications: cancel past notification log messages the given move IDs
 */
cancelNotifications(moveIds) {
 for (var logId in this.log_to_move_id) {
   var moveId = +this.log_to_move_id[logId];
   if (moveIds.includes(moveId)) {
     debug('Cancel notification message for move ID ' + moveId + ', log ID ' + logId);
     dojo.addClass('log_' + logId, 'cancel');
   }
 }
},



////////////////////////////////
////////////////////////////////
////////  Build dominos  ///////
////////////////////////////////
////////////////////////////////

onEnteringStateBuildDominos(args){
  this._limit = null;
  this.makeDominosEditable(args._private.dominos);
},

onEnteringStateAdaptDomino(args){
  this._limit = 1;
  this.makeDominosEditable(args._private.dominos);
},


onClickCancelModifs(){
  this.makeDominosEditable(this._editableDominos);
},

makeDominosEditable(dominos){
  if(!this.isCurrentPlayerActive())
    return;

  this._editableDominos = dominos;
  this._confirm = false;
  this._dominoId = null;
  dominos.forEach(domino => {
    this.addDomino(domino, 'domino-' + domino.id, 'replace');
    dojo.addClass('domino-' + domino.id, 'editable');
  })
  this.checkAllDominos(false);
},


onClickDominoSquare(dominoId, square){
  if(!dojo.hasClass('domino-' + dominoId, 'editable') || !this.isCurrentPlayerActive())
    return;

  var token = parseInt(dojo.attr(square, 'data-token'));
  dojo.attr(square, 'data-token', (token + 1) % 3);
  this._dominoId = dominoId;
  this.checkAllDominos(true);
},

onClickDominoType(dominoId, type){
  if(!dojo.hasClass('domino-' + dominoId, 'editable'))
    return;

  dojo.attr('domino-' + dominoId, 'data-type', type.className.substr(12));
  this._dominoId = dominoId;
  this.checkAllDominos(true);
},

checkAllDominos(afterEvent){
  this.removeActionButtons();

  if(afterEvent && this._limit == 1){
    this.addActionButton('buttonCancelModifs', _('Cancel modifications'), 'onClickCancelModifs', null, false, 'gray');
    this._editableDominos.forEach(domino => {
      if(domino.id != this._dominoId)
        dojo.removeClass('domino-' + domino.id, 'editable');
    });
  }

  if(this._editableDominos.reduce((carry, domino) => carry && this.checkDomino(domino.id, afterEvent), true))
    this.addActionButton('buttonConfirmDominos', _('Confirm'), 'onClickConfirmDominos', null, false, 'blue');
},

checkDomino(dominoId, afterEvent){
  var dom = "domino-" + dominoId;
  var type = dojo.attr(dom, 'data-type');

  var cause = dojo.query("#"+dom + " .domino-cause div").map(square => dojo.attr(square, 'data-token'));
  var effect = dojo.query("#"+dom + " .domino-effect div").map(square => dojo.attr(square, 'data-token'));

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
    okEffect = (diff == 0) && (newEffect == 2) && (newCause == 0);
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
    var data = {
      dominoId: dominoId,
      type:type,
      cause:cause.join(','),
      effect:effect.join(','),
    };

    if(this._limit == 1 && this._confirm && afterEvent)
      this.takeAction("adaptDomino", data);
    if(this._limit != 1 && afterEvent)
      this.takeAction("updateDomino", data, false);
  }
  else
    dojo.removeClass(dom, 'valid');

  if(okCause) dojo.query("#"+dom + " .domino-cause").removeClass("invalid");
  else dojo.query("#"+dom + " .domino-cause").addClass("invalid");

  if(okEffect) dojo.query("#"+dom + " .domino-effect").removeClass("invalid");
  else dojo.query("#"+dom + " .domino-effect").addClass("invalid");

  return (okCause && okEffect);
},


onClickConfirmDominos(){
  if(this._limit == 1 && this._dominoId != null){
    this._confirm = true;
    this.checkDomino(this._dominoId, true);
  } else {
    this.takeAction("confirmDominos", {
      playerId: this.getCurrentPlayerId(),
    });
  }
  this.clearPossible();
},



////////////////////////////////
////////  Start of turn  ///////
////////////////////////////////
onEnteringStateStartOfTurn(args){
  if(args._private.dominos && args._private.dominos.length > 0)
    this.addActionButton('buttonApplyLaw', _('Apply law'), () => this.takeAction('chooseApplyLaw'), null, false, 'blue');

  if(args.pieces && args.pieces.length > 0)
    this.addActionButton('buttonMove', _('Move'), () => this.takeAction('chooseMove'), null, false, 'blue');
},



/////////////////////////////
/////////////////////////////
////////  Apply law  ////////
/////////////////////////////
/////////////////////////////

onEnteringStateApplyLaw(args){
  this._selectableDominos = args._private.dominos;
  this.makeDominosSelectable();
  dojo.style("yinyang-overlay", "display", "grid");
  dojo.style("yinyang-mask", "display", "grid");
},

makeDominosSelectable(){
  dojo.query(".domino").addClass("unselectable");
  this._selectableDominos.forEach(domino => {
    if(domino.compatible){
      dojo.removeClass('domino-' + domino.id, 'unselectable');
      dojo.addClass('domino-' + domino.id, 'selectable');
    } else {
      this.addTooltip('domino-' + domino.id, _("Uncompatible law"), '');
    }
  });
},

onClickDomino(dominoId){
  if(!dojo.hasClass('domino-' + dominoId, 'selectable') || !this.isCurrentPlayerActive())
    return;

  var domino = this._selectableDominos.find(elem => elem.id == dominoId);
  if(!domino)
    return;
  this._selectedDomino = domino;
  dojo.query('.domino').removeClass("selected");
  dojo.addClass('domino-' + domino.id, 'selected');

  dojo.query('#yinyang-mask .square').forEach(square => {
    var x = dojo.attr(square, "data-x"), y = dojo.attr(square, "data-y");
    dojo.attr(square, "data-token", domino.type == "adaptation"? domino['cause'+x+y] : domino['effect'+x+y]);
  })

  dojo.query('.overlay').removeClass("selectable");
  domino.locations.forEach(location => {
    dojo.addClass('overlay-' + location.x + "-" + location.y, "selectable");
  });
},


onMouseEnterOverlay(x,y){
  if(!dojo.hasClass('overlay-' + x + "-" + y, 'selectable') || !this.isCurrentPlayerActive())
    return;

  dojo.style('yinyang-mask', 'opacity', 1);
  dojo.style('yinyang-mask', 'top', (21 + 124*x) + "px");
  dojo.style('yinyang-mask', 'left', (21 + 124*y) + "px");
},

onMouseOutOverlay(){
  dojo.addClass("yinyang-mask", 'notransition');
  dojo.style("yinyang-mask", "opacity", 0);
  $("yinyang-mask").offsetHeight;
  dojo.removeClass("yinyang-mask", 'notransition');
},


onClickOverlay(x,y){
  if(!dojo.hasClass('overlay-' + x + "-" + y, 'selectable') || !this.isCurrentPlayerActive())
    return;

  this.takeAction("applyLaw", {
    dominoId:this._selectedDomino.id,
    x:x,
    y:y,
  });
},


notif_lawApplied(n){
  debug("Notif: a law was applied", n.args);
  this.setBoard(n.args.board);
  this.highlightZone(n.args.pos.x, n.args.pos.y, n.args.pId != this.player_id);

  var dominoId = 'domino-' + n.args.domino.id;
  if($(dominoId)){
    if(dojo.attr(dominoId, 'data-location') == "board"){
      this.highlightDomino(dominoId);
      return;
    }

    this.slideDestroy($(dominoId), "dominos-player", 1000, 0).then(() => {
      this.addDomino(n.args.domino, 'dominos-player');
      this.highlightDomino(dominoId);
    });
  }
  else {
    this.addDomino(n.args.domino, 'dominos-opponent');
    this.highlightDomino(dominoId);
  }
},


notif_dominoAdapted(n){
  debug("Notif: a domino was adapted", n.args);

  var dominoId = 'domino-' + n.args.dominoId;
  if($(dominoId) && $(dominoId).parentNode.id != "player-private-hand"){
    var target = ($(dominoId).parentNode.id == "dominos-player")?  "player-private-hand" : "player_boards";
    this.slideDestroy($(dominoId), target, 900, 0);
  }
},


notif_newDomino(n){
  if($('domino-' + n.args.domino.id))
    this.addDomino(n.args.domino, $('domino-' + n.args.domino.id), "replace");
  else
    this.addDomino(n.args.domino, 'player-private-hand');
},


/////////////////////////////
/////////////////////////////
////////  Move piece ////////
/////////////////////////////
/////////////////////////////

onEnteringStateMovePiece(args){
  this._selectablePieces = args.pieces;
  this._selectedPiece = null;
  this.makePiecesSelectable();
  dojo.style("yinyang-overlay", "display", "none");
  dojo.style("yinyang-mask", "display", "none");
},

makePiecesSelectable(){
  this._selectablePieces.forEach(piece => {
    dojo.addClass('square-' + piece.x + '-' + piece.y, 'selectable');
  });
},

onClickSquare(x,y){
  if(!dojo.hasClass('square-' + x + "-" + y, 'selectable') || !this.isCurrentPlayerActive())
    return;

  // Already a selected piece ? => finish move
  if(this._selectedPiece != null){
    this.takeAction("movePiece", {
      pieceId:this._selectedPiece.id,
      x:x,
      y:y,
    });
    return;
  }

  // Make the piece selected and highlight available spaces
  var piece = this._selectablePieces.find(elem => elem.x == x && elem.y == y);
  if(!piece)
    return;
  this._selectedPiece = piece;
  dojo.query('.square').removeClass("selected selectable");
  dojo.addClass('square-' + x + "-" + y, 'selected');

  this.addActionButton('buttonCancelSelectedPiece', _('Cancel'), () =>  this.cancelSelectedPiece(), null, false, 'gray');

  piece.moves.forEach(move => {
    dojo.addClass('square-' + move.x + "-" + move.y, "selectable");
  });
},

cancelSelectedPiece(){
  this._selectedPiece = null;
  dojo.query('.square').removeClass("selectable selected");
  this.removeActionButtons();
  this.onUpdateActionButtons(this.gamedatas.gamestate.name, this.gamedatas.gamestate.args);

  this.makePiecesSelectable();
},

notif_pieceMoved(n){
  debug("Notif: a law was applied", n.args);
  this.setBoard(n.args.board);

  this.highlightSquare(n.args.piece.x, n.args.piece.y, n.args.pId != this.player_id);
  this.highlightSquare(n.args.pos.x, n.args.pos.y, n.args.pId != this.player_id);
},


 ////////////////////////////////
 ////////////////////////////////
 /////////    Utils    //////////
 ////////////////////////////////
 ////////////////////////////////


 /*
  * clearPossible:	clear every clickable space
  */
 clearPossible() {
   this.removeActionButtons();
   this.onUpdateActionButtons(this.gamedatas.gamestate.name, this.gamedatas.gamestate.args);

   this._editableDominos.forEach(domino => {
     dojo.removeClass('domino-' + domino.id, 'editable');
   });
   this._editableDominos = [];

   dojo.query('.overlay').removeClass("selectable");
   this._selectedDomino = null;
   dojo.query('.domino').removeClass("selected");
   dojo.query('.domino').removeClass('selectable');
   dojo.query('.domino').removeClass('unselectable');
   dojo.query('.domino').forEach(domino => this.removeTooltip(domino.id));

   dojo.query('.square').removeClass("selectable selected");
   this._selectedPiece = null;
   this._selectablePieces = null;
 },


 /*
  * takeAction: default AJAX call with locked interface
  */
 takeAction (action, data, lock, callback) {
   data = data || {};
   if(lock)
    data.lock = true;
   callback = callback || function (res) { };
   this.ajaxcall("/yinyang/yinyang/" + action + ".html", data, this, callback);
 },


 /*
  * slideTemporary: a wrapper of slideTemporaryObject using Promise
  */
 slideTemporary (template, data, container, sourceId, targetId, duration, delay) {
   return new Promise((resolve, reject) => {
     var animation = this.slideTemporaryObject(this.format_block(template, data), container, sourceId, targetId, duration, delay);
     setTimeout(function(){
       resolve();
     }, duration + delay)
   });
 },

 slideDestroy (node, to, duration, delay) {
   return new Promise((resolve, reject) => {
     var animation = this.slideToObjectAndDestroy(node, to, duration, delay);
     setTimeout(function(){
       resolve();
     }, duration + delay)
   });
 },


 onScreenWidthChange () {
   dojo.style('page-content', 'zoom', '');
   dojo.style('page-title', 'zoom', '');
   dojo.style('right-side-first-part', 'zoom', '');
   let boardWidth = 1148;
   let boardHeight = 708;
   let box = $('yinyang-container').getBoundingClientRect();
   let h = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0) - box.y;
   let scale = Math.min(box['width'] / boardWidth, h / boardHeight);
   dojo.style("fixed-width-container", "transform", "scale(" + scale + ")");
   dojo.style('fixed-width-container', 'height', (708 * scale) + 'px');
 },


 highlightElem(id){
   let elem = $(id);
   if(!elem){
     console.log(id);
     return;
   }
   dojo.removeClass(elem, "highlight");
   elem.offsetWidth;
   dojo.addClass(elem, "highlight");
 },
 highlightDomino(id){
   this.highlightElem(id);
 },
 highlightSquare(x,y, flip){
   let xx = flip ? (3 - x) : parseInt(x);
   let yy = flip ? (3 - y) : parseInt(y);
   this.highlightElem("square-" + xx + "-" + yy);
 },
 highlightZone(x,y, flip){
   let xx = flip ? (2 - x) : parseInt(x);
   let yy = flip ? (2 - y) : parseInt(y);
   for(var i = 0; i < 2; i++)
   for(var j = 0; j < 2; j++)
    this.highlightSquare(xx + i, yy + j);
 },

 ///////////////////////////////////////////////////
 //////   Reaction to cometD notifications   ///////
 ///////////////////////////////////////////////////

 /*
  * setupNotifications:
  *  In this method, you associate each of your game notifications with your local method to handle it.
  *	Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" in the santorini.game.php file.
  */
 setupNotifications () {
   var notifs = [
     ['lawApplied', 1100],
     ['dominoAdapted', 1000],
     ['newDomino', 1],
     ['pieceMoved', 1000],
     ['cancel', 10],
   ];

   notifs.forEach(notif => {
     dojo.subscribe(notif[0], this, "notif_" + notif[0]);
     this.notifqueue.setSynchronous(notif[0], notif[1]);
   });
 }

    });
 });
