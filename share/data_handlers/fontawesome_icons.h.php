<?php

$Q = db()->query('SELECT name FROM '.db('icons').' WHERE active="1"');
while ($A = db()->fetch_assoc($Q)) $data[$A['name']] = $A['name'];
