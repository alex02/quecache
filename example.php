<?php

/**
 * Que Cache
 * @version 2.0
 */

include('config.cache.php');
include('class.cache.php');
$cache = new QueCache();

// Store content in cache
// For one hour
$cache->put('welcome', 'Hello world', 3600);
// Store info for the default cache time in class_cache.php
// By default its 1 year.
$cache->put('COOKIE_PATH', './tmp/cookies/');
// Store in some easy for unsterstand way, see strtotime at php.net
$cache->put('username', 'Que Cache User', '+1 week 3 days 2 hours');

?>
<style type="text/css">
p { max-width: 300px; }
</style>
<h3><?= $cache->get('welcome'); ?></h3>
Hello <?= $cache->get('username'); ?>.<br />
The temporary cookie path is <?= $cache->get('COOKIE_PATH'); ?>
<br />
<h3>Lets store something and delete it</h3>
<p>
** Note **<br />
When making some cache with zero timing is by default un-deletable.
But when we add second parameter true for cache::destroy it will delete
zeroed cache files.
</p>
<?php

// Example for saving, zeroing and destroying cache
// Make_zero isn't required, but it can be handy when
// You don't want to lose some cache data.

$cache->put('store1', 'is here!');
echo 'Added: ' . $cache->get('store1') . "<br />";
$cache->make_zero('store1');
echo 'Zeroed: ' . $cache->get('store1') . "<br />";
$cache->destroy('store1');
echo 'Deleted (default): ' . $cache->get('store1') . "<br />";
$cache->destroy('store1', true);
echo 'Deleted (extended): ' . $cache->get('store1') . "<br />";

?>
<h3>Exists or not &amp; PHP source</h3>
<?php

// This example shows the cache exists function and
// Shows that saving in php file a php source won't conflict with result.
// Also the _time end prefix for cache won't conflict with names.

$cache->put('Some_CacheFOO_file_cache_time', '<?php echo "ok"; ?>');

if($cache->exists('Some_CacheFOO_file_cache_time'))
{
    echo $cache->get('Some_CacheFOO_file_cache_time');
}

?>
<i>View source :-) (ctrl+u)</i>
<h3>Merging content</h3>
<?php

$cache->put('some1', 'Person #1');
$cache->put('some2', 'Person #2');
$cache->put('some3', 'Person #3');

$cache->merge('all_persons', array('some1', 'some2', 'some3'));

?>
<p>
** Note **<br />
We've saved 3 cache files (some1, some2, some3) and we want to merge them with
all_persons.The we return by number.Starts from 0 (like arrays) and we want to get some2.
</p>
Result: <?= $cache->get_merge('all_persons', 1); ?>
<h3>Serializing cache</h3>
<?php

$array = array(
    'name'    =>    'Guest',
    'location'    =>    'checking this example source code',
    'ip'    =>    'hidden',
);

if($cache->put('user_details', serialize($array)))
{
    $results = unserialize($cache->get('user_details'));
}

?>
Results:
<br />
You are <?= $results['name']; ?>, <?= $results['location']; ?><br />
IP: <?= $results['ip']; ?>
<h3>Returning time of cache</h3>
<?php

$cache->put('SomeCache', 'Content', 3600);

?>
Cache 'SomeCache' should expire at <?= date('d.m.Y g:i', $cache->get_time('SomeCache')); ?> (after one hour)
<h3>Update and alter</h3>
<?php

$cache->put('Time', 'OK', 3600);
echo "Before: " . date('g:i', $cache->get_time('Time'));
echo "<br />";
// Update timing to + one hour
$cache->update('Time', 7200, 'time');
echo "After: " . date('g:i', $cache->get_time('Time'));
echo "<br /><hr />";
echo "Before: " . $cache->get('Time');
echo "<br />";
$cache->alter('Time', '_NEW'); // Append _NEW to OK, OK_NEW
echo "After: " . $cache->get('Time');

?>
<h3>As array</h3>
<p>
** Note **<br />
Return array of cache keys with specific keywords or regex.
</p>
<pre>
<?php print_r($cache->asarray('some')); ?>
</pre>