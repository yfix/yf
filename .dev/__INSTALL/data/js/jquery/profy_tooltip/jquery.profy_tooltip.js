/*
 * jQuery YF Tooltip plugin 1.0.3
 * based on jQuery Tooltip plugin 1.1
 *
 * http://bassistance.de/jquery-plugins/jquery-plugin-tooltip/
 *
 * Copyright (c) 2006 Jörn Zaefferer, Stefan Petre
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 * ADDED: ability to get content by AJAX query using cache
 *
 * @author Jörn Zaefferer (http://bassistance.de)
 * @author Yuriy Vysotskiy (http://yfix.dev)
 */
;(function($) {

	// the tooltip element
	var helper = {},
		// the current tooltipped element
		current,
		// the title of the current element, used for restoring
		title,
		// timeout id for delayed tooltips
		tID,
		// timeout id for delayed hide
		hide_id,
		// IE 5.5 or 6
		IE = $.browser.msie && /MSIE\s(5\.5|6\.)/.test(navigator.userAgent),
		// flag for mouse tracking
		track = false;

	// Cache storage for AJAX responses
	var _ajax_cache = {};
	// Put here any html you wan't to appear when waiting for the AJAX response
	var loading_html = "loading...";

	$.yf_tooltip = {
		blocked: false,
		defaults: {
			ajax_id: false,
			ajax_url: "",
			ajax_prefix: "",
			delay: 200, // Delay before display
			hide_delay: 2000, // Delay for auto-hide (set to 0 to disable)
			showURL: true,
			extraClass: "",
			top: 15,
			left: 15
		},
		block: function() {
			$.yf_tooltip.blocked = !$.yf_tooltip.blocked;
		}
	};

	$.fn.extend({
		yf_tooltip: function(settings) {
			settings = $.extend({}, $.yf_tooltip.defaults, settings);
			createHelper();
			return this.each(function() {
					this.tSettings = settings;
					if (this.tSettings.ajax_id && typeof this.tSettings.ajax_id == "string") {
						this.tSettings.ajax_num_id = $(this).attr(this.tSettings.ajax_id);
						$(this).attr("yf:tooltip_url", this.tSettings.ajax_url);
						$(this).attr("yf:ajax_num_id", this.tSettings.ajax_num_id);
						$(this).attr("yf:ajax_prefix", this.tSettings.ajax_prefix);
					}
					// copy tooltip into its own expando and remove the title
					this.tooltipText = this.title;
					$(this).removeAttr("title");
					// also remove alt attribute to prevent default tooltip in IE
					this.alt = "";
				})
				.hover(save, settings.ajax_num_id ? hide2 : hide)
				.click(hide);
		},
		fixPNG: IE ? function() {
			return this.each(function () {
				var image = $(this).css('backgroundImage');
				if (image.match(/^url\(["']?(.*\.png)["']?\)$/i)) {
					image = RegExp.$1;
					$(this).css({
						'backgroundImage': 'none',
						'filter': "progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled=true, sizingMethod=crop, src='" + image + "')"
					}).each(function () {
						var position = $(this).css('position');
						if (position != 'absolute' && position != 'relative')
							$(this).css('position', 'relative');
					});
				}
			});
		} : function() { return this; },
		unfixPNG: IE ? function() {
			return this.each(function () {
				$(this).css({'filter': '', backgroundImage: ''});
			});
		} : function() { return this; },
		hideWhenEmpty: function() {
			return this.each(function() {
				$(this)[ $(this).html() ? "show" : "hide" ]();
			});
		},
		url: function() {
			return this.attr('href') || this.attr('src');
		}
	});

	function createHelper() {
		// there can be only one tooltip helper
		if( helper.parent )
			return;
		// create the helper, h3 for title, div for url
		helper.parent = $('<div id="tooltip"><h3>test</h3><div class="body"></div><div class="url"></div></div>')
			// add to document
			.appendTo("body")
			// hide it at first
			.hide();

		// apply bgiframe if available
		if ( $.fn.bgiframe )
			helper.parent.bgiframe();

		// save references to title and url elements
		helper.title = $('h3', helper.parent);
		helper.body = $('div.body', helper.parent);
		helper.url = $('div.url', helper.parent);
	}

	// main event handler to start showing tooltips
	function handle(event) {
		var _cur_settings	= this.tSettings;
		// show helper, either with timeout or on instant
		if( this.tSettings.delay )
			tID = setTimeout(function(){show(_cur_settings)}, this.tSettings.delay);
		else
			show(_cur_settings);

		// Set delay hide timeout
		if (this.tSettings.hide_delay) {
			hide_id = setTimeout(delay_hide, _cur_settings.hide_delay);
		}
		
		// if selected, update the helper position when the mouse moves
		track = !!this.tSettings.track;
		$('body').bind('mousemove', update);
			
		// update at least once
		update(event);
	}

	// save elements title before the tooltip is displayed
	function save() {
		// if this is the current source, or it has no title (occurs with click event), stop
		if ( $.yf_tooltip.blocked || this == current || (!this.tooltipText && !this.tSettings.ajax_num_id))
			return;
		// save current
		current = this;
		title = this.tooltipText;

		if (hide_id) {
			clearTimeout(hide_id);
		}
		if ($(helper.parent).is(":visible")) {
			helper.parent.hide();
		}

		if ( this.tSettings.bodyHandler ) {
			helper.title.hide();
			helper.body.html( this.tSettings.bodyHandler.call(this) ).show();
		} else if ( this.tSettings.showBody ) {
			var parts = title.split(this.tSettings.showBody);
			helper.title.html(parts.shift()).show();
			helper.body.empty();
			for(var i = 0, part; part = parts[i]; i++) {
				if(i > 0)
					helper.body.append("<br/>");
				helper.body.append(part);
			}
			helper.body.hideWhenEmpty();
		} else {
			helper.title.html(title).show();
			helper.body.hide();
		}
		
		// if element has href or src, add and show it, otherwise hide it
		if( this.tSettings.showURL && $(this).url() )
			helper.url.html( $(this).url().replace('http://', '') ).show();
		else 
			helper.url.hide();
		
		// add an optional class for this tip
		helper.parent.addClass(this.tSettings.extraClass);

		// fix PNG background for IE
		if (this.tSettings.fixPNG )
			helper.parent.fixPNG();
			
		handle.apply(this, arguments);
	}

	// delete timeout and show helper
	function show(cur_settings) {
		tID = null;

		var _id		= $(current).attr("yf:ajax_num_id");
		var _prefix = $(current).attr("yf:ajax_prefix");
		if (typeof _prefix != "undefined") {
			_id	= _prefix + _id;
		}
		var _url	= $(current).attr("yf:tooltip_url");
		// Default show method
		if (!_id) {
			helper.parent.show();
			update();
			return;
		}
		// AJAX loading text
		helper.title.html(loading_html);
		// Try to get from cache first
		var _cache_name = String(_url + "#" + _id);
		if (_ajax_cache[_cache_name] != null) {
			helper.body.html(_ajax_cache[_cache_name]).show();
			helper.title.hide();
			helper.parent.show(/*"fast"*/);
			update();
			try {
				_debug_catch(helper.body);
			} catch (e) {}
		} else {
			if (hide_id) {
				clearTimeout(hide_id);
			}
			helper.parent.show(/*"fast"*/);
			$.post(_url, {"id": _id}, function(data){
				_ajax_cache[_cache_name] = data;
				helper.title.hide();
				helper.body.html(data).show();
				update();
				if (cur_settings.hide_delay) {
					hide_id = setTimeout(delay_hide, cur_settings.hide_delay);
				}
				try {
					_debug_catch(helper.body);
				} catch (e) {}
			});
		}
	}

	/**
	* callback for mousemove
	* updates the helper position
	* removes itself when no current element
	*/
	function update(event)	{
		if($.yf_tooltip.blocked)
			return;
		
		// stop updating when tracking is disabled and the tooltip is visible
		if ( !track && helper.parent.is(":visible")) {
			$('body').unbind('mousemove', update)
		}
		
		// if no current element is available, remove this listener
		if( current == null ) {
			$('body').unbind('mousemove', update);
			return;	
		}
		var left = helper.parent[0].offsetLeft;
		var top = helper.parent[0].offsetTop;
		if(event) {
			// position the helper 15 pixel to bottom right, starting from mouse position
			left = event.pageX + current.tSettings.left;
			top = event.pageY + current.tSettings.top;
			helper.parent.css({
				left: left + 'px',
				top: top + 'px'
			});
		}
		var v = viewport(),
			h = helper.parent[0];
		// check horizontal position
		if(v.x + v.cx < h.offsetLeft + h.offsetWidth) {
			left -= h.offsetWidth + 20 + current.tSettings.left;
			helper.parent.css({left: left + 'px'});
		}
		// check vertical position
		if(v.y + v.cy < h.offsetTop + h.offsetHeight) {
			top -= h.offsetHeight + 20 + current.tSettings.top;
			// Fix for possible negative vertical offset
			if (top < 0) {
				top *= -1;
			}
			helper.parent.css({top: top + 'px'});
		}
	}
	
	function viewport() {
		return {
			x: $(window).scrollLeft(),
			y: $(window).scrollTop(),
			cx: $(window).width(),
			cy: $(window).height()
		};
	}
	
	// hide helper and restore added classes and the title
	function hide(event, cur_settings) {
		if($.yf_tooltip.blocked) {
			return;
		}
		// clear timeout if possible
		if (tID) {
			clearTimeout(tID);
		}
		// clear hide timeout if possible
		if (hide_id) {
			clearTimeout(hide_id);
		}
		// no more current element
		current = null;
		
		helper.parent.hide();

		var s = this.tSettings || cur_settings;
		if (s) {
			helper.parent.removeClass( s.extraClass );
			if( s.fixPNG ) {
				helper.parent.unfixPNG();
			}
			// Fix for the yf class
			helper.parent.removeClass("yf");
		}
	}
	
	// Hide helper only if mouse if out from it
	function hide2(event) {
		var _cur_settings = this.tSettings;
		$(helper.parent[0]).hover(function(e){
			$.data(helper.parent, "mouse_is_over", 1);
		}, function(e){
			$.data(helper.parent, "mouse_is_over", 0);
			helper.parent.removeClass(_cur_settings.extraClass);
			hide(event, _cur_settings);
		});
	}
	
	// Hide helper with delay
	function delay_hide(event) {
		if (!helper.parent.is(":visible")) {
			return false;
		}
		if ($.data(helper.parent, "mouse_is_over") != 1) {
			hide(event);
		}
	}

	// Add force hide tooltip on click on page (except when mouse is over the tooltip)
	$(document).click(function(e){
		if (!$("#tooltip").is(":visible")) {
			return;
		}
		if (!$(e.target).parents().filter("#tooltip").length) {
			$("#tooltip").hide().removeClass("yf");
		}
	})
	
})(jQuery);