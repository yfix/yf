<?php

return function () {
    return ['versions' => ['master' => [
    'css' => '
		.popover.ajax-user-info { min-width: 350px; min-height: 300px; border: 5px solid black; }
		.popover.ajax-user-info .popover-content { width: auto; overflow-x: visible; overflow-y: auto; }
	',
    'jquery' => '
		$("body").popover({
			animation: false,
			delay: { "show": 0, "hide": 300 },
			container: "body",
			html: true,
			selector: \'a[href*="object=members&action=edit&id="]:not(.no-popover),a[href*="/profile/"]:not(.no-popover):not([href*="ok.ru/profile/"]),[data-ajax-user-info]\',
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
			if (m === null) {
				m = href.match(/\/profile\/([0-9]+)/);
			}
			return m !== null ? m[1] : 0;
		}

		var yf_cache_ajax_user_info = { };
		function yf_ajax_user_info(uid, _this) {
			var url = "' . url('/members/ajax/%uid') . '";
			if (typeof uid == "undefined" || !uid) {
				return false;
			}
			var _popover_elm = _this.data("bs.popover").tip()
				if (typeof yf_cache_ajax_user_info[uid] === "string") {
				return yf_cache_ajax_user_info[uid];
			}
			yf_cache_ajax_user_info[uid] = \'<i class="fa fa-2x fa-spinner fa-spin"></i>\';

			$.ajaxQueue({
				type: "POST",
				url: url.replace("%uid", uid),
				dataType: "html",
				cache: false, // Prevent content to be cached by browser
				success: function (html) {
					yf_cache_ajax_user_info[uid] = html;
					_popover_elm.find(".popover-content").html(html)
				}, error: function (xhr, status, etext) {
					_popover_elm.find(".popover-content").html("<h4 class=\"text-danger\">Error loading data<br /><small>server response code:<br /> " + xhr.status + "<br />message: " + status + "</h4>")
				}
			});
			return yf_cache_ajax_user_info[uid];
		}
	', ]],
    'require' => [
        'asset' => [
            'jquery',
            'jquery-ajax-queue',
            'bootstrap-theme',
            'yf_js_popover_fix',
        ],
    ],
    'config' => [
        'no_cache' => true,
        'main_type' => 'admin',
    ],
];
};
