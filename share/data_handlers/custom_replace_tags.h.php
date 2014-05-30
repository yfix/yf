<?php

return (array)db()->get_all('SELECT id,pattern_find,pattern_replace FROM '.db('custom_replace_tags').' WHERE active="1"');
