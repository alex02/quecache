<?php

if(!is_array($config))
{
    $config = array();
}

if(!defined('CACHE_PHP')) { define('CACHE_PHP', substr(strrchr(__FILE__, "."), 1), true); }

/*** Cache variables */
$config['cache']['directory'] = 'cache/';
$config['cache'][0] = 'cache/';
$config['cache']['time'] = 31556926;
$config['cache']['prefix'] = '_time';
$config['cache']['merge'] = '_merged';
$config['cache']['default'] = '<?php exit; ?>';
$config['cache']['delim'] = '--#--';
$config['cache']['extension'] = CACHE_PHP;
$config['cache']['line'] = "\n";
$config['cache']['syntax'] = '((\'%s\'))@((\'%s\'))';
$config['cache']['syntax_type'] = '%s';
$config['cache']['blank'] = 'blank';

?>