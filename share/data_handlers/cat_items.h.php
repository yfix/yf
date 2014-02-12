<?php

$data = db()->get_all('SELECT * FROM '.db('category_items').' WHERE active="1" ORDER BY `order` ASC');
