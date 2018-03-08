<?php

return (array)db()->get_all('SELECT * FROM '.db('core_servers'));
