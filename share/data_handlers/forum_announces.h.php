<?php

$Q = db()->query("SELECT * FROM ".db("forum_announce")." WHERE active=1");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A;