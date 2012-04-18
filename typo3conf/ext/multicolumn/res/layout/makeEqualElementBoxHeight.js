jQuery.noConflict();
(function($) {
        $(document).ready(function(){
		fixHeight.start();
	});
        
        var fixHeight = {
                elements : {}
		,ie6 : ($.browser.msie && $.browser.version.substr(0,1) < 7)
                ,start : function() {
			var self = fixHeight;
			
                        fixHeight.catchItems();
                        fixHeight.forceElementHeight();
                }
                
                ,catchItems : function () {
                        $('.multicolumnContainerFixHeight').each(function(containerIndex, container){
                                fixHeight.elements[containerIndex] = {};
                                $(container).find('.column').each(function(columnIndex, column){
                                        $(column).find('.columnItem').each(function(columnItemIndex, columnItem){
                                                var $el = $(columnItem);
                                                if(typeof(fixHeight.elements[containerIndex][columnItemIndex]) === 'undefined'){
                                                        fixHeight.elements[containerIndex][columnItemIndex] = {};
                                                        fixHeight.elements[containerIndex][columnItemIndex]['el'] = [];
                                                        fixHeight.elements[containerIndex][columnItemIndex]['elHeight'] = [];
                                                }
                                                fixHeight.elements[containerIndex][columnItemIndex]['el'].push($el);
                                                fixHeight.elements[containerIndex][columnItemIndex]['elHeight'].push($el.innerHeight());
                                        });
                                    
                                });
                        });                       
                }
                
                ,forceElementHeight : function () {
                        $.each(this.elements, function(containerIndex, container){
                                $.each(container, function(columnItemIndex, columnItem){
                                        if(columnItem.el.length > 0){
                                                var height = columnItem['elHeight'].sort(fixHeight.sortNumber)[0];
							
                                                $.each(columnItem['el'], function(elementIndex, element){
							var 	$el = $(element),
								$coEl = $el.find('div:first'),
								$css3 = $coEl.prev('css3-container'),
								$coElInner = $coEl.find('div:first'),
								padding = parseInt($coElInner.css('padding-bottom')) + parseInt($coElInner.css('padding-top')),
								heightCss = {'min-height' :  height - padding + 'px'};
								
							$el.css(heightCss);
							$coEl.css(heightCss);
							$coElInner.css(heightCss);
								// flush container
							if($css3.length) $coEl.hide().show();
                                                });
                                        }
                                });
                        });
                }
                
                ,sortNumber : function (a, b) {
                       return b -a; 
                }
        };
	
	$.fn.multicolumnFixHeight = function(method) {
		if (fixHeight[method]) {
			return fixHeight[method].apply(this, Array.prototype.slice.call(arguments, 1));
		} else {
		      $.error( 'Method ' +  method + ' does not exist on jQuery.fixHeight' );
		      return false;
		}    			
	};
	
}(jQuery));