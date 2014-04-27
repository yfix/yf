<?php

$data = (array)db()->get_all('SELECT * FROM '.db('admin_groups').' WHERE active="1"');
