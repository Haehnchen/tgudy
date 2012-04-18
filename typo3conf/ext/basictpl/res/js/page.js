jQuery.noConflict();

(function($) {
	$(document).ready(function(){

		// toogle box
		$(".toggle-box .csc-header").click(function(){
			$(this).parent().toggleClass('toggle-box-open');
			return false;
		});
    
		// menu
		$(".menuparent > a").click(function(){
			return false;
		});    
		
		var cycle_fx = $('#slider').attr('class');
		if (typeof cycle_fx == 'undefined') {
			cycle_fx = 'horizontal';
		}
		
		
		// Fancybox Image
		$("a.lightbox").fancybox({
			'zoomSpeedIn': 500,
			'zoomSpeedOut': 500,
			'transitionIn': 'elastic',
			'titlePosition':'over',
			'overlayShow': true
		});

		// Fancybox iFrame Content
		$("a.lightbox-page").fancybox({
			'zoomSpeedIn': 500,
			'zoomSpeedOut': 500,
			'overlayShow': true,
			'width': '80%',
			'height': '80%',
			'titleShow': false,
			'autoScale' : true,
			'type': 'iframe'
		});
		
		$('a.lightbox').append('<div class="resize" />');
    
    		
		$('#slider').bxSlider({
			mode: cycle_fx,
			auto: true,
			pause: 5000
		});
		
		// same height on multicolumns
    $('.multicolumnContainerFixHeight').each(function(containerIndex, container){
      $(container).find('li.column div.csc-default').equalHeights();
  	});	  	 
	
    // same height on image rows
    $('.csc-type-image .csc-textpic-imagewrap ul').each(function(containerIndex, container) {
	            
      if ($(container).find('li.csc-textpic-image img').length > 1 ) { 

        var imgs = $(container).find('li.csc-textpic-image');
        imgs.equalHeights();
        
        $(container).find('li.csc-textpic-image img').each(function(containerIndex1, container1){
          var li = $(container1).parents('li.csc-textpic-image');
          var margin = li.height() - $(container1).height();
          //console.log(li.height());
          if (margin > 0) {
            li.prepend('<div class="before"/>').find('.before').css({width: '100%', height: (margin / 2) + "px"})
            li.append('<div class="after"/>').find('.after').css({width: '100%', height: (margin / 2) + "px"})
          }
        });	 

      }      
  	});	  	
		
    // compare tables
		$('.contenttable-71 tbody td').TableCompare({
			'+': 'tick',
			'-': 'cross'
		});
		
		
		$('.contenttable-72 tbody td').TableCompare({
			'+': 'green',
			'0': 'yellow',
			'-': 'red'
	  });
		
		
		$('.csc-layout-91, .csc-layout-92, .csc-layout-93, .csc-layout-94, .csc-layout-95, .csc-layout-96, .csc-layout-97').append('<div class="postit"/>')
	

	});
})(jQuery);


(function($) {
	$.fn.equalHeights = function(minHeight, maxHeight) {
		tallest = (minHeight) ? minHeight : 0;
		this.each(function() {
			if($(this).height() > tallest) {
				tallest = $(this).height();
			}
		});
		if((maxHeight) && tallest > maxHeight) tallest = maxHeight;
		return this.each(function() {
			$(this).height(tallest);
		});
	}
})(jQuery);

(function($) {
	$.fn.TableCompare = function(replace) {
		this.each(function() {
		    var text = $(this).html().toLowerCase();
			if(text.length == 1 && replace[text] !== undefined) {
			  $(this).html($('<div/>').addClass(replace[text] + ' icon'));
			}
		});

	}
})(jQuery);