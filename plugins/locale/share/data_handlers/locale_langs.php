<?php

$data = [];
if ( ! main()->is_db()) {
    return $data;
}
foreach ((array) db()->from('locale_langs')->where('active', '1')->order_by('is_default DESC, locale ASC')->get_all() as $a) {
    $data[$a['locale']] = $a;
}
return $data;
