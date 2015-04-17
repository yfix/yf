<?php

return function() {

return array('versions' => array('master' => array(
	'css' => '
		.popover.ajax-user-info { min-width: 350px; min-height: 300px; border: 5px solid black; }
		.popover.ajax-user-info .popover-content { max-height: 500px; width: auto; overflow-x: visible; overflow-y: auto; }
	',
	// Ideas from here: http://stackoverflow.com/a/28731847/2521354
	'jquery' => '
			var originalLeave = $.fn.popover.Constructor.prototype.leave;
			$.fn.popover.Constructor.prototype.leave = function(obj) {
				var self = obj instanceof this.constructor ? obj : $(obj.currentTarget)[this.type](this.getDelegateOptions()).data("bs." + this.type);
				originalLeave.call(this, obj);
				if (obj.currentTarget) {
					self.$tip.one("mouseenter", function() {
						clearTimeout(self.timeout);
						self.$tip.one("mouseleave", function() {
							$.fn.popover.Constructor.prototype.leave.call(self, self);
						});
					})
				}
			};
			$("body").popover({
				animation: false,
				delay: { "show": 0, "hide": 300 },
				container: "body",
				html: true,
				selector: \'a[href*="object=members&action=edit&id="]:not(.no-popover),[data-ajax-user-info]\',
				placement: "auto",
				trigger: "hover",
				template: \'<div class="popover ajax-user-info" role="tooltip"><div class="arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div></div>\',
				title: function() {
					var uid = yf_get_uid_from_href($(this).attr("href"));
					if (uid) {
						return "User #" + uid;
					}
					return false;
				},
				content: function() {
					var _this = $(this)
					var uid = yf_get_uid_from_href(_this.attr("href"));
					if (uid) {
						return yf_ajax_user_info(uid, _this);
					}
					return false;
				}
			})

			function yf_get_uid_from_href(href) {
				var m = href.match(/object=members&action=edit&id=([0-9]+)/);
				return m !== null ? m[1] : 0;
			}

			var yf_cache_ajax_user_info = { };
			function yf_ajax_user_info(uid, _this) {
				var url = "'.url('/members/ajax/%uid').'";
				if (typeof uid == "undefined" || !uid) {
					return false;
				}
				var _popover_elm = _this.data("bs.popover").tip()

				if (typeof yf_cache_ajax_user_info[uid] === "string") {
					return yf_cache_ajax_user_info[uid];
				}
				yf_cache_ajax_user_info[uid] = \'<i class="fa fa-2x fa-spinner fa-spin"></i>\';

				$.ajax({
					type: "POST",
					url: url.replace("%uid", uid),
					dataType: "html",
					cache: false, // Prevent content to be cached by browser
					success: function (html) {
						yf_cache_ajax_user_info[uid] = html;
						_popover_elm.find(".popover-content").html(html)
					}
				});
				return yf_cache_ajax_user_info[uid];
			}
		'
	)),
	'require' => array(
		'asset' => array(
			'jquery',
			'bootstrap-theme',
		),
	),
);

};