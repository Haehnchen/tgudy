jQuery.noConflict();
(function($) {
        $(document).ready(function(){
		fixColumnHeight.start();
	});
        
        var fixColumnHeight = {
                elements : {}
                ,start : function() {
			var self = fixColumnHeight;
			
                        self.catchItems();
                        self.forceElementHeight();
                }
                
                ,catchItems : function () {
                        $('.multicolumnContainerfixColumnHeight').each(function(containerIndex, container){
                                fixColumnHeight.elements[containerIndex] = {};
				fixColumnHeight.elements[containerIndex]['columns'] = [];
				fixColumnHeight.elements[containerIndex]['columnHeights'] = [];
				
                                $(container).find('div.columnItems').each(function(columnIndex, column){
					var $column = $(column);
					fixColumnHeight.elements[containerIndex]['columns'].push($column);
					fixColumnHeight.elements[containerIndex]['columnHeights'].push($column.height());
                                });
                        });                       
                }
                
                ,forceElementHeight : function () {
                        $.each(this.elements, function(containerIndex, container){
				var columnHeight = container['columnHeights'].sort(fixColumnHeight.sortNumber)[0];
				$(container['columns']).each(function(){
					var 	$column = $(this),
						$li = $column.parent(),
						$container = $column.parents('ul:first'),
						$css3container = $column.prev();
					
					$column.css('min-height', columnHeight + 'px');

					// flush container
					if($css3container.length) {
						$column.css({
							'visibility' : 'hidden'
						});
							// force dom re rendering for ie quirks
						$container.before($column);
						$li.append($column);
						$column.css('visibility', 'visible');
					}
				});
                        });
                }
                
                ,sortNumber : function (a, b) {
                       return b -a; 
                }
        };
	
	$.fn.multicolumnFixColumnHeight = function(method) {
		if (fixColumnHeight[method]) {
			return fixColumnHeight[method].apply(this, Array.prototype.slice.call(arguments, 1));
		} else {
		      $.error( 'Method ' +  method + ' does not exist on jQuery.fixColumnHeight' );
		      return false;
		}    			
	};
}(jQuery));