<?php

if (!class_exists('MIME')) require_once 'MIME.php';

if (!class_exists('SMTP4')) require_once 'PHP4/SMTP4.php';
class SMTP extends SMTP4 { }

?>