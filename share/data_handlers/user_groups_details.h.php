<?php

return (array)db()->get_all('SELECT * FROM '.db('user_groups').' WHERE active="1"');
