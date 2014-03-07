<?php

$data = db()->get_2d('SELECT id, name FROM '.db('blocks').' WHERE type="user" ORDER BY name ASC');
