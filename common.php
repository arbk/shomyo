<?php

$f3 = require(__DIR__.'/libs/f3/base.php');

$f3->set('DEBUG', 0);
$f3->set('version', '2.17.4');
$f3->set('AUTOLOAD', __DIR__ . '/;libs/f3/;libs/;libs/JShrink/;libs/WideImage/;daos/;libs/twitteroauth/;libs/FeedWriter/');
$f3->set('cache', __DIR__ . '/data/cache');
$f3->set('BASEDIR', __DIR__);
$f3->set('LOCALES', __DIR__ . '/public/lang/');
$f3->set('LOGDIR', __DIR__ . '/data/logs/');

// read defaults
$f3->config('defaults.ini');

// read config, if it exists
if (file_exists('config.ini')) {
    $f3->config('config.ini');
}

// overwrite config with ENV variables
$env_prefix = $f3->get('env_prefix');
foreach ($f3->get('ENV') as $key => $value) {
    if (strncasecmp($key, $env_prefix, strlen($env_prefix)) == 0) {
        $f3->set(strtolower(substr($key, strlen($env_prefix))), $value);
    }
}

// error log
ini_set("log_errors", 1);
ini_set("error_log", $f3->get('LOGDIR') . 'error.log');

// init logger
$f3->set(
    'logger',
    new \helpers\Logger($f3->get('LOGDIR') . 'default.log', $f3->get('logger_level'))
);

// init error handling
$f3->set(
    'ONERROR',
    function ($f3) {
        $trace = $f3->get('ERROR.trace');
        $tracestr = "\n";
        if (is_array($trace)) {
            foreach ($trace as $entry) {
                $tracestr = $tracestr . $entry['file'] . ':' . $entry['line'] . "\n";
            }
        } else {
            $tracestr = $tracestr . $trace . "\n";
        }

        \F3::get('logger')->log($f3->get('ERROR.text') . $tracestr, \ERROR);
        if (\F3::get('DEBUG')!=0) {
            echo $f3->get('lang_error') . ": ";
            echo $f3->get('ERROR.text') . "\n";
            echo $tracestr;
        } else {
            echo $f3->get('lang_error');
        }
    }
);

if (\F3::get('DEBUG') != 0) {
    ini_set('display_errors', 0);
}
