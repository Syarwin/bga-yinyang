{OVERALL_GAME_HEADER}
<div id="yinyang-container">
  <div id="fixed-width-container">
    <div id="board">
      <div id="dominos-player"></div>
      <div id="grid-container">
        <div id="yinyang-grid">
        <!-- BEGIN square -->
           <div id="square-{X}-{Y}" class="square"></div>
        <!-- END square -->
        </div>
        <div id="yinyang-overlay">
        <!-- BEGIN overlay -->
           <div id="overlay-{X}-{Y}" class="overlay"></div>
        <!-- END overlay -->
        </div>
        <div id="yinyang-mask">
          <div class='square' data-token='1' data-x='0' data-y='0'></div>
          <div class='square' data-token='0' data-x='0' data-y='1'></div>
          <div class='square' data-token='2' data-x='1' data-y='0'></div>
          <div class='square' data-token='1' data-x='1' data-y='1'></div>
        </div>
      </div>
      <div id="dominos-opponent"></div>
    </div>
    <div id="player-private-hand"></div>
  </div>
</div>
<script type="text/javascript">
var jstpl_domino = `<div class='domino' data-type='\${type}' data-location='\${location}' id='domino-\${id}'>
  <div class="domino-types">
    <div class="domino-type-destruction"></div>
    <div class="domino-type-creation"></div>
    <div class="domino-type-adaptation"></div>
  </div>
  <div class='domino-cause'>
    <div class='square' data-token='\${cause00}' data-x='0' data-y='0'></div>
    <div class='square' data-token='\${cause01}' data-x='0' data-y='1'></div>
    <div class='square' data-token='\${cause10}' data-x='1' data-y='0'></div>
    <div class='square' data-token='\${cause11}' data-x='1' data-y='1'></div>
  </div>
  <div class='domino-arrow'></div>
  <div class='domino-effect'>
    <div class='square' data-token='\${effect00}' data-x='0' data-y='0'></div>
    <div class='square' data-token='\${effect01}' data-x='0' data-y='1'></div>
    <div class='square' data-token='\${effect10}' data-x='1' data-y='0'></div>
    <div class='square' data-token='\${effect11}' data-x='1' data-y='1'></div>
  </div>
</div>`;
</script>
{OVERALL_GAME_FOOTER}
