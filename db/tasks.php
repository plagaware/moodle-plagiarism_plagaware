<?php
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'plagiarism_plagaware\task\scheduledtask',
        'blocking'  => 0,
        'minute'    => '*/5', // Every 5 minutes
        'hour'      => '*',
        'day'       => '*',
        'dayofweek' => '*',
        'month'     => '*'
    ]
];
