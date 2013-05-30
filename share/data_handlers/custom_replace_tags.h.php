<?php

$Q = db()->query("SELECT `id`,`pattern_find`,`pattern_replace` FROM `".db("custom_replace_tags")."` WHERE `active`='1'");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A;