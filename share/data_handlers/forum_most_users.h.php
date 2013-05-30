<?php

$data = array(
	"num_most_users"=> count(module("forum")->online_array),
	"most_date"		=> time(),
);