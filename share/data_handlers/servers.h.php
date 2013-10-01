<?php

$Q = db()->query('SELECT * FROM '.db('core_servers').'');
while ($A = db()->fetch_assoc($Q)) $data[$A['id']] = $A;