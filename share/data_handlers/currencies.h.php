<?php

$data = db()->get_all('SELECT * FROM '.db('currencies').' WHERE active="1" ORDER BY name ASC');
