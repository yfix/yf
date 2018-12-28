<?php

return ['versions' => ['master' => [
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
		',
    ]],
    'require' => [
        'asset' => [
            'jquery',
            'bootstrap-theme',
        ],
    ],
];
