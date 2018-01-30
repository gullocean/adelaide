// @jdalton
// https://gist.github.com/903131
// 1) won't restrict viewport if JS is disabled
// 2) uses capture phase
// 3) assumes last viewport meta is the one to edit (in case for some odd reason there is more than one)
// 4) feature inference (no sniffs, behavior should be ignored on other environments)
// 5) removes event handler after fired
!function(doc) {
  var addEvent = 'addEventListener',
      type = 'gesturestart',
      qsa = 'querySelectorAll',
      scales = [1, 1],
      meta = qsa in doc ? doc[qsa]('meta[name=viewport]') : [];

  function fix() {
    meta.content = 'width=device-width,minimum-scale=' + scales[0] + ',maximum-scale=' + scales[1];
    doc.removeEventListener(type, fix, !0);
  }
  if ((meta = meta[meta.length - 1]) && addEvent in doc) {
    fix();
    scales = [.25, 1.6];
    doc[addEvent](type, fix, !0);
  }
}(document);

// back top

$('.backTop').click(function(){
	$("html, body").animate({ scrollTop: 0 }, 'slow');
	return false;
});

// foundation pop modal

$('a.pop').click(function(event) {
  event.preventDefault();
  var $div = $('<div>').addClass('reveal-modal').appendTo('body'),
    $this = $(this);
  $.get($this.attr('href'), function(data) {
    return $div.empty().html(data).append('<a class="close-reveal-modal">&#215;</a>').reveal();
  });
});
$('.help a').click(function(event) {
  event.preventDefault();
  var $div = $('<div>').addClass('reveal-modal').appendTo('body'),
    $this = $(this);
  $.get($this.attr('href'), function(data) {
    return $div.empty().html(data).append('<a class="close-reveal-modal">&#215;</a>').reveal();
  });
});

// layer slider

jQuery("#layerslider").layerSlider({
	responsive: false,
	responsiveUnder: 1000,
	layersContainer: 1000,
	skin: 'v5',
	randomSlideshow: false,
	hoverPrevNext: true, // show outer arrows on hover
	navPrevNext: true, // arrows on either side
	navStartStop: false, // start stop buttons
	navButtons: true, // bullets
	showCircleTimer: false,
	autoPlayVideos: false,
	thumbnailNavigation:"disabled",
	skinsPath: 'includes/lib/layerslider/skins/'
});