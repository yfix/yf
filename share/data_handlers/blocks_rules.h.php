<?php

$data = db()->get_all('SELECT * FROM '.db('block_rules').' WHERE active="1" ORDER BY block_id ASC, `order` ASC');
