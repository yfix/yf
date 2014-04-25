#!/usr/bin/php
<?php

#http://www.consultant.ru/document/cons_doc_LAW_100738/?frame=2#p90
$url = 'http://ru.wikipedia.org/wiki/%D0%9E%D0%B1%D1%89%D0%B5%D1%80%D0%BE%D1%81%D1%81%D0%B8%D0%B9%D1%81%D0%BA%D0%B8%D0%B9_%D0%BA%D0%BB%D0%B0%D1%81%D1%81%D0%B8%D1%84%D0%B8%D0%BA%D0%B0%D1%82%D0%BE%D1%80_%D0%B2%D0%B0%D0%BB%D1%8E%D1%82';
echo file_get_contents($url);