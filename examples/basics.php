<?php

/*
 * Hi, here you can learn the basic Que cache functions
 * Listed functions: Get, Put, Exists, Destroy
 * @author Alex Emilov Georgiev
 *
 * FUNCTIONS
 *
 * Put:
 *    Cache names are not case-sensitive
 *    Cache values can be mixed (arrays, object require serialization)
 *    Cache time can be mixed (3600 | +1 hour)
 *
 *    Auto serialization is turned off
 *    Only Array-based caches have serialization
 *
 */
  
@ob_start('nl2br');
include('/../class.cache.php');
$cache = new QueCache();

if($cache->put('InnerText', 'My value', 3600)) {

    if($cache->exists('InnerText')) {
         
        echo $cache->get('InnerText') . "\n";
        
    } else {
    
        echo "Cache InnerText is not saved or its expired.\n";
        
    }
    
}

$cache->put('Foo', 'Bar', '+1 hour');
echo 'Cache "Foo" should expire at: ' . date('d.m.Y g:i:s', $cache->gettime('Foo')) . "\n";

// Save Temporary cache for 1 second
$cache->put('Temporary', 'File', 1);

// List it

if($cache->exists('Temporary')) {
    echo "Cache \"Temporary\" is here.\n";
} else {
    echo "Cache \"Temporary\" is expired.\n";
}

// Wait two seconds

sleep(2);

// Try again

if($cache->exists('Temporary')) {
    echo "Cache \"Temporary\" is here.\n";
} else {
    echo "Cache \"Temporary\" is expired.\n";
}

// Another example

$cache->put('Temporary2', 'File2', 3600);

// Destroy the cache
$cache->destroy('Temporary2');

echo "The value of \"Temporary2\" is:\n";
var_dump($cache->get('Temporary2'));