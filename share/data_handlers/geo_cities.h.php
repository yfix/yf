<?php

$data = (array)db()->get_all('SELECT * FROM '.db('geo_cities').' WHERE active="1" ORDER BY name ASC');
