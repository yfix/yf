<?php

$data = db()->get_2d('SELECT name, offset FROM '.db('timezones').' ORDER BY n');
