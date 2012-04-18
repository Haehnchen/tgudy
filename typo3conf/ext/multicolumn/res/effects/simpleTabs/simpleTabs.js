jQuery.noConflict();
jQuery(document).ready(function($){	
	var effectBox = {
		options : {}
		,$el : []
		,id : 0
		,$tabItems : []
		,maxHeight : []
		,$navigationContainer : []
		,$simpleTabsContainer : []
		,start : function () {
			var self = this;
			self.$tabItems = [];
			
			self.$simpleTabs = $('div.simpleTabs');
			self.$simpleTabs.each(function(index, element){
				self.id = element.id.split('_')[1];
				
				self.options  = window['mullticolumnEffectBox_' + self.id] ? window['mullticolumnEffectBox_' + self.id] : {};
				self.$el = $(this);
				self.$simpleTabsContainer.push(self.$el.find('.simpleTabsContainer')),
				self.$tabItems.push(self.$el.find('li.tabItem'));

				if(self.$el.length) {
					self.buildNavigation(index);
					if(self.options['fixHeight']) self.setContainerHeight(index);
				}
				
				self.$el.removeClass('effectBoxLoading');
			});
		}
		
		,buildNavigation : function (tabIndex) {
			var	$navigationAppend = this.$el.find('.simpleTabNavigationContainer'),
				self = this,
				$navigationContainer = $('<ul class="simpleTabNavigation clearfix"></ul>');

			self.$navigationContainer.push($navigationContainer);

			self.$tabItems[tabIndex].each(function(index){
				var $el = $(this),
					$container = $el.parent('ul'),
					$navigationItemContent = $el.find('.simpleTabNavigationItemContent'),
					navigationLabel = $navigationItemContent.text(),
					navigationItemId = 'tab-' + (tabIndex + 1) + '-' + (index + 1),
					
					$a = $('<a id="' + navigationItemId + '" href="#' + navigationItemId + '">' + navigationLabel + '</a>'),
					$item = $('<li class="simpleTabNavigationItem simpleTabNavigationItem' + index + '"></li>');
				
				$item.append($a);
				$navigationContainer.append($item);
				$navigationItemContent.remove();
				self.maxHeight.push($el.height());
				
				var show = function () {
					self.hideAll(tabIndex);
					$el.show();
					$item.addClass('tabItemAct');
					
						// implment hook style
					if($.isFunction($.fn.multicolumnFixHeight)) {
						$.fn.multicolumnFixHeight('start');
					}
					
					if($.isFunction($.fn.multicolumnFixColumnHeight)) {
						$.fn.multicolumnFixColumnHeight('start');
					}
				};
				
				$a.click(function(event){
					event.preventDefault();
					show();
				});

					// add target links
				$container.find('a[href="#' + navigationItemId + '"]').each(function(){
					$(this).click(function(){
						show()
					});
				});
				
				if(index) {
					$el.hide();
				} else {
					$item.addClass('tabItemAct');
				}
			});

			$navigationAppend.append($navigationContainer);
		}
		
		,setContainerHeight : function (tabIndex) {
			var self = this;
				height = self.maxHeight.sort(self.sortNumber)[0];
				
			self.$simpleTabsContainer[tabIndex].css('height', height + 'px');
		}
		
		,hideAll : function (tabIndex) {
			var self = this;
			this.$tabItems[tabIndex].each(function(index){
				var $el = $(this);
				$el.hide();
				
				self.$navigationContainer[tabIndex].find('li.simpleTabNavigationItem' + index).removeClass('tabItemAct');
			});
		}
                ,sortNumber : function (a, b) {
                       return b -a; 
                }
	};
	
	effectBox.start();

		// hash bang
	if(window.location.hash) {
		var $href = $(window.location.hash);
		if($href) {
			$href.trigger('click');
		}
	}
});