<?php

return [
    'versions' => [
        'master' => [
            'css' => [
    '.draggable_menu { width:70%; }
	.draggable_menu li { list-style-type: none; }
	.draggable_menu dl { font-weight:bold; position: relative; display: block; margin:0; border-top: 1px solid #444; }
	.draggable_menu dl.over { background-color: #ccc !important; }
	.draggable_menu .dropzone { background-color: transparent; height: 4px; }
	.draggable_menu .dropzone.over { background-color: #aaa !important; }
	.draggable_menu .menu-container { }
	.draggable_menu ul { display: block; }
	.draggable_menu ul.closed { display: none; }
	.draggable_menu .controls_over { display: none; }
	.icon-move, .fa-arrows { cursor: move; }',
            ],
            'js' => [
<<<'END'
$(function(){
	var orig_items = { };
	var i = 0;
	$("li", ".draggable_menu").each(function(){
		orig_items[++i] = {
			"item_id" : +$(this).attr("id").substring("item_".length),
			"parent_id" : +($(this).closest("ul").not(".draggable_menu").parent("li").attr("id") || "").substring("item_".length)
		}
	})
	$("#draggable_form, .draggable_form").on("submit", function(){
		var _form = $(this);
		var items = { };
		var i = 0;
		$(".draggable_menu li", _form).each(function(){
			items[++i] = {
				"item_id" : +$(this).attr("id").substring("item_".length),
				"parent_id" : +($(this).closest("ul").not(".draggable_menu").parent("li").attr("id") || "").substring("item_".length)
			}
			if (orig_items[i] && orig_items[i]["item_id"] == items[i]["item_id"] && orig_items[i]["parent_id"] == items[i]["parent_id"]) {
				delete items[i];
			} else {
				orig_items[i] = items[i];
			}
		})
		if (Object.keys(items).length > 0) {
			if ($("input[type=hidden][name=items]", _form).length == 0) {
				_form.append("<input type=hidden name=items>")
			}
			$("input[type=hidden][name=items]", _form).val(JSON.stringify(items))
			console.log("Draggable form: saving these changed items", items)
			return true;
//			$.post(_form.attr("action"), {"items" : JSON.stringify(items)}, function(data){
//				window.location.reload();
//			})
		} else {
			console.log("Draggable form: no changes tracked, do nothing")
		}
		return false;
	})

	var caret_class_opened = "icon-caret-down fa fa-caret-down";
	var caret_class_closed = "icon-caret-right fa fa-caret-right";

	$(".draggable-menu-expand-all").on("click", function() {
		var _this = $(this);
		var _context = ".draggable_menu";
		var i = $(".expander i", _context).filter("." + caret_class_opened + ", ." + caret_class_closed);
		if (!_this.data("now_close_all")) {
			$("ul.closed", _context).removeClass("closed");
			i.removeClass(caret_class_closed).addClass(caret_class_opened);
			_this.data("now_close_all", true);
		} else {
			$("ul", _context).addClass("closed");
			i.removeClass(caret_class_opened).addClass(caret_class_closed);
			_this.data("now_close_all", false);
		}
		return false;
	});

	$(".draggable_menu").on("click", ".expander", function () {
		var ul = $(this).closest("li").find("> ul");
		var i = $(this).find("i");
		if (ul.hasClass("closed")) {
			ul.removeClass("closed")
			i.removeClass(caret_class_closed).addClass(caret_class_opened);
		} else {
			ul.addClass("closed")
			i.removeClass(caret_class_opened).addClass(caret_class_closed);
		}
		return false;
	});

	$("dl, .dropzone", ".draggable_menu").droppable({
		accept: ".draggable_menu li",
		tolerance: "pointer",
		drop: function(e, ui) {
			var _this = $(this)
			var li = _this.closest("li");
			var child = !_this.hasClass("dropzone");
			if (child && li.children("ul").length == 0) {
				li.append("<ul/>");
			}
			if (child) {
				li.addClass("opened").removeClass("closed").children("ul").append(ui.draggable);
			} else {
				li.before(ui.draggable);
			}
//			$("li.opened", ".draggable_menu").not(":has(li:not(.ui-draggable-dragging))").removeClass("opened");
			_this.filter(".over").removeClass("over");
			draggable_history.commit();
		},
		over: function() {
			$(this).filter("dl, .dropzone").addClass("over");
		},
		out: function() {
			$(this).filter("dl, .dropzone").removeClass("over");
		},
		update: function() {
			_set_dl_colors()
		}
	});
	$("li", ".draggable_menu").not(".not_draggable").draggable({
		handle: " > dl",
		opacity: .8,
		addClasses: false,
		helper: "clone",
		zIndex: 100,
		start: function(e, ui) {
			draggable_history.save_state(this);
		}
	});
	$(".sitemap_undo").click(draggable_history.restore_state);
	$(document).bind("keypress", function(e) {
		if (e.ctrlKey && (e.which == 122 || e.which == 26)) {
			draggable_history.restore_state();
		}
	});

	$(".draggable_menu").not(".no_hide_controls").find(".controls_over").hide();

	$(".draggable_menu").not(".no_hide_controls").on("mouseover", "dl", function() {
		$(this).find(".controls_over").show();
	});
	$(".draggable_menu").not(".no_hide_controls").on("mouseout", "dl", function() {
		$(this).find(".controls_over").hide();
	});
})
END
,
<<<'END'
	var draggable_history = {
		stack: new Array(),
		temp: null,
		// takes an element and saves it"s position in the sitemap.
		// note: doesn"t commit the save until commit() is called!
		// this is because we might decide to cancel the move
		save_state: function(item) {
			draggable_history.temp = { item: $(item), itemParent: $(item).parent(), itemAfter: $(item).prev() };
		},
		commit: function() {
			if (draggable_history.temp != null) draggable_history.stack.push(draggable_history.temp);
		},
		// restores the state of the last moved item.
		restore_state: function() {
			var h = draggable_history.stack.pop();
			if (h == null) return;
			if (h.itemAfter.length > 0) {
				h.itemAfter.after(h.item);
			} else {
				h.itemParent.prepend(h.item);
			}
			//checks the classes on the lists
			$(".draggable_menu li.opened").not(":has(li)").removeClass("opened");
			$(".draggable_menu li:has(ul li):not(.closed)").addClass("opened");
		}
	}
END
            ],
        ],
    ],
    'require' => [
        'asset' => 'jquery-ui',
    ],
];
