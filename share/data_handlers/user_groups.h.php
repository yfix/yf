<?php

$data = db()->get_2d('SELECT id, name FROM '.db('user_groups').' WHERE active="1"');
