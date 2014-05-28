<?php

class yf_form2_stars {

	/**
	*/
	function stars($name = '', $desc = '', $extra = array(), $replace = array(), $__this) {
		if (is_array($desc)) {
			$extra += $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$extra['name'] = $extra['name'] ?: ($name ?: 'stars');
		$extra['desc'] = $__this->_prepare_desc($extra, $desc);
		$func = function($extra, $r, $_this) {
			$extra['id'] = $extra['name'];
			$color_ok = $extra['color_ok'] ?: 'yellow';
			$color_ko = $extra['color_ko'] ?: '';
			$class = $extra['class'] ?: 'icon-star icon-large';
			$class_ok = $extra['class_ok'] ?: 'star-ok';
			$class_ko = $extra['class_ko'] ?: 'star-ko';
			$max = $extra['max'] ?: 5;
			$stars = $extra['stars'] ?: 5;
			$input = isset($r[$extra['name']]) ? $r[$extra['name']] : $extra['name'];
			foreach (range(1, $stars) as $num) {
				$is_ok = $input >= ($num * $max / $stars) ? 1 : 0;
				$body[] = '<i class="'.$class.' '.($is_ok ? $class_ok : $class_ko).'" style="color:'.($is_ok ? $color_ok : $color_ko).';" title="'.$input.'"></i>';
			}
			return $_this->_row_html(implode(PHP_EOL, $body), $extra, $r);
		};
		if ($__this->_chained_mode) {
			$__this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $__this;
		}
		return $func($extra, $replace, $__this);
	}

	/**
	* Star selector, got from http://fontawesome.io/examples/#custom. Require this CSS:
	*	'<style>
	*	.rating { unicode-bidi:bidi-override;direction:rtl;font-size:20px }
	*	.rating span.star { font-family:FontAwesome;font-weight:normal;font-style:normal;display:inline-block }
	*	.rating span.star:hover { cursor:pointer }
	*	.rating span.star:before { content:"\f006";padding-right:0.2em;color:#999 }
	*	.rating span.star:hover:before, .rating span.star:hover~span.star:before{ content:"\f005";color:#e3cf7a }
	*	</style>';
	*/
	function stars_select($name = '', $desc = '', $extra = array(), $replace = array(), $__this) {
		if (is_array($desc)) {
			$extra += $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$extra['name'] = $extra['name'] ?: ($name ?: 'stars');
		$extra['desc'] = $__this->_prepare_desc($extra, $desc);
		$func = function($extra, $r, $_this) {
			$_this->_prepare_inline_error($extra);
			$max = $extra['max'] ?: 5;
			$stars = $extra['stars'] ?: 5;
			$class = $extra['class'] ?: 'star';
			$body[] = '<span class="rating">';
			foreach (range(1, $stars) as $num) {
				$body[] = '<span class="'.$class.' '.$extra['name'].'" data-name="'.$extra['name'].'" data-value="'.($stars-$num+1).'"></span>';
			}
			$body[] = '</span>';
			$body[] = '<input type="hidden" name="'.$extra['name'].'" id='.$extra['name'].' value="0">';
			
			js('<script type="text/javascript">
				$(function () {
					$(".'.$class.'.'.$extra['name'].'").on("click",function() {
						var value = $(this).attr("data-value");
						$("#"+$(this).attr("data-name")).val(value);
						$(".rating.star.'.$extra['name'].'").each(function() {
							$(this).attr("data-value");
							if (value>=$(this).attr("data-value")) {
								$(this).addClass("rating_selected");								
							} else {
								$(this).removeClass("rating_selected");				
							}
						});
					});
				});
				</script>');
			
			return $_this->_row_html(implode('', $body), $extra, $r);
		};
		if ($__this->_chained_mode) {
			$__this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $__this;
		}
		return $func($extra, $replace, $__this);
	}
}