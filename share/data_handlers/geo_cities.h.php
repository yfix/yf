<?php

$data = db()->get_all('SELECT * FROM '.db('geo_cities').' WHERE active="1"');
