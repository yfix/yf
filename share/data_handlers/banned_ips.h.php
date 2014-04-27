<?php

$data = (array)db()->get_all('SELECT * FROM '.db('banned_ips'));
