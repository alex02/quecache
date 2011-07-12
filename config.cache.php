<?php

if(!is_array($config))
{
    $config = array();
}

/*** Cache variables */
$config['cache']['directory'] = 'cache/';
$config['cache'][0] = 'cache/';
$config['cache']['time'] = 31556926;
$config['cache']['prefix'] = '.time';
$config['cache']['merge'] = '_merged';
$config['cache']['default'] = '<?php /* QueCache */ exit; ?>';
$config['cache']['delim'] = '--#--';
$config['cache']['extension'] = substr(strrchr(__FILE__, "."), 1);
$config['cache']['line'] = "\n";

?>