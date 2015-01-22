<?php

return array('versions' => array('master' => array('css' => array('

fieldset.well { padding-bottom:0; }
fieldset.well legend { margin-bottom:0; padding:0 0.5em; width:auto; line-height:1.5em; background:inherit; }
legend+.control-group { margin-top:0; }

.sidebar-nav { padding:9px 0; }

@media (min-width:768px) {
	.navbar .dropdown-menu .sub-menu { left:100%; position:absolute; top:0; visibility:hidden; margin-top:-1px; }
	.navbar .dropdown-menu li:hover > .sub-menu { visibility:visible; }
	.navbar .dropdown:hover .dropdown-menu { display:block; }
}
.nav-tabs .dropdown-menu, .nav-pills .dropdown-menu, .navbar .dropdown-menu { margin-top:0; }

.form-no-labels .controls { margin-left:0; }

td input[type=text], td textarea { margin-bottom:0; }

.rating { unicode-bidi:bidi-override;direction:rtl;font-size:20px }
.rating span.star { font-family:FontAwesome;font-weight:normal;font-style:normal;display:inline-block }
.rating span.star:hover { cursor:pointer }
.rating span.star:before { content:"\f006";padding-right:0.2em;color:#999 }
.rating span.star:hover:before, .rating span.star:hover~span.star:before{ content:"\f005";color:#e3cf7a }

.label.labels-big, .badge.labels-big { font-size:inherit; }

.bs-docs-sidenav { width:200px; margin:0; padding:0; z-index:1000; }
.bs-docs-sidenav [class*="span"] { margin-left:0; }
.bs-docs-sidenav > li > a { display:block; margin:0; padding:5px 15px; }
.bs-docs-sidenav .icon-chevron-right { float:right; margin-top:0; margin-right:-5px; opacity:.25; }

.table a.btn { text-decoration:none; }
.table { width:auto; }

.form-condensed .control-group { margin-bottom:1em; }

.form-inline .form-group { width:100%; }
.form-inline .form-group .controls { margin-left:0; }
.form-inline .radio input[type=radio] { float:none; }
.form-inline legend { margin-bottom:0; margin-top:0; }

.control-group-required .control-label:after, .form-group-required .control-label:after { content:" *"; color:red; }

.cssfw-bs2 label.radio-inline { padding-left:inherit; display:inline-block; margin-bottom:inherit; }
.cssfw-bs2 label.radio-inline input[type=radio] { float:none; margin-left:inherit; margin-top:0; }

.cssfw-bs2 .dropdown-toggle .caret { margin:0 !important; }

.cssfw-bs2 .icon-email:before { content:"@"; }

.cssfw-bs2 .modal.fade.in { top:30px; }
.cssfw-bs2 .modal .form-horizontal .control-group { margin-bottom:1em; }
.cssfw-bs2 .modal .form-horizontal .controls { margin-left:150px; margin-right:20px; }
.cssfw-bs2 .modal .form-horizontal .control-label { width:130px; }

.cssfw-bs2 .breadcrumb > li+li:before { content:"/\00a0"; padding:0 5px; }
.cssfw-bs2 .navbar .breadcrumb { margin:0 7px; border:0; }
@media (min-width:768px) {
	.cssfw-bs2 .navbar .breadcrumb { float:left; margin:2px 10px; }
}

.cssfw-bs3 .navbar-header { width:99%; padding-right:1%; }
.cssfw-bs3 .navbar .breadcrumb { margin:0 7px; }
@media (min-width:768px) {
	.cssfw-bs3 .navbar .breadcrumb { float:left; margin:7px 10px; }
}

.cssfw-bs3 .container-fixed input, textarea, select { max-width:300px; }
.cssfw-bs3 .container-fixed textarea.cke_source { max-width:inherit; }
.cssfw-bs3 .container-fixed .input-mini { width:70px !important }
.cssfw-bs3 .container-fixed .input-small { width:100px !important }
.cssfw-bs3 .container-fixed .input-medium { width:160px !important }
.cssfw-bs3 .container-fixed .input-large { width:220px !important }
.cssfw-bs3 .container-fixed .input-xlarge { width:280px !important }
.cssfw-bs3 .container-fixed .input-xxlarge { width:540px !important }
.cssfw-bs3 .container-fixed .input-group[class*="col-"] { float:left !important; margin-right:3px; }
.cssfw-bs3 .radio-inline, .cssfw-bs3 .checkbox-inline { margin-top:0; }

.cssfw-bs3 .carousel-control .icon-chevron-left,
.cssfw-bs3 .carousel-control .icon-chevron-right { position:absolute; top:50%; z-index:5; display:inline-block; }
.cssfw-bs3 a.accordion-toggle { display:block; }

.cssfw-bs3 .form-vertical .control-label { width:100% !important; margin-top:5px; margin-bottom:0; padding-left:0; }
.cssfw-bs3 .form-vertical .controls { width:100%; margin-left:0; margin-top:5px; float:left; padding-left:0; }
.cssfw-bs3 .form-vertical .input-group { max-width:300px; }

.cssfw-bs3 .form-horizontal .controls.btn-group { margin-left:0; }
.cssfw-bs3 .form-horizontal .controls.btn-group label.btn { padding: 3px 5px; }

.cssfw-bs3 .media { min-height:75px; }

.cssfw-bs3 .controls .help-block { width:100%; margin-bottom:0; }

.cssfw-bs3 .modal .form-horizontal .form-group { margin-right:inherit; margin-left:inherit; }
/*.cssfw-bs3 .modal .form-horizontal .control-label.col-md-3 { width:30%; }*/
/*.cssfw-bs3 .modal .form-horizontal .controls.col-md-offset-3 { margin-left:30%; }*/

/* override styles per bs theme here */ 
.bs-theme-slate textarea, .bs-theme-slate select, .bs-theme-slate .chosen-container .chosen-drop { color:#52575c; }
.bs-theme-slate.cssfw-bs3 .dropdown-toggle .caret { border-top-color:#c8c8c8; }

'))));
