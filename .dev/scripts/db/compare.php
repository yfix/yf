#!/usr/bin/php
<?php

require_once dirname(__DIR__) . '/scripts_init.php';

$report = db()->migrator()->compare();
echo _var_export($report) . PHP_EOL;
