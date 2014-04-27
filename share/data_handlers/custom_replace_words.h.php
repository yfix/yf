<?php

$data = (array)db()->get_all('SELECT key, value FROM '.db('custom_replace_words').' WHERE active="1" ORDER BY key ASC');
