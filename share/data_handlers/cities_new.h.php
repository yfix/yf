<?php

return (array)db()->get_all('SELECT * FROM '.db('cities').' WHERE active="1"');
