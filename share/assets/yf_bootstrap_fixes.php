<?php

return array(
	'versions' => array(
		'master' => array(
			'css' => array('
fieldset.well { padding-bottom: 0; }
fieldset.well legend { margin-bottom:0; padding: 0 0.5em; width: auto; line-height: 1.5em; background: inherit; }
legend+.control-group { margin-top: 0; }

.sidebar-nav { padding: 9px 0; }

.navbar .dropdown-menu .sub-menu { left: 100%; position: absolute; top: 0; visibility: hidden; margin-top: -1px; }
.navbar .dropdown-menu li:hover > .sub-menu { visibility: visible; }
.navbar .dropdown:hover .dropdown-menu { display: block; }

.nav-tabs .dropdown-menu, .nav-pills .dropdown-menu, .navbar .dropdown-menu { margin-top: 0; }
.cssfw-bs3 .navbar-header { width:99%; padding-right:1%; }

.form-no-labels .controls { margin-left: 0; }

td input[type=text], td textarea { margin-bottom: 0; }

.rating { unicode-bidi:bidi-override;direction:rtl;font-size:20px }
.rating span.star { font-family:FontAwesome;font-weight:normal;font-style:normal;display:inline-block }
.rating span.star:hover { cursor:pointer }
.rating span.star:before { content:"\f006";padding-right:0.2em;color:#999 }
.rating span.star:hover:before, .rating span.star:hover~span.star:before{ content:"\f005";color:#e3cf7a }

.label.labels-big, .badge.labels-big { font-size: inherit; }

.form-condensed .control-group { margin-bottom: 1em; }

.bs-docs-sidenav { width: 200px; margin: 0; padding: 0; z-index: 1000; }
.bs-docs-sidenav [class*="span"] { margin-left: 0; }
.bs-docs-sidenav > li > a { display: block; margin: 0; padding: 5px 15px; /*  border: 1px solid #555;*/ }
.bs-docs-sidenav .icon-chevron-right { float: right; margin-top: 0; margin-right: -5px; opacity: .25; }

.table a.btn { text-decoration: none; }
.table { width:auto; }

.control-group-required .control-label:after, .form-group-required .control-label:after { content:" *"; color:red; }

.cssfw-bs2 .modal.fade.in { top: 30px; }
.cssfw-bs2 .modal .form-horizontal .control-group { margin-bottom: 1em; }
.cssfw-bs2 .modal .form-horizontal .controls { margin-left: 150px; margin-right: 20px; }
.cssfw-bs2 .modal .form-horizontal .control-label { width: 130px; }

.cssfw-bs3 .carousel-control .icon-chevron-left,
.cssfw-bs3 .carousel-control .icon-chevron-right { position: absolute; top: 50%; z-index: 5; display: inline-block; }
.cssfw-bs3 a.accordion-toggle { display: block; }

.cssfw-bs3 .form-vertical .col-md-3 { width:100%; margin-top: 5px; }

/* override styles per bs theme here */ 
.bs-theme-slate textarea, .bs-theme-slate select, .bs-theme-slate .chosen-container .chosen-drop { color: #52575c; }
.bs-theme-slate.cssfw-bs3 .dropdown-toggle .caret { border-top-color: #c8c8c8; }
			'),
		),
	),
);
