<?php
  
  /*
   * Que Cache
   * 1.1.8
   */
  
  /**
   * Add the cache file
   */
  include('class_cache.php');

  /**
   * Define cache class
   */
  $cache = new cache();
  
 /**
  * cache::put
  * cache::get
  * cache::get_time
  * cache::exists
  * cache::update
  * cache::merge
  * cache::get_merge
  * cache::is_merged
  * cache::asarray
  * cache::make_zero
  * cache::destroy
  * cache::purge
  * cache::remove_all
  * cache::asarray
  * cache::multiput
  * cache::cut
  * cache::cuteach
  * cache::geteach
  * cache::restore
  */

  /**
   * Some sets
   * $cache->cache_dir (default ./cache)
   * $cache->cache_prefix (default _time)
   * $cache->cache_extension (Checks whats the extension of class_cache.php (php, php3..) and applies it to caches.
   * $cache->cache_time (default 31556926)
   *
   */

  /**
   * Some test variables to put cache
   * By default it lasts 1 year.
   *
   */
   
   echo "<h3>Simple usage examples for Que Cache<br /><small>(please ignore the mess and lookup the source)</small></h3>";
  
  echo "<h3>Sample for put content</h3>";
  $cache->put('1hour', '2nd value', 3600);
  $cache->put('string', '3rd value', "+3 days");
  if($cache->put('1year_by_default', '1st value'))
  {
      echo "TRUE";
  } else {
      echo "FALSE";
  }
  
  echo "<h3>Sample for get content</h3>";
 /**
  * Get cache value
  *
  */ 
  echo $cache->get('1year_by_default');
  echo "<br />";
  
  echo "<h3>Sample for get content time</h3>";
  /**
   * Get cache life
   *
   */
  
  echo "Time #1 :: " . $cache->get_time('1year_by_default') . " => " . date("d.m.Y g:i", $cache->get_time('1year_by_default')) . "<br />";
  echo "Time #2 :: " . $cache->get_time('1hour') . " => " . date("d.m.Y g:i", $cache->get_time('1hour')) . "<br />";
  echo "Time #3 :: " . $cache->get_time('string') . " => " . date("d.m.Y g:i", $cache->get_time('string')) . "<br />";
  
  /**
   * If exists test
   *
   */
   
  echo "<h3>Check if content exists</h3>";
  if($cache->exists('1year_by_default'))
  {
  
  echo "2nd value :: exists";
  
  } else {
  
  echo "2nd value :: not exists";
  echo "<br />";
  
  }


  echo "<h3>Make zero test</h3>";
  /**
   * Make zero the 1hour cache file, won't be purged
   * and cache::destory clears it only if its speficied
   * second parameter 1. cache::destroy('1hour', 1);
   *
   */
  $cache->make_zero('1hour');

  if($cache->get_time('1hour') == 0)
  {
  echo "TRUE";
  } else {
  echo "FALSE";
  }
  
  echo "<h3>Merge content</h3>";
  /**
   * Merge some caches and return the 2nd
   *
   */
  $cache->merge('asd', array('1year_by_default', '1hour', 'string'));
  echo $cache->get_merge('asd', 2);
  
  /**
   * Some example merge caching.
   *
   */
  $cache->put('city1', 'Pernik1', 3600);
  $cache->put('city2', 'Pernik2', 3600);
  $cache->put('city3', 'Pernik3', 3600);
  $cache->merge('all_cities', array('city1', 'city2', 'city3'), 3600);
  
  echo "<br />";
  echo $cache->get_merge('all_cities', 2);
    
  /**
   * Return Pernik3
   *
   */
   
  /**
   * Removes a singe cache
   * Since its zero cache, this is the way to delete it.
   *
   */
  $cache->destroy('1hour', 1);
  
  /**
   * Fix in ver1.0.9
   * Test for cache filenames with cache_prefix (_time),
   * please remove the comment to the code down (only first line, $cache->put..) to see the result,
   * for purging, removing files with key as cache_prefix.
   *
   */
  //$cache->put('time_time_test', 'test', 20); /** for 20 seconds */
  echo "<h3>Check if content exists #2</h3>";
  if($cache->exists('time_time_test'))
  {
   echo "exists";
  } else {
   echo "not exists";
  }
  
  /**
   * Inserting new content,
   * into old cache with
   * cache::alter.
   *
   */
   
  echo "<h3>Alter content test</h3>";
  if($cache->put('alter_it', 'test1', 3600) && $cache->alter('alter_it', '::test2'))
  {
      echo "Altered.";
  } else {
      echo "Not altered.";
  }
  
  /**
   * Return matched caches as array.
   *
   */
  
  /**
   * Match cached files with _ in their name.
   *
   */
  echo "<h3>As array test</h3>";
  print_r($cache->asarray('_'));
  echo "<br />";
  
  echo "<h3>Merge return</h3>";
  $cache->put('test1', 'testing..1');
  $cache->put('test2', 'testing..2');
  $cache->put('test3', 'testing..3');
  $cache->merge('testers', array('test1', 'test2' ,'test3'));
  echo $cache->get_merge('testers', 2);
  
  
  /**
   * Plugin usage, for more plugins, use array
   * $cache->plugin = new plugin(array('plugin1', 'plugin2', 'plugin3'));
   *
   */
  echo "<h3>Test plugin usage</h3>";
  $cache->plugin = new plugin(array('test_addon'));
  echo $cache->plugin->test_addon->test_it('test1');
  
  /**
   * The new cuteach and geteach functions
   *
   */
  
  echo "<h3>Cuteach and geteach test</h3>";
  
  $array = array(
      'name'  =>  'Alex',
      'age'   =>  16,
      'from'  =>  'Pernik',
   );
   
   if($cache->cuteach('Each', $array))
   {
      $each = $cache->geteach('Each');
      echo $each['from'];
   }
  
  /**
   * Alternate way to make zero
   * $cache->update('1hour', -time(), 'time');

   * The default way is turn off when cache life is 0.
   * $cache->destroy('1hour', 1);

   * $cache->put('adsa1', 'asddas', 20);

   * Removes all expired caches
   *
   */
  
  $cache->purge();
  
  /**
   * Remove all cached files.
   * $cache->remove_all();
   *
   */
   
?>