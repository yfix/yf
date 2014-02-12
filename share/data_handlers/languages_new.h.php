<?php

$data = db()->get_all('SELECT * FROM '.db('languages').' WHERE active="1" ORDER BY native ASC');
