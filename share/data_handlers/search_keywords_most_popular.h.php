<?php

return (array)db()->get_2d('SELECT site_url,text FROM '.db('search_keywords').' WHERE active="1" ORDER BY hits DESC LIMIT 100');
