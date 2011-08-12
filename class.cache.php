<?php

/**
* Que Cache class
*
* Simple, flexible and powerful caching system for php
*
* @package Que Cache
* @version 2.1
* @author Alex Emilov Georgiev <alexemilov1@gmail.com>
* @license Dual license http://www.gnu.org/licenses/gpl.html GNU GPL || http://www.opensource.org/licenses/mit-license.php MIT
*/

class QueCache
{
    public $options;
    
    function __construct($atts = null)
    {
        $this->options = array(
            'cache_dir'       =>  'cache/',
            'cache_time'      =>  60*60*24*365,
            'cache_extension' =>  substr(strrchr(__FILE__, "."), 1),
            'cache_default'   =>  '<?php /* QueCache */ exit; ?>',
            'newline'         =>  PHP_EOL,
        );
        
        if( isset($atts) && is_array($atts) )
        {
            $this->options = array_merge($this->options, $atts);
        }
        
        $this->options['cache_slash'] = $this->dir2slash(null);
    }
    
    /*
     * Checks if cache exists by key name and/or dir
     *
     * $param string $key The cache key name to check
     * $param string $dir The directory where the cache should be
     *
     * @return boolean
     *
     */
    public function exists($key, $directory = null)
    {
        $dir = (is_dir($directory)) ? $directory : $this->options['cache_dir'];
        $this->options['cache_slash'] = $this->dir2slash($dir);
        
        if(file_exists($dir . $this->options['cache_slash'] . $key . '.' . $this->options['cache_extension']))  
        {
            $output = file_get_contents($dir . $this->options['cache_slash'] . $key . '.' . $this->options['cache_extension']);
            $output = str_replace($this->options['cache_default'], '', $output);
            $output = preg_replace('/[^0-9]+/', '', $output);
            $output = substr($output, 0, 10);
            
            if((integer) $output >= time())
            {
                return true;
            }
            return false;
        }
        return false;  
    }
    
    /*
     * Stores value into cache
     *
     * @param string $key The cache key
     * @param string $value The cache value
     * @param string|integer $time The cache time
     * @param string $directory The cache directory where to store cache
     *
     */
    public function put($key, $value, $time = '', $directory = null)
    {
        $dir = ($directory) ? $directory : $this->options['cache_dir'];
        $this->options['cache_slash'] = $this->dir2slash($dir);
        
        if(!is_dir($dir))
        {
            @mkdir($dir, 0770, true);
        }
        
        $time = (empty($time)) ? $this->options['cache_time'] : $time;
        $time = (is_int($time)) ? $time : strtotime($time)-time();
        $time = time()+$time;
        
        // Regex to match all chunked arrays
        // Match {} as well
        preg_match_all('/[\[|\{]([a-zA-Z0-9_"\']+)?[\]|\}]{1,}/', $key, $keys, PREG_PATTERN_ORDER);
        unset($keys[0]);
        
        if(preg_match('/^\b(.+)\b[\[|\{]([a-zA-Z0-9_"\']+)?[\]|\}]/', $key, $chunks))
        {
            $chunks = array_map(array(&$this, 'clean_name'), $chunks);
            $key =& $chunks[1];
            $this_array = (is_array(unserialize($this->get($key)))) ? unserialize($this->get($key)) : array();
            
            if(isset($chunks[2]))
            {
                $subkeys = $keys[1];
                $subkeys = array_map(array(&$this, 'clean_name'), $subkeys);
                
                if(empty($subkeys[sizeof($subkeys)-1]))
                {
                    $filtered_subkeys = $subkeys;
                    array_pop($filtered_subkeys);
                    
                    if(is_array($this_array) && isset($this_array[$subkeys[0]]))
                    {
                        $subchunk_key = $this->format_array($this_array, $filtered_subkeys);
                    } else {
                        $subchunk_key = array();
                    }
                    
                    $value = array_merge_recursive($subchunk_key, array($value));
                } else {
                    $value = array($subkeys[sizeof($subkeys)-1] => $value);
                }
                
                for($i = sizeof($subkeys)-2;$i >= 0;$i--)
                {
                    $value = array($subkeys[$i] => $value);
                }
            } else {
                $value = array_merge($this_array, array($value));
            }
            
            if(is_array($this_array))
            {
                $value =& serialize($this->parse_arrays($this_array, $value));
            } else {
                $value =& serialize($value);
            }
        }

        $prepared_value = <<<QCS
{$this->options['cache_default']}
$time{$this->options['newline']}$value
QCS;
        
        if(@file_put_contents($dir . $this->options['cache_slash'] . $key . '.' . $this->options['cache_extension'], $prepared_value))
        {
            return true;
        }
        
        return false;
    }
    
