<?php

$data[" "] = " ";
$Q = db()->query("SELECT * FROM ".db("countries")." ORDER BY n");
while ($A = db()->fetch_assoc($Q)) $data[$A["c"]] = $A["n"];