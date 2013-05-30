<?php

$Q = db()->query("SELECT * FROM `".db("forum_ranks")."`");
while($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A;