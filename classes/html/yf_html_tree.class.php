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
		$this->_css($extra);
		$this->_js($extra);

		$items = implode(PHP_EOL, (array)$this->_tree_items($data, $extra));
		$r = array(
			'form_action'	=> $extra['form_action'] ?: './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id'],
			'add_link'		=> $extra['add_link'] ?: './?object='.$_GET['object'].'&action=add_item&id='.$_GET['id'],
			'back_link'		=> $extra['back_link'] ?: './?object='.$_GET['object'].'&action=show_items&id='.$_GET['id'],
		);
#		$btn_save	= '<button type="submit" class="btn btn-primary btn-mini btn-xs"><i class="icon-large icon-save"></i> '.t('Save').'</button>';
#		$btn_back	= $r['back_link'] ? '<a href="'.$r['back_link'].'" class="btn btn-mini btn-xs"><i class="icon-large icon-backward"></i> '.t('Go Back').'</a>' : '';
#		$btn_add	= $r['add_link'] ? '<a href="'.$r['add_link'].'" class="btn btn-mini btn-xs ajax_add"><i class="icon-large icon-plus-sign"></i> '.t('Add').'</a>' : '';
		$btn_expand = !$extra['no_expand'] ? '<a href="javascript:void(0);" class="btn btn-mini btn-xs draggable-menu-expand-all"><i class="icon-large icon-expand-alt fa-expand"></i> '.t('Expand').'</a>' : '';
		return '<form action="'.$r['form_action'].'" method="post" class="draggable_form'.($extra['form_class'] ? ' '.$extra['form_class'] : '').'">
				<div class="controls">'
					. $btn_save
					. $btn_back
					. $btn_add
					. $btn_expand
				.'</div>
				<ul class="draggable_menu">'.$items.'</ul>
			</form>';
	}

	/**
	* This pure-php method needed to greatly speedup page rendering time for 100+ items
	*/
	function _tree_items(&$data, $extra = array()) {
		if ($extra['show_controls']) {
			$r = array(
				'edit_link'		=> $extra['edit_link'] ?: './?object='.$_GET['object'].'&action=edit_item&id=%d',
				'delete_link'	=> $extra['delete_link'] ?: './?object='.$_GET['object'].'&action=delete_item&id=%d',
				'clone_link'	=> $extra['clone_link'] ?: './?object='.$_GET['object'].'&action=clone_item&id=%d',
			);
			$form_controls = form_item($r)->tbl_link_edit()
				. form_item($r)->tbl_link_delete()
				. form_item($r)->tbl_link_clone();
		}
		$opened_levels = isset($extra['opened_levels']) ? $extra['opened_levels'] : 1;
		$is_draggable = isset($extra['draggable']) ? $extra['draggable'] : true;
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
			$expander_icon = '';
			if ($has_children) {
				$expander_icon = $item['level'] >= $opened_levels ? 'icon-caret-right' : 'icon-caret-down';
			}
			$content = ($item['icon_class'] ? '<i class="'.$item['icon_class'].'"></i>' : ''). $item['name'];
			if ($item['link']) {
				$content = '<a href="'.$item['link'].'">'.$content. '</a>';
			}
			$controls = $extra['show_controls'] ? str_replace('%d', $id, $form_controls) : '';
			$badge = $item['badge'] ? ' <sup class="badge badge-'.($item['class_badge'] ?: 'info').'">'.$item['badge'].'</sup>' : ''; 
			$items[] = '
				<li id="item_'.$id.'"'.(!$is_draggable ? ' class="not_draggable"' : '').'>
					<div class="dropzone"></div>
					<dl>
						<a href="'.$item['link'].'" class="expander"><i class="icon '.$expander_icon.'"></i></a>&nbsp;'
						.$content
						.$badge
						.($is_draggable ? '&nbsp;<span class="move" title="'.t('Move').'"><i class="icon icon-move"></i></span>' : '')
						.($controls ? '<div style="float:right;display:none;" class="controls_over">'.$controls.'</div>' : '')
					.'</dl>'
				;
			if ($has_children) {
				$ul_opened = true;
				$items[] = PHP_EOL. '<ul class="'.($item['level'] >= $opened_levels ? 'closed' : '').'">'. PHP_EOL;
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
		return $items;
	}

	/**
	*/
	function _css($extra = array()) {
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
	function _js($extra = array()) {
		js(
'$(function(){
	$(".draggable_form").on("submit", function(){
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
	$("li",".draggable_menu").not(".not_draggable").draggable({
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
