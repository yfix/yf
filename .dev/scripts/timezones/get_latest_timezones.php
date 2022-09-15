#!/usr/bin/env php
<?php

require_once dirname(__DIR__) . '/scripts_utils.php';

// http://www.iana.org/time-zones
// http://www.iana.org/time-zones/repository/releases/tzdata2016d.tar.gz
// pecl install timezonedb
function get_latest_timezones()
{
    $timezones_list = function () {
        $timezones = [];
        $offsets = [];
        $now = new DateTime();
        $format_utc_offset = function ($offset) {
            $hours = (int) ($offset / 3600);
            $minutes = abs((int) ($offset % 3600 / 60));
            return 'UTC' . ($offset ? sprintf('%+03d:%02d', $hours, $minutes) : '');
        };
        foreach (DateTimeZone::listIdentifiers() as $timezone) {
            $now->setTimezone(new DateTimeZone($timezone));
            $offsets[] = $offset = $now->getOffset();
            $timezones[$timezone] = [
                'name' => $timezone,
                'offset' => $format_utc_offset($offset),
                'seconds' => $offset,
                'active' => 1,
            ];
        }
        array_multisort($offsets, $timezones);
        return $timezones;
    };
    $data = $timezones_list();

    $f4 = __DIR__ . '/timezones.php';
    file_put_contents($f4, '<?' . 'php' . PHP_EOL . 'return ' . var_export($data, 1) . ';');
    print_r($data);
}

get_latest_timezones();
