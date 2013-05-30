<?php

$Q = db()->query("SELECT * FROM `".db("custom_bbcode")."` WHERE `active`='1'");
while ($A = db()->fetch_assoc($Q)) $data[$A["tag"]] = array("useoption" => $A["useoption"], "replace" => $A["replace"]);