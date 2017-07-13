<?php

$f3 = require(__DIR__.'/libs/f3/base.php');

$f3->set('DEBUG',0);
$f3->set('version','2.15.2');
$f3->set('AUTOLOAD',__dir__.'/;libs/f3/;libs/;libs/JShrink/;libs/WideImage/;daos/;libs/twitteroauth/;libs/FeedWriter/');
$f3->set('cache',__dir__.'/data/cache');
$f3->set('BASEDIR',__dir__);
$f3->set('LOCALES',__dir__.'/public/lang/');

// read defaults
$f3->config('defaults.ini');

// read config, if it exists
if(file_exists('config.ini'))
    $f3->config('config.ini');

// overwrite config with ENV variables
$env_prefix = $f3->get('env_prefix');
foreach($f3->get('ENV') as $key => $value) {
  if(strncasecmp($key,$env_prefix,strlen($env_prefix)) == 0) {
    $f3->set(strtolower(substr($key,strlen($env_prefix))),$value);
  }
}

// init logger
$f3->set(
    'logger',
    new \helpers\Logger( __dir__.'/data/logs/default.log', $f3->get('logger_level') )
);

// init error handling
$f3->set('ONERROR',
    function($f3) {
        $trace = $f3->get('ERROR.trace');
        $tracestr = "\n";
        foreach($trace as $entry) {
            $tracestr = $tracestr . $entry['file'] . ':' . $entry['line'] . "\n";
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

if (\F3::get('DEBUG')!=0)
    ini_set('display_errors',0);
