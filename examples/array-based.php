<?php

 /*
  * Hi, here you can learn how to use the array-based cache in Que Cache
  * Its quite simple, like using arrays in PHP, supports [] and {} with ", ' or without
  * Listed functions: Put, Get
  *
  */
  
  include('/../class.cache.php');
  $cache = new QueCache();
  
  $cache->put('temp[]', 1);
  $cache->put('temp[some]', 2);
  $cache->put('temp[simple][array]', 3);
  $cache->put('temp[based][cache][test][]', 4);
  $cache->put('temp["mixed"][\'twice\'][]', 5);
  $cache->put('temp{simple}{twice}', 6);
  $cache->put('temp[this]', 7);
  
  echo '<pre>';
  print_r(unserialize($cache->get('temp')));
  echo '</pre>';
  
  echo '<br />';
  echo 'Temp[this]: ' . $cache->get('temp[this]'); // 7