    /*
     * Updates cache value
     * or time without altering
     * the other part.
     * * May change the value
     * and stay with the same timing
     * and vice versa
     *
     * @param string $key The cache key
     * @param string $value The cache value
     * @param string $mode (key|name|time|timing) Update method
     * @param boolean $appendNew Should the value be appended to the old one or not (useful for time)
     * @param string $directory Where the cache should be
     *
     * @return boolean
     *
     */
    public function update($key, $value, $mode = 'key', $appendNew = false, $directory = null)
    {
        $dir = (is_dir($directory)) ? $directory : $this->options['cache_dir'];
        $this->options['cache_slash'] = $this->dir2slash($dir);
        
        if($this->exists($key))
        {
            switch($mode)
            {
                default:
                case 'name':
                case 'key':
       
                $prepared_value = @file_get_contents($dir . $this->options['cache_slash'] . $key . '.' . $this->options['cache_extension']);
                $prepared_value = str_replace($this->options['cache_default'] . $this->options['newline'], '', $prepared_value);
                $prepared_value = preg_replace('/[^0-9]+/', '', $prepared_value, 1);
                $prepared_value = (integer) substr($prepared_value, 0, 10);
                
                if(!! $appendNew === true)
                {
                    $value = $this->get($key) . $value;
                }
                
                $prepared_value = <<<QCS
{$this->options['cache_default']}
{$prepared_value}{$this->options['newline']}{$value}
QCS;
           
                if(@file_put_contents($dir . $this->options['cache_slash'] . $key . '.' . $this->options['cache_extension'], $prepared_value))
                {
                    return true;
                } else {
                    return false;
                }
         
                break;
                
                case 'time':
                case 'timing':
                
                $value = (empty($value)) ? $this->options['cache_time'] : $value;
                $value = (is_int($value)) ? $value : strtotime($value)-time();
                $value = time()+$value;
                
                if(!! $appendNew === true)
                {
                    $value_old = (integer) $this->gettime($key)-time();
                    $value += $value_old;
                }
                
                $prepared_value = @file_get_contents($dir . "{$this->options['cache_slash']}{$key}." . $this->options['cache_extension']);
                $prepared_value = str_replace($this->options['cache_default'] . $this->options['newline'], '', $prepared_value);
                $prepared_value = preg_replace('/[0-9]+' . $this->options['newline'] . '/', '', $prepared_value, 1);
                
                $prepared_value = <<<QCS
{$this->options['cache_default']}
{$value}{$this->options['newline']}{$prepared_value}
QCS;
                if(@file_put_contents($dir . $this->options['cache_slash'] . $key . '.' . $this->options['cache_extension'], $prepared_value))
                {
                    return true;
                } else {
                    return false;
                }
                
                break;
            }
            return false;
        }
        return false;
    }
    
    /*
     * Return cache value
     *
     * @param string $key
     * @param boolean $getAny
     * @param string $directory
     *
     * @return string
     *
     */
    public function get($key, $directory = null) 
    {
        $dir = (is_dir($directory)) ? $directory : $this->options['cache_dir'];
        $this->options['cache_slash'] = $this->dir2slash($dir);
        
        // Regex to match all chunked arrays
        preg_match_all('/\[([a-zA-Z0-9_"\']+)?\]/', $key, $keys, PREG_PATTERN_ORDER);
        
        if(preg_match('/^\b(.+)\b\[([a-zA-Z0-9_"\']+)?\]/', $key, $chunks))
        {
            $key =& $this->clean_name($chunks[1]);
            $keys = array_map(array(&$this, 'clean_name'), $keys);
        }
        
        if($this->exists($key, $dir))
        {
            $cache_output = @file_get_contents($dir . "{$this->options['cache_slash']}{$key}." . $this->options['cache_extension']);
            $cache_output = str_replace($this->options['cache_default'], '', $cache_output);
            $cache_output = preg_replace('/(\s[0-9]+\s)(\s)?/', '', $cache_output, 1);
            
            if( sizeof($chunks) )
            {
                $cache_output = unserialize($cache_output);
                return $this->format_array($cache_output, $keys[1], false);
            } else {
                return $cache_output;
            }
        }
        return;
    }
    
