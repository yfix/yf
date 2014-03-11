<?php

$data = db()->get_2d('SELECT id, name FROM '.db('blocks').' WHERE type="admin" ORDER BY name ASC');
