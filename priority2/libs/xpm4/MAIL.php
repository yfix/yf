<?php

if (!class_exists('SMTP')) require_once 'SMTP.php';

if (!class_exists('MAIL4')) require_once 'PHP4/MAIL4.php';
class MAIL extends MAIL4 { }

?>