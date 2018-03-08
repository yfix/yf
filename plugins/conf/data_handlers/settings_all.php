<?php

return (array)db()->get_all('SELECT * FROM '.db('settings'), 'item');
