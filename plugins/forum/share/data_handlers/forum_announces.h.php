<?php

$data = (array)db()->get_all('SELECT * FROM '.db('forum_announce').' WHERE active=1');
