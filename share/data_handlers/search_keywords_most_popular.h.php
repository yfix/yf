<?php

$Q = db()->query("SELECT site_url,text FROM ".db("search_keywords")." WHERE active='1' ORDER BY hits DESC LIMIT 100");
while ($A = db()->fetch_assoc($Q)) $data[$A["text"]] = $A["site_url"];