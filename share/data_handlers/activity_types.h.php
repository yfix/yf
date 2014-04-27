<?php

$data = (array)db()->get_all('SELECT * FROM '.db('activity_types').' WHERE active="1"');
