<?php

$Q = db()->query("SELECT * FROM ".db("forum_forums")." ORDER BY category ASC, order ASC");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A;