jQuery.noConflict();
(function($) {
        $(document).ready(function(){
		fixHeight.start();
	});
        
        var fixHeight = {
                elements : {}
		,ie6 : ($.browser.msie && $.browser.version.substr(0,1) < 7)
                ,start : function() {
                        this.catchItems();
                        this.forceElementHeight();
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
                                        if(columnItem.el.length > 1){
                                                var height = columnItem['elHeight'].sort(fixHeight.sortNumber)[0],
							heightCss = fixHeight.ie6 ? {'height' : height + 'px'} : {'min-height' : height + 'px'};
							
                                                $.each(columnItem['el'], function(elementIndex, element){
                                                        $(element).css(heightCss);
                                                });
                                        }
                                });
                        });
                }
                
                ,sortNumber : function (a, b) {
                       return b -a; 
                }
        };
}(jQuery));