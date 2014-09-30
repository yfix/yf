<?php

return (array)db()->get_all('SELECT * FROM '.db('geo_lang_to_country'));
