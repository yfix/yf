<?php

class test_gridster_demo {
	function show() {
		asset('gridster');
		css('
			.gridster { width: 960px; margin: 0 auto; }
			.gridster .gs-w { background: #FFF; cursor: pointer; -webkit-box-shadow: 0 0 5px rgba(0,0,0,0.3); box-shadow: 0 0 5px rgba(0,0,0,0.3); }
			.gridster .player { -webkit-box-shadow: 3px 3px 5px rgba(0,0,0,0.3); box-shadow: 3px 3px 5px rgba(0,0,0,0.3); }
			.gridster .gs-w.try { background-image: url(../img/sprite.png); background-repeat: no-repeat; background-position: 37px -169px; }
			.gridster .preview-holder { border: none!important; border-radius: 0!important; background: rgba(255,255,255,.2)!important; }
			.gridster [hidden] { display: none; }
			.gridster ul, .gridster ol { list-style: none; }
		');
		jquery('
			$(".gridster ul").gridster({
				widget_margins: [10, 10],
				widget_base_dimensions: [140, 140],
				min_cols: 6,
				resize: {
					enabled: true
				}
			});
		');
		return '
			<div class="gridster">
			    <ul>
			        <li data-row="1" data-col="1" data-sizex="1" data-sizey="1"></li>
			        <li data-row="2" data-col="1" data-sizex="1" data-sizey="1"></li>
			        <li data-row="3" data-col="1" data-sizex="1" data-sizey="1"></li>
 
			        <li data-row="1" data-col="2" data-sizex="2" data-sizey="1"></li>
			        <li data-row="2" data-col="2" data-sizex="2" data-sizey="2"></li>
 
			        <li data-row="1" data-col="4" data-sizex="1" data-sizey="1"></li>
			        <li data-row="2" data-col="4" data-sizex="2" data-sizey="1"></li>
			        <li data-row="3" data-col="4" data-sizex="1" data-sizey="1"></li>
 
			        <li data-row="1" data-col="5" data-sizex="1" data-sizey="1"></li>
					<li data-row="3" data-col="5" data-sizex="1" data-sizey="1"></li>
 
			        <li data-row="1" data-col="6" data-sizex="1" data-sizey="1"></li>
			        <li data-row="2" data-col="6" data-sizex="1" data-sizey="2"></li>
			    </ul>
			</div>';
	}
}
