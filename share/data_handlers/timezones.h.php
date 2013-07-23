<?php

$Q = db()->query("SELECT name, offset FROM ".db("timezones")." ORDER BY n");
while ($A = db()->fetch_assoc($Q)) $data[$A["offset"]] = $A["name"];