#!/usr/bin/phantomjs

var page = require('webpage').create();
page.open('http://localhost:33380/test/html5fw_bs2', function() {
	page.render('html5fw_bs2.png');
//	phantom.exit();
});
var page = require('webpage').create();
page.open('http://localhost:33380/test/html5fw_bs3', function() {
	page.render('html5fw_bs3.png');
	phantom.exit();
});
