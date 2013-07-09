<?php

$Q = db()->query("SELECT * FROM ".db("rss_feeds")." WHERE active='1' ORDER BY `order` DESC");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A;