<?php

return (array)db()->get_2d('SELECT id, name FROM '.db('blocks').' ORDER BY name ASC');
