<?php

$data = db()->get_all('SELECT * FROM '.db('forum_groups').' ORDER BY id ASC');
