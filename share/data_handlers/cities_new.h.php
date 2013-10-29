<?php
$data = db()->get_all('SELECT * FROM '.db('cities').' WHERE active="1"');
