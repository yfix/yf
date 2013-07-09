<?php

$Q = db()->query("SELECT * FROM ".db("smilies")." WHERE emo_set=1 ORDER BY LENGTH(code) DESC");
while($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A;