    /*
     * Return cache time left
     *
     * @param string $key
     * @param string $directory
     *
     * @return integer
     *
     */    
    
    public function gettime($key, $directory = null)
    {
        $dir = (is_dir($directory)) ? $directory : $this->options['cache_dir'];
        $this->options['cache_slash'] = $this->dir2slash($dir);
        
        if(preg_match('/^\b(.+)\b\[([a-zA-Z0-9_"\']+)?\]/', $key, $chunks))
        {
            $key =& $this->clean_name($chunks[1]);
            unset($chunks);
        }
        
        if($this->exists($key, $dir))
        {
            $output = @file_get_contents($dir . $this->options['cache_slash'] . $key . "." . $this->options['cache_extension']);
            $output = str_replace($this->options['cache_default'], '', $output);
            $output = preg_replace('/[^0-9]/', '', $output);
            $output = substr($output, 0, 10);
            
            return (integer) $output;
        }
        return;
    }
    
    /*
     * Destroy single cache
     * This function deletes the file
     *
     * @param string $key
     * @param boolean $del_zero
     * @param string $prefix
     * @param string $directory
     *
     * @return boolean
     *
     */
    public function destroy($key, $directory = null)
    {
        $dir = (is_dir($directory)) ? $directory : $this->options['cache_dir'];
        $this->options['cache_slash'] = $this->dir2slash($dir);
        
        if($this->exists($key, $dir))
        {
            if(file_exists($dir . $this->options['cache_slash'] . $key . '.' . $this->options['cache_extension'])) {
                @unlink($dir . $this->options['cache_slash'] . $key . '.' . $this->options['cache_extension']);
                return true;
            }
            return false;
        }
        return false;
    }
    
    /*
     * Return array with key names by keyword/s or regex
     *
     * @param mixed $expr The regular expression or keyword
     * @param string $directory The cache directory where the cache should be
     *
     * @return array
     *
     */
    public function asarray($expr = '/(.*)/', $directory = null, $listAll = false)
    {
        $i = 0;
        $asarry = array();
        $dir = (is_dir($directory)) ? $directory : $this->options['cache_dir'];
        $this->options['cache_slash'] = $this->dir2slash($dir);

        foreach(scandir($dir, 1) as $name)
        {
            if($name == '.' || $name == '..') {
                continue;
            }
            
            if(is_dir($dir . $name))
            {
                $asarry[$name] = $this->asarray($expr, $dir . $name . $this->options['cache_slash']);
                continue;
            }
            
            if(!preg_match('/' . $this->options['cache_extension'] . '/', $name))
            {
                unset($name);
                continue;
            }
            
            $name = str_replace('.' . $this->options['cache_extension'], '', $name);
            
            if($expr[0] <> '/' && $expr[strlen($expr)-1] == '/')
            {
                $expr = '/' . $expr;
            } elseif($expr[0] == '/' && $expr[strlen($expr)-1] <> '/') {
                $expr .= '/';
            } elseif($expr[0] <> '/' && $expr[strlen($expr)-1] <> '/') {
                $expr = '/' . $expr . '/';
            }
            
            if(!preg_match($expr, $name))
            {
                unset($name);
                continue;
            }
            
            if(empty($name))
            {
                unset($name);
                continue;
            }
            
            $asarry[$i] = $name;
            
            if(!$this->exists($asarry[$i], $dir) && !! $listAll === false)
            {
                unset($asarry[$i]);
            }
            
            if(isset($asarry[$i]))
            {
                $i++;
            }
        }
        
        return $asarry;
    }
    
