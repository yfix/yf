<?php

/**
*/
class yf_html_tree {

	/**
	*/
	function _init() {
		$this->_parent = _class('html');
	}

	/**
	*/
	function tree($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		if ($data) {
			$data = $this->_parent->_recursive_sort_items($data);
		}
#return _var_dump($data);
/*
		$keys = array_keys($data);
		$keys_counter = array_flip($keys);
		$items = array();
		$ul_opened = false;
		foreach ((array)$data as $id => $item) {
			$next_item = $data[ $keys[$keys_counter[$id] + 1] ];
			$has_children = false;
			$close_li = 1;
			$close_ul = 0;
			if ($next_item) {
				if ($next_item['level'] > $item['level']) {
					$has_children = true;
				}
				$close_li = $item['level'] - $next_item['level'] + 1;
				if ($close_li < 0) {
					$close_li = 0;
				}
			}
			$items[] = '
				<li class="dropdown">
					<a href="'.$item['link'].'" class="dropdown-toggle">'. $item['name']. ($has_children ? ' <b class="caret"></b>' : ''). '</a>'
				;
			if ($has_children) {
				$ul_opened = true;
				$items[] = PHP_EOL. '<ul class="dropdown-menu sub-menu">'. PHP_EOL;
			} elseif ($close_li) {
				if ($ul_opened && !$has_children && $item['level'] != $next_item['level']) {
					$ul_opened = false;
					$close_ul = 1;
				}
				$tmp = str_repeat(PHP_EOL. ($close_ul ? '</li></ul>' : '</li>'). PHP_EOL, $close_li);
				if ($close_li > 1 && $close_ul) {
					$tmp = substr($tmp, 0, -strlen('</ul>'.PHP_EOL)). PHP_EOL;
				}
				$items[] = $tmp;
			}
		}
		return '<div class="navbar navbar-default'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">
					<div class="navbar-inner navbar-header">
						<ul class="nav navbar-nav">'.implode(PHP_EOL, (array)$items).'</ul>
					</div>
				</div>';
*/
		return $this->_drag_tpl_main($data);
	}

