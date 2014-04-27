<?php

$data = (array)db()->get_2d('SELECT name, value FROM '.db('conf').' WHERE active="1"');
