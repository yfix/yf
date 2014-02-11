<?php

$data = db()->get_all('SELECT * FROM '.db('smilies').' WHERE emo_set=2 ORDER BY LENGTH(code) DESC');
