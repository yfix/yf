<?php

$Q = db()->query("SELECT * FROM ".db("smilies")." WHERE emo_set=2 ORDER BY LENGTH(code) DESC");
while($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A;