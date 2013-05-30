<?php

$data[" "] = "Non US";
$Q = db()->query("SELECT * FROM `".db("states")."`");
while ($A = db()->fetch_assoc($Q)) $data[$A["code"]] = $A["name"];