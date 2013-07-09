<?php

$Q = db()->query("SELECT * FROM ".db("forum_moderators")."");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A;