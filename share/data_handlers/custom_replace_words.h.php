<?php

$Q = db()->query("SELECT * FROM `".db("custom_replace_words")."` WHERE `active`='1' ORDER BY `key` ASC");
while ($A = db()->fetch_assoc($Q)) $data[$A["key"]] = $A["value"];