	/**
	* This pure-php method needed to greatly speedup page rendering time for 100+ items
	*/
	function _drag_tpl_main(&$data) {
		$this->_css();
		$this->_js();
		$r = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id'],
			'add_link'		=> './?object='.$_GET['object'].'&action=add_item&id='.$_GET['id'],
			'back_link'		=> './?object='.$_GET['object'].'&action=show_items&id='.$_GET['id'],
		);
		return '<form action="'.$r['form_action'].'" method="post" id="draggable_form">
				<div class="controls">
<!--
					<button type="submit" class="btn btn-primary btn-mini btn-xs"><i class="icon-save"></i> '.t('Save').'</button>
					<a href="'.$r['back_link'].'" class="btn btn-mini btn-xs"><i class="icon-backward"></i> '.t('Go Back').'</a>
					<a href="'.$r['add_link'].'" class="btn btn-mini btn-xs ajax_add"><i class="icon-plus-sign"></i> '.t('Add').'</a>
-->
					<a href="javascript:void(0);" class="btn btn-mini btn-xs" id="draggable-menu-expand-all"><i class="icon-expand-alt fa-expand"></i> '.t('Expand').'</a>
				</div>
				<ul class="draggable_menu">'.implode(PHP_EOL, (array)$this->_drag_tpl_items($data)).'</ul>
			</form>';
	}

	/**
	* This pure-php method needed to greatly speedup page rendering time for 100+ items
	*/
	function _drag_tpl_items(&$data) {
		$body = array();

		$form = _class('form2');
		$replace = array(
			'edit_link'		=> './?object='.$_GET['object'].'&action=edit_item&id=%d',
			'delete_link'	=> './?object='.$_GET['object'].'&action=delete_item&id=%d',
			'clone_link'	=> './?object='.$_GET['object'].'&action=clone_item&id=%d',
		);
		$form_controls =
			$form->tpl_row('tbl_link_edit', $replace, '', '', '')
			.$form->tpl_row('tbl_link_delete', $replace, '', '', '')
			.$form->tpl_row('tbl_link_clone', $replace, '', '', '')
		;
		$keys = array_keys($data);
		$keys_counter = array_flip($keys);
#		$items = array();
#		$ul_opened = false;
		foreach ((array)$data as $id => $item) {
			if (!$id) {
				continue;
			}
			$next_item = $data[ $keys[$keys_counter[$id] + 1] ];
			$has_children = false;
			if ($next_item) {
				if ($next_item['level'] > $item['level']) {
					$has_children = true;
				}
#				$close_li = $item['level'] - $next_item['level'] + 1;
#				if ($close_li < 0) {
#					$close_li = 0;
#				}
			}
			$expander_icon = '';
			if ($has_children) {
				$expander_icon = $item['level'] >= 1 ? 'icon-caret-right' : 'icon-caret-down';
			}
			$content = ($item['icon_class'] ? '<i class="'.$item['icon_class'].'"></i>' : ''). $item['name'];
			if ($item['link']) {
				$content = '<a href="'.$item['link'].'">'.$content. '</a>';
			}
			if ($has_children) {
				$footer = '<ul class="'.($item['level'] >= 1 ? 'closed' : '').'">';
			} else {
				$footer = '</li>'.str_repeat('</ul>'.PHP_EOL, $item['next_level_diff']);
			}
			$body[] = '
				<li id="item_'.$id.'">
					<div class="dropzone"></div>
					<dl>
						<a href="#" class="expander"><i class="icon '.$expander_icon.'"></i></a>&nbsp;'
						.$content
						.'&nbsp;<span class="move" title="'.t('Move').'"><i class="icon icon-move"></i></span>
						<div style="float:right;display:none;" class="controls_over">'
						.str_replace('%d', $id, $form_controls)
						.'</div>
					</dl>'
				.$footer
			;
		}
		return $body;
	}

	/**
	*/
	function _css() {
		css(
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
			.draggable_menu .icon-move { cursor: move; }'
		);
	}

	/**
	*/
	function _js() {
		js(
'$(function(){
	$("#draggable_form").on("submit", function(){
		var _form = $(this);
		var items = { };
		var i = 0;
		$("li", ".draggable_menu").each(function(){
			items[++i] = {
				"item_id" : +$(this).attr("id").substring("item_".length),
				"parent_id" : +($(this).closest("ul").not(".draggable_menu").parent("li").attr("id") || "").substring("item_".length)
			}
		})
		$.post(_form.attr("action"), {"items" : items}, function(data){
//			window.location.reload();
		})
		return false;
	})

	var caret_class_opened = "icon-caret-down";
	var caret_class_closed = "icon-caret-right";

	$("#draggable-menu-expand-all").on("click", function() {
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
});

//init functions
$(function() {
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
	$("li",".draggable_menu").draggable({
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

	$(".controls_over", ".draggable_menu").hide();

	$(".draggable_menu").on("mouseover", "dl", function() {
		$(this).find(".controls_over").show();
	});
	$(".draggable_menu").on("mouseout", "dl", function() {
		$(this).find(".controls_over").hide();
	});
});

var draggable_history = {
	stack: new Array(),
	temp: null,
	//takes an element and saves it"s position in the sitemap.
	//note: doesn"t commit the save until commit() is called!
	//this is because we might decide to cancel the move
	save_state: function(item) {
		draggable_history.temp = { item: $(item), itemParent: $(item).parent(), itemAfter: $(item).prev() };
	},
	commit: function() {
		if (draggable_history.temp != null) draggable_history.stack.push(draggable_history.temp);
	},
	//restores the state of the last moved item.
	restore_state: function() {
		var h = draggable_history.stack.pop();
		if (h == null) return;
		if (h.itemAfter.length > 0) {
			h.itemAfter.after(h.item);
		}
		else {
			h.itemParent.prepend(h.item);
		}
		//checks the classes on the lists
		$(".draggable_menu li.opened").not(":has(li)").removeClass("opened");
		$(".draggable_menu li:has(ul li):not(.closed)").addClass("opened");
	}
}'
		);
	}
}
