<?php

$data = db()->get_all('SELECT * FROM '.db('regions').' WHERE active="1"');
