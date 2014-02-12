<?php

$data = db()->get_2d('SELECT item, value FROM '.db('settings'));
