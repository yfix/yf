<?php

return (array)db()->get_2d('SELECT id, height FROM '.db('heights'));
