<?php

$data = [];
if ( ! main()->is_db()) {
    return $data;
}
// get_all() returns false on DB error; (array) false would give [false]
foreach (db()->from('locale_langs')->where('active', '1')->order_by('is_default DESC, locale ASC')->get_all() ?: [] as $a) {
    $data[$a['locale']] = $a;
}
return $data;
