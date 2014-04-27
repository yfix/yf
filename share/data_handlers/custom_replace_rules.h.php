<?php

$data = (array)db()->get_all('SELECT * FROM '.db('custom_replace_rules').' WHERE active="1" ORDER BY tag_id ASC, `order` ASC');
