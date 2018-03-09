<?php

class yf_faq {

	const table = 'faq';

	private $_tpl = '
		<div class="container-block">
			<form class="form-inline" id="faq-search" style="width:50%;">
				<div class="form-group">
					<label class="sr-only" for="search"></label>
					<div class="input-group">
						<div class="input-group-addon"><i class="fa fa-search"></i></div>
						<input type="text" class="form-control" id="search" placeholder="{t(Search)}">
					</div>
				</div>
<!--				<button type="submit" class="btn btn-primary">{t(Search)}</button> -->
			</form>
			<div id="faq-items">
				{items}
			</div>
		</div>
	';

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	*/
	public function show() {
		css('
			#faq-search { padding-top: 20px; }
			#faq-items { padding-top: 20px; padding-bottom: 20px; }
			#faq-items li.li-header { list-style: none; display:none; }
			#faq-items li.li-level-0 { display: block; font-size: 15px; }
			#faq-items li.li-level-1 { padding-top: 10px; font-size: 13px; }
			span.highlight { background-color: #ff0; }
		');
		asset('jquery-highlight');
		jquery('
			var url_hash = window.location.hash.replace("/", "");
			if (url_hash) {
				$("li.li-level-0" + url_hash + " .li-level-1", "#faq-items").show();
			}
			$(".li-level-0", "#faq-items").click(function(){
				$(".li-level-1", this).toggle()
			})
			$("input#search", "#faq-search").on("change keyup", function(){
				var words = $(this).val();
				$("#faq-items").unhighlight();
				$("#faq-items").highlight(words);
				$(".li-level-1").hide().filter(":has(\'span.highlight\')").show();
			})
		');
		$items = [];
		foreach ((array)db()->from(self::table)->where('active', 1)->where('locale', conf('language'))->get_all() as $a) {
			$items[$a['id']] = [
				'parent_id'	=> $a['parent_id'],
				'name'		=> _truncate(trim($a['title']), 60, true, '...'),
				'link'		=> url('/@object/#/faq'.$a['id']),
				'id'		=> 'faq'.$a['id'],
			];
			if ($a['text']) {
				$items['1111'.$a['id']] = [
					'parent_id'	=> $a['id'],
					'body'		=> trim($a['text']),
				];
			}
		}
		return tpl()->parse_string($this->_tpl, [
			'items'	=> html()->li_tree($items),
		]);
	}

	/**
	* Hook for the site_map
	*/
	function _hook_sitemap($sitemap = false) {
		if (!is_object($sitemap)) {
			return false;
		}
		$sitemap->_add('/faq?lang='.conf('language'));
		return true;
	}
}