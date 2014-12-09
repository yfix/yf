<?php

return (array)db()->get_2d('SELECT item, value FROM '.db('settings'));
