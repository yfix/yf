<?php

$data = db()->get_2d('SELECT id,name FROM '.db('admin_groups').' WHERE active="1"');
