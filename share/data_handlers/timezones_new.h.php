<?php

$data = db()->get_all('SELECT * FROM '.db('timezones').' WHERE active="1" ORDER BY offset ASC, name ASC');
