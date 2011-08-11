<?php

 /*
  * Hi, here you can learn how to clear cache with Que Cache
  * Using it its easy!
  * Listed functions: Put, Destroy, Purge, Removeall
  *
  */
  
  /*
   * You can always change the whole cache directory via changing the constructor
   * $cache = new QueCache(array('cache_dir' => 'cache/core/new'));
   * Now when you call $cache->put('Some', 'thing', 3600); it will save it in cache/core/new
   * You don't need to append path every time
   *
   */
  
  include('/../class.cache.php');
  $cache = new QueCache();
  
  $cache->put('InOtherDir', 'Value', 3600, 'cache/some/other/dir');
  
  if($cache->destroy('InOtherDir', 'cache/some/other/dir')) {
      echo 'done';
  } else {
      echo 'not done';
  }
  
  // Removes expired files (per selected dir)
  $cache->purge();
  
  // Removes all cache files (per selected dir)
  // $cache->removeall();