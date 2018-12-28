<?php

return [
    'versions' => [
        '1.0.8' => [
            'js' => [
                '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.8/jquery.jqplot.min.js',
                '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.8/plugins/jqplot.dateAxisRenderer.min.js',
                '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.8/plugins/jqplot.categoryAxisRenderer.min.js',
                '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.8/plugins/jqplot.ohlcRenderer.min.js',
                '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.8/plugins/jqplot.highlighter.min.js',
                '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.8/plugins/jqplot.cursor.min.js',
//				'//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.8/excanvas.min.js',
            ],
            'css' => [
                '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.8/jquery.jqplot.min.css',
            ],
        ],
    ],
    'require' => [
        'asset' => 'jquery',
    ],
];
