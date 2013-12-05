<?php
$data = array (
  99999 => 
  array (
    'id' => '1',
    'name' => 'page_title',
    'desc' => '<title> tag contents',
    'pattern_find' => '#(<title>)(.*?)(</title>)#',
    'pattern_replace' => '$1%%TAG%%$3',
    'active' => '0',
  ),
  100000 => 
  array (
    'id' => '2',
    'name' => 'desc',
    'desc' => 'Meta description',
    'pattern_find' => '#(<meta name=["\']{0,1}description["\']{0,1}\\s+content=["\']{0,1})([^"\']*?)(["\']{0,1}[\\s\\/]*?>)#',
    'pattern_replace' => '$1%%TAG%%$3',
    'active' => '0',
  ),
  100001 => 
  array (
    'id' => '3',
    'name' => 'keywords',
    'desc' => 'Meta keywords',
    'pattern_find' => '#(<meta name=["\']{0,1}keywords["\']{0,1}\\s+content=["\']{0,1})([^"\']*?)(["\']{0,1}[\\s\\/]*?>)#',
    'pattern_replace' => '$1%%TAG%%$3',
    'active' => '0',
  ),
);