    /*
     * Delete all expired cache files
     *
     * @param string $directory
     *
     * @return boolean
     *
     */
    public function purge($directory = null)
    {
        $dir = (is_dir($directory)) ? $directory : $this->options['cache_dir'];
        $this->options['cache_slash'] = $this->dir2slash($dir);

        foreach(scandir($dir, 1) as $name)
        {
            if($name == '.' || $name == '..') {
                continue;
            }
            
            if(is_dir($dir . $name))
            {
                $this->purge($dir . $name . $this->options['cache_slash']);
                continue;
            }
            
            if(isset($name) && !preg_match('/' . $this->options['cache_extension'] . '/', $name))
            {
                unset($name);
                continue;
            }
            
            if(isset($name))
            {
                $name = str_replace('.' . $this->options['cache_extension'], '', $name);
            }
            
            if(empty($name))
            {
                unset($name);
                continue;
            }
            
            if(isset($name))
            {
                if(!$this->exists($name, $dir))
                {
                    if(file_exists($dir . $this->options['cache_slash'] . $name . '.' . $this->options['cache_extension'])) {
                        @unlink($dir . $this->options['cache_slash'] . $name . '.' . $this->options['cache_extension']);
                    }
                }
            }
        }
        return true; 
    }
    
    /*
     * Delete all caches by directory
     * nevermind if they are expired or not
     *
     * @param string $directory
     *
     * @return boolean
     *
     */
    public function removeall($directory = null)
    {
        $dir = (is_dir($directory)) ? $directory : $this->options['cache_dir'];
        $this->options['cache_slash'] = $this->dir2slash($dir);
        
        foreach(scandir($dir, 1) as $name)
        {
            if($name == '.' || $name == '..') {
                continue;
            }
            
            if(is_dir($dir . $name))
            {
                $this->removeall($dir . $name . $this->options['cache_slash']);
                continue;
            }
            
            if(isset($name) && !preg_match('/' . $this->options['cache_extension'] . '/', $name))
            {
                unset($name);
                continue;
            }
            
            if(isset($name))
            {
                $name = str_replace('.' . $this->options['cache_extension'], '', $name);
            }
            
            if(!isset($name))
            {
                unset($name);
                continue;
            }
            
            if(isset($name))
            {
                if(file_exists($dir . $this->options['cache_slash'] . $name . '.' . $this->options['cache_extension'])) {
                    @unlink($dir . $this->options['cache_slash'] . $name . '.' . $this->options['cache_extension']);
                }
            }
        }
        return true;
    }
    
    /*
     * Simple private function to find out if the directory needs a slash at its end
     *
     * @param string $dir
     *
     * @return string
     *
     */
    private function dir2slash($dir = null)
    {
        if( $dir )
        {
            return ( $dir[strlen($dir)-1] == '/' ) ? '' : '/';
        } else {
            return ( $this->options['cache_dir'][strlen($this->options['cache_dir'])-1] == '/' ) ? '' : '/';
        }
    }
    
    /*
     * Simple private function to clean special symbols from cache keys
     *
     * @param string $str
     *
     * @return string
     */
    private function clean_name($str)
    {
        return preg_replace('/\W+/', '', $str);
    }
    
    /*
     * Private function to merge two arrays in correct way and return them both
     *
     * @param array $array The base array
     * @param array $subarray The array that has to be appended in the base array
     *
     * @return array
     */
    private function parse_arrays(&$array, $subarray)
    {
        foreach($subarray as $key => $value)
        {
            if( isset($array[$key]) )
            { 
                if( is_array($array[$key]) && is_array($value) )
                {
                    $this->parse_arrays($array[$key], $value);
                    continue;
                }
            }
            $array[$key] = $value;
        }

        return $array;
    }
    
    /*
     * Private function to format an array to call the current key
     *
     * @param array $base The base array
     * @param array $indexes The base indexes to match
     *
     */
    private function format_array($base, $indexes, $asarray = true)
    {
        foreach( $indexes as $key ) {
            if(isset($base[$key])) {
                $base = $base[$key];
            }
        }
        
        if(!! $asarray === true) {
            return (is_array($base)) ? $base : array();
        } else {
            return $base;
        }
    }
}
