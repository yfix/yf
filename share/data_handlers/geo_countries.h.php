<?php

return (array)db()->get_all('SELECT * FROM '.db('geo_countries').' WHERE active="1" ORDER BY name ASC');
