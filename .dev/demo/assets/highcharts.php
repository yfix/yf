<?php

js([
    'https://cdnjs.cloudflare.com/ajax/libs/highcharts/10.0.0/highcharts.js',
    //	'https://cdnjs.cloudflare.com/ajax/libs/highcharts/10.0.0/modules/exporting.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/highcharts/10.0.0/themes/gray.min.js',
]);

jquery('
$.getJSON("' . WEB_PATH . 'docs/jsonp?filename=usdeur.json&callback=?", function (data) {
    Highcharts.chart("hc-container", {
        chart: {
            zoomType: "x"
        },
        title: {
            text: "USD to EUR exchange rate over time"
        },
        subtitle: {
            text: document.ontouchstart === undefined ?
                    "Click and drag in the plot area to zoom in" : "Pinch the chart to zoom in"
        },
        xAxis: {
            type: "datetime"
        },
        yAxis: {
            title: {
                text: "Exchange rate"
            }
        },
        legend: {
            enabled: false
        },
        plotOptions: {
            area: {
                fillColor: {
                    linearGradient: {
                        x1: 0,
                        y1: 0,
                        x2: 0,
                        y2: 1
                    },
                    stops: [
                        [0, Highcharts.getOptions().colors[0]],
                        [1, Highcharts.Color(Highcharts.getOptions().colors[0]).setOpacity(0).get("rgba")]
                    ]
                },
                marker: {
                    radius: 2
                },
                lineWidth: 1,
                states: {
                    hover: {
                        lineWidth: 1
                    }
                },
                threshold: null
            }
        },
        series: [{
            type: "area",
            name: "USD to EUR",
            data: data
        }]
    });
});
');

return '<div id="hc-container" style="min-width: 310px; height: 400px; margin: 0 auto"></div>';
