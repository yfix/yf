<?php

$data = (array)db()->get_2d('SELECT c, call_code FROM '.db('countries').' ORDER BY n');
