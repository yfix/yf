<?php

return (array)db()->get_2d('SELECT id,name FROM '.db('admin_groups').' WHERE active="1"');
