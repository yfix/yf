<?php

$data = [];
$q = db()->query('SELECT * FROM ' . db('custom_bbcode') . ' WHERE active="1"');
while ($a = db()->fetch_assoc($q)) {
    $data[$a['tag']] = [
        'useoption' => $a['useoption'],
        'replace' => $a['replace'],
    ];
}
return $data;
