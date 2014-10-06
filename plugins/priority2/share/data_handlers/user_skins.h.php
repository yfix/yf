<?php

return (array)db()->get_2d('SELECT id, name FROM '.db('skins').' WHERE for_user="1" AND active="1"');
