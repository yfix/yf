<?php

$data = (array)db()->get_all('SELECT * FROM '.db('smilies').' WHERE emo_set=1 ORDER BY LENGTH(code) DESC');
