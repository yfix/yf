var _series_colors = [ 
	"#417DEC", 
	"#FFBC40", 
	"#7BA30D",
	"#EA1302", 
	"#6443ED", 
	"#EA7202",
	"#1A5F04", 
	"#A40D22", 
	"#047E8F", 
	"#3AEAD7", 
	"#2B920C" 
];


$(function(){

	$(".bar_chart").each(function(){
		var bar = $(this);
		
		var data = [];
		var points = [];
		var ticks_y = [];
		var ticks_x = [];
		$("input", bar).each(function(i){
		
			if($(this).attr("class") == "line"){
			
				var name = $(this).attr("name");
				var value = $(this).attr("value");
				var link = $(this).attr("link");
				
				name = "<a href='" + link + "'>" + name + "</a>";
				
				points.push(value);

				if($(bar).attr("direction") === "vertical"){
					data.push([i+1, parseFloat(value)]);
				}else{
					data.push([parseFloat(value), i+1]);
				}
				ticks_y.push(name);
			}
			
			if($(this).attr("class") == "x"){
				var name = $(this).attr("name");
				var value = $(this).attr("value");
			
				ticks_x.push([parseFloat(name), value]);
			}
		});
		
		
		hi = parseInt(data.length) * 30 + 22;
		
		$(bar).css("height", hi);
		
		if($(bar).attr("direction") === "vertical"){
			plot = $.jqplot($(bar).attr("id"), [data], {
				grid: {
					shadow: false,
			        background: '#f1f1f1',      // CSS color spec for background color of grid.
			        borderColor: '#e9e9e9',     // CSS color spec for border around grid.
			        borderWidth: 2           // pixel width of border around grid.
				},
				seriesDefaults:{
					shadow: true,   // show shadow or not.
					color: "#417DEC",	
					renderer:$.jqplot.BarRenderer, 
					rendererOptions:{barDirection:"vertical", barPadding: 6, barMargin: 15}
				},
				axes:{
					xaxis:{
						renderer:$.jqplot.CategoryAxisRenderer, 
						ticks:ticks_y
					}, 
					yaxis:{
						ticks:ticks_x
					}
				}
			});		
		}else{
			plot = $.jqplot($(bar).attr("id"), [data], {
				grid: {
					shadow: false,
			        background: '#f1f1f1',
			        borderColor: '#e9e9e9',
			        borderWidth: 2
				},
				seriesDefaults:{
					shadow: true,
					color: "#417DEC",	
					renderer:$.jqplot.BarRenderer, 
					rendererOptions:{barDirection:"horizontal", barPadding: 6, barMargin: 13, barWidth: 15}
				},
				series:[
					{
						pointLabels:{
							show: true,
							labels: points,
							location:'e'
						}
					}
				],
				highlighter: {
					show: false,
					lineWidthAdjust: 2.5,
					sizeAdjust: 7.5,
					showTooltip: true,
					tooltipLocation: 'n',
					tooltipOffset: 2,
					tooltipAxes: 'x',    
					useAxesFormatters: true,
					//formatString: sign + ', %s'
			    },
				axes:{
					xaxis:{
						ticks:ticks_x
					},
					yaxis:{
						renderer:$.jqplot.CategoryAxisRenderer, 
						ticks:ticks_y
					}
				}
			});		

		}
	});
	
	$(".pie_chart").each(function(){
		var pie = $(this);
		var max = 0;
	
		var data = [];
		$("input", pie).each(function(){
			var value = $(this).attr("value");
			var name = $(this).attr("name");
			var link = $(this).attr("link");
			
			if(link !== ""){
				name = "<a href='" + link + "'>" + name + "</a>";
			}
			
			name = name + " -  " + value;
			
			value = parseFloat(value);
			data.push([name, value]);
			
			if(value > max){
				max = value;
			}
			
		});
		
		if(max == 0){
			return;
		}
		
		plot_pie = $.jqplot($(pie).attr("id"), [data], {
			sortData: false,
			seriesDefaults:{renderer:$.jqplot.PieRenderer, 
				rendererOptions:{
					seriesColors: [ 
						"#417DEC", 
						"#FFBC40", 
						"#7BA30D",
						"#EA1302", 
						"#6443ED", 
						"#EA7202",
						"#1A5F04", 
						"#A40D22", 
						"#047E8F", 
						"#3AEAD7", 
						"#2B920C" 
					],
					sliceMargin:7,
					diameter: 250
				}
			},
			grid: {
				shadow: false,
		        background: '#ffffff',
		        borderColor: '#ffffff',
		        borderWidth: 0

			},
			legend:{
				show:true,
				location: 'nw',
				preDraw: true,
				xoffset: 5,
				yoffset: 5
			},
			series:[
				{
					pointLabels:{
						show: false
					}
				}
			]
		});
	});
	
	
	$(".chart").each(function(){
	
		graph = $(this);
		
		var stats_line = {};
		var max = 0;
		var line_names = {};
		
		$("input", graph).each(function(){
			line_name = $(this).attr("name");
			line_names[line_name] = line_name;
		});
		
		
		$.each(line_names, function(key, name){
			var line = [];
			
			$("[name="+name+"]", graph).each(function(){
				val = parseFloat($(this).val());
				line.push([$(this).attr("date"), val]);
				if(val > max){
					max = val;
				}
			});
			
			stats_line[name] = line;
		});
		
		var stats_line_js = [];
		var series = [];
		var count_stats = count(stats_line);
		var show_legend = count_stats > 1 ? true : false;
		
		i = 0;
		$.each(stats_line, function(name, line){
		
			

			stats_line_js.push(line);
			series.push({
		        lineWidth: 3,
				label: name,
				color: _series_colors[i],
				fillColor: _series_colors[i],
				fill: count_stats > 1 ? false : true,
				fillAndStroke: count_stats > 1 ? false : true,
				fillAlpha: 0.3,
		        markerOptions: {
					size: 3
		        },
				pointLabels:{
					show: false
				}
		    });
			i++;
			if (i > _series_colors.length){
				i = 0;
			}
		});
		
		plot_stats = $.jqplot(graph.attr("id"),stats_line_js,{
			grid: {
		        drawGridLines: true,
		        gridLineColor: '#ffffff',
		        background: '#f1f1f1',
		        borderColor: '#e9e9e9',
		        borderWidth: 2,
		        shadow: false,
		        renderer: $.jqplot.CanvasGridRenderer,
		        rendererOptions: {}
		    },
			legend: {
		        show: show_legend,
				location: 'nw'
		    },
			axes: {
		        xaxis: {
		            renderer: $.jqplot.DateAxisRenderer,
					pad: 1.0,
		            rendererOptions: {
		                tickRenderer: $.jqplot.CanvasAxisTickRenderer
		            },
		            tickOptions: {
		                formatString: '%d-%m-%Y',
		                fontSize: '8pt',
		                fontFamily: 'Tahoma',
		                angle: -90
		            }
		        },
				yaxis: {
					pad: 1.0,
					max: Math.round(max*1.1)
				}
		    },
			highlighter: {
				lineWidthAdjust: 2.5,
				sizeAdjust: 7.5,
				showTooltip: true,
				tooltipLocation: 'n',
				tooltipOffset: 2,
				tooltipAxes: 'yx',    
				useAxesFormatters: true,
//				formatString: sign +', %s'
		    },
		    series: series
		});
		
	
	});
	
});


function count( mixed_var, mode ) {    // Count elements in an array, or properties in an object
    
    var key, cnt = 0;
 
    if( mode == 'COUNT_RECURSIVE' ) mode = 1;
    if( mode != 1 ) mode = 0;
 
    for (key in mixed_var){
        cnt++;
        if( mode==1 && mixed_var[key] && (mixed_var[key].constructor === Array || mixed_var[key].constructor === Object) ){
            cnt += count(mixed_var[key], 1);
        }
    }
 
    return cnt;
}
