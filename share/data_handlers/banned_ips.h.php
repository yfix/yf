<?php

$Q = db()->query("SELECT * FROM `".db("banned_ips")."`");
while ($A = db()->fetch_assoc($Q)) $data[$A["ip"]] = $A;