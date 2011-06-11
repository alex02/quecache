<style type="text/css">
span { font-weight: bold; }
span#fail { color: red; }
span#recommended { color: blue; }
span#success { color: green; }
h5 { padding: 0; margin: 0; }
</style>
<h3>Que Cache Tests</h3>
<span>PHP 5:</span>
<?php

if (version_compare(PHP_VERSION, '5.0.0', '>=')) {
    echo "<span id=\"success\">Success</span>";
} else {
    echo "<span id=\"fail\">Fail</span>";
}

echo "<br />";

echo "<span>Is cache directory writable: </span>";
if(is_dir('./cache') && is_writable('./cache'))
{
  echo "<span id=\"success\">Success</span>";
} else {
  echo "<span id=\"fail\">Fail</span>";
}


echo "<br />";

echo "<span>Cache directory permissions: </span>";
$perms = substr(sprintf('%o', fileperms('./cache')), -3);
if($perms)
{
    if($perms <= 770)
    {
        echo "<span id=\"fail\">Fail</span>";
    } else {
        echo "<span id=\"success\">Success</span>";
    }
    if($perms <> 770 && $perms >= 770)
    {
        echo " | <span id=\"recommended\">Recommended: 770 (current {$perms}) </span>";
    }
} else {
    echo "<span id=\"fail\">Fail</span>";
}

echo "<br />";

echo "<span>Is plugins directory writable: </span>";
if(is_writable('./cache'))
{
  echo "<span id=\"success\">Success</span>";
} else {
  echo "<span id=\"fail\">Fail</span>";
}


echo "<br />";

echo "<span>Plugins directory permissions: </span>";
$permsp = substr(sprintf('%o', fileperms('./cache')), -3);
if($permsp)
{
    if($permsp <= 770)
    {
        echo "<span id=\"fail\">Fail</span>";
    } else {
        echo "<span id=\"success\">Success</span>";
    }
    if($permsp <> 770 && $permsp >= 770)
    {
        echo " | <span id=\"recommended\">Recommended: 770 (current {$permsp}) </span>";
    }
} else {
    echo "<span id=\"fail\">Fail</span>";
}

?>