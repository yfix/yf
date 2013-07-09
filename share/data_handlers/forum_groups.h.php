<?php

$Q = db()->query("SELECT * FROM ".db("forum_groups")." ORDER BY id ASC");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A;