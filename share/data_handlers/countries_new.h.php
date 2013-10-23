<?php
$data = db()->get_all('SELECT * FROM '.db('countries').' WHERE active="1" ORDER BY name ASC');
