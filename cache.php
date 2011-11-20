<?php

/**
* Que Cache class
*
* Simple, flexible and powerful caching system for php
*
* @package Que Cache
* @version 2.3
* @author Alex Emilov Georgiev <alexemilov1@gmail.com> <quecache.org>
* @license Dual license http://www.gnu.org/licenses/gpl.html GNU GPL || http://www.opensource.org/licenses/mit-license.php MIT
*/

class QueCache
{
    public $options, $iterator;
    
    function __construct($options = null)
    {
      // Default options
      $this->options = array(
        'cache_dir'       =>  'cache/',
        'cache_time'      =>  60 * 60 * 24 * 365,
        'cache_extension' =>  substr(strrchr(__FILE__, "."), 1),
        'cache_default'   =>  '<?php /* QueCache */ exit; ?>',
        'newline'         =>  PHP_EOL,
      );
      
      // Use dir iteration for cache directory
      $this->iterator = new DirectoryIterator($this->options['cache_dir']);
      
      // Is isset custom values, apply them.
      if(isset($options) && sizeof($options))
      {
        $this->options = array_merge($this->options, $options);
      }
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
    public function exists($var)
    {
      // Checks if cache file exists
      if($this->is_file($var))  
      {
        // Get cache
        $output = $this->get_cache($var, 0);
        // Remove default values, before removing non-integer values, because this may containt 0-9.
        $output = str_replace($this->options['cache_default'], '', $output);
        // Remove any non-integer values.
        $output = preg_replace('/[^0-9]+/', '', $output);
        // Get only time.
        $output = substr($output, 0, 10);
        
        // The actual check
        if((int) $output >= time())
        {
          return true;
        }
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
    public function put($key, $value, $time = '')
    {
      // Try to make (nested) directory if needed.
      if(!is_dir($this->options['cache_dir']))
      {
        @mkdir($this->options['cache_dir'], 0770, true);
      }
      
      // Here is where nested timing tries to apply.
      // If no time, try to use default cache time.
      $time = (empty($time)) ? $this->options['cache_time'] : $time;
      // If not integer, its strtotime (maybe).
      $time = (is_int($time)) ? $time : strtotime($time) - time();
      // Get the time
      $time = time() + $time;
      
      // Regex to match all chunked arrays
      // Match {} as well
      preg_match_all('/[\[|\{]([a-zA-Z0-9_"\']+)?[\]|\}]{1,}/', $key, $keys, PREG_PATTERN_ORDER);
      
      // Perform only if needed
      if(preg_match('/^\b(.+)\b[\[|\{]([a-zA-Z0-9_"\']+)?[\]|\}]/', $key, $chunks))
      {
          $chunks = array_map(array($this, 'clean_name'), $chunks);
          $key = &$chunks[1];
          $this_array = (is_array($this->get($key))) ? $this->get($key) : array();
          
          // If we have chunks
          if(isset($chunks[2]))
          {
            $subkeys = $keys[1];
            // Cleans(filters) the names, security check
            $subkeys = array_map(array($this, 'clean_name'), $subkeys);
            
            // Actually I don't remembed whats up here.
            if(empty($subkeys[sizeof($subkeys) - 1]))
            {
              $filtered_subkeys = $subkeys;
              array_pop($filtered_subkeys);
              
              if(is_array($this_array) && isset($this_array[$subkeys[0]]))
              {
                $subchunk_key = $this->format_array($this_array, $filtered_subkeys);
              }
              else
              {
                $subchunk_key = array();
              }
              
              $value = array_merge_recursive($subchunk_key, array($value));
            }
            else
            {
              $value = array($subkeys[sizeof($subkeys)-1] => $value);
            }
            
            for($i = sizeof($subkeys) - 2;$i >= 0;$i--)
            {
              $value = array($subkeys[$i] => $value);
            }
          }
          else
          {
            $value = array_merge($this_array, array($value));
          }
          
          if(is_array($this_array))
          {
            $value = serialize($this->parse_arrays($this_array, $value));
          }
          else
          {
            $value = serialize($value);
          }
      }
      
      // Another check
      if(is_array($value) || is_object($value))
      {
        $value = @serialize($value);
      }
      
      // Prepare value.
      $prepared_value = <<<QCS
{$this->options['cache_default']}
$time{$this->options['newline']}$value
QCS;
      
      // API save
      return $this->save_cache($key, $prepared_value);
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
    public function update($key, $value, $append = false, $mode = 'key')
    {
      // Check first if exists.
      if($this->exists($key))
      {
        switch($mode)
        {
          default:
          case 'name':
          case 'key':
          
          $prepared_value = $this->get_cache($key, 1);
          $prepared_value = str_replace($this->options['cache_default'] . $this->options['newline'], '', $prepared_value);
          $prepared_value = preg_replace('/[^0-9]+/', '', $prepared_value, 1);
          $prepared_value = (integer) substr($prepared_value, 0, 10);
          
          $data = $this->get($key);
          if($append)
          {
            if(is_array($value))
            {
              if(!is_array($data))
              {
                $data = array($data);
              }
              $data = $data + $value;
              $value = @serialize($data);
            }
            else
            {
              if(is_array($data))
              {
                $value = array($value);
                $value = $data + $value;
                $value = @serialize($value);
              }
              else
              {
                $value = $this->get($key) . $value;
              }
            }
          }
          else
          {
            if(is_array($value))
            {
              $value = @serialize($value);
            }
          }
          
          $prepared_value = <<<QC
{$this->options['cache_default']}
{$prepared_value}{$this->options['newline']}{$value}
QC;
          
          return $this->save_cache($key, $prepared_value);
     
          break;
          
          case 'time':
          case 'timing':
          
          $value = (empty($value)) ? $this->options['cache_time'] : $value;
          $value = (is_int($value)) ? $value : strtotime($value) - time();
          $value = time() + $value;
          
          if($append)
          {
            $value_old = (integer) $this->gettime($key) - time();
            $value += $value_old;
          }
          
          $prepared_value = $this->get_cache($key, 1);
          $prepared_value = str_replace($this->options['cache_default'] . $this->options['newline'], '', $prepared_value);
          $prepared_value = preg_replace('/[0-9]+' . $this->options['newline'] . '/', '', $prepared_value, 1);
          
          $prepared_value = <<<QCS
{$this->options['cache_default']}
{$value}{$this->options['newline']}{$prepared_value}
QCS;
          return $this->save_cache($key, $prepared_value);
            
          break;
        }
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
    public function get($key) 
    {
      // Regex to match all chunked arrays
      preg_match_all('/\[([a-zA-Z0-9_"\']+)?\]/', $key, $keys, PREG_PATTERN_ORDER);
      
      if(preg_match('/^\b(.+)\b\[([a-zA-Z0-9_"\']+)?\]/', $key, $chunks))
      {
        $key = &$this->clean_name($chunks[1]);
        $keys = array_map(array($this, 'clean_name'), $keys);
      }
      
      $cache_output = $this->get_cache($key, 1);
      $cache_output = str_replace($this->options['cache_default'], '', $cache_output);
      $cache_output = preg_replace('/(\s)?(\s[0-9]+\s)(\s)?/', '', $cache_output, 1);
      
      if(sizeof($chunks))
      {
        $cache_output = unserialize($cache_output);
        
        return $this->format_array($cache_output, $keys[1], false);
      }
      
      return (@unserialize($cache_output) === false) ? $cache_output : unserialize($cache_output);
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
    
    public function gettime($key)
    {
      if(preg_match('/^\b(.+)\b\[([a-zA-Z0-9_"\']+)?\]/', $key, $chunks))
      {
        $key = &$this->clean_name($chunks[1]);
        unset($chunks);
      }
      
      $output = $this->get_cache($key, 1);
      $output = str_replace($this->options['cache_default'], '', $output);
      $output = preg_replace('/[^0-9]/', '', $output);
      $output = substr($output, 0, 10);
      
      return (int) $output;
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
    public function destroy($key)
    {
      if($this->exists($key))
      {
        $this->delete_cache($key);
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
    public function asarray($expr = '/(.*)/')
    {
        $data = array();
        
        foreach($this->iterator as $name)
        {
          if($name->isDot() || $name->isDir())
          {
            continue;
          }
          
          if(!preg_match('/' . $this->options['cache_extension'] . '/', $name))
          {
            continue;
          }
          
          $name = str_replace(".{$this->options['cache_extension']}", '', $name);
          
          if($expr[0] <> '/' && $expr[strlen($expr) - 1] == '/')
          {
            $expr = '/' . $expr;
          } else if($expr[0] == '/' && $expr[strlen($expr) - 1] <> '/')
          {
            $expr .= '/';
          } else if($expr[0] <> '/' && $expr[strlen($expr) - 1] <> '/')
          {
            $expr = '/' . $expr . '/';
          }
          
          if(!preg_match($expr, $name) || empty($name))
          {
            continue;
          }
          
          if($this->exists($name))
          {
            $data[] = $name;
          }
        }
        
        return $data;
    }
    
    /*
     * Delete all expired cache files
     *
     * @return boolean
     *
     */
    public function tidy()
    {
      foreach($this->iterator as $name)
      {
        if($name->isDot() || $name->isDir())
        {
          continue;
        }
        
        if(!preg_match('/' . $this->options['cache_extension'] . '/', $name))
        {
          continue;
        }
        
        $name = str_replace(".{$this->options['cache_extension']}", '', $name);
        
        if(!$this->exists($name) && $this->is_file($name) && !empty($name))
        {
          $this->delete_cache($name);
        }
      }
      
      return true; 
    }
    
    /*
     * Delete all caches by directory
     * nevermind if they are expired or not
     *
     * @return boolean
     *
     */
    public function purge()
    {
      foreach($this->iterator as $name)
      {
        if($name->isDot() || $name->isDir())
        {
          continue;
        }
        
        if(!preg_match('/' . $this->options['cache_extension'] . '/', $name))
        {
          continue;
        }
        
        $name = str_replace(".{$this->options['cache_extension']}", '', $name);
        
        if($this->is_file($name) && !empty($name))
        {
          $this->delete_cache($name);
        }
      }
      
      return true;
    }
    
    /*
     * Simple private function to clean special symbols from cache keys
     *
     * @param string $data
     *
     * @return string
     */
    private function clean_name($data)
    {
      return preg_replace('/\W+/', '', $data);
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
        if(isset($array[$key]))
        {
          if(is_array($array[$key]) && is_array($value))
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
    private function format_array($base, $indexes, $arry = true)
    {
      foreach($indexes as $key)
      {
        if(isset($base[$key]))
        {
          $base = $base[$key];
        }
      }
      
      if($arry)
      {
        return (is_array($base)) ? $base : array();
      }
      else
      {
        return $base;
      }
    }
    
    /*
    * API function to save the cache
    *
    * @param string $var
    * @param mixed $data
    *
    * @return boolean
    *
    */
    private function save_cache($var, $data)
    {
      // Checks if dir exists.
      if($this->iterator->isDir())
      {
        // Tries to save the data.
        $path = $this->options['cache_dir'] . DIRECTORY_SEPARATOR . "{$var}." . $this->options['cache_extension'];
        @file_put_contents($path, $data);
      }
      
      return true;
    }
    
    /*
    * API function for getting cache
    *
    * @param string $var The cache key(name)
    * @param boolean $nocheck If not specified the getting should auto-check if the cache is existing.
    *
    * @reutrn string
    *
    */
    private function get_cache($var, $nocheck = 1)
    {
      // Read @param $nocheck
      if($nocheck)
      {
        // Checks if cache is here
        if($this->exists($var))
        {
          // Try go get it
          $path = $this->options['cache_dir'] . "{$var}." . $this->options['cache_extension'];
          
          return @file_get_contents($path);
        }
      }
      else
      {
        // Try to get the cache without checking if exists
        // Should be called only when is_file() is checked before this.
        $path = $this->options['cache_dir'] . "{$var}." . $this->options['cache_extension'];
          
        return @file_get_contents($path);
      }
      
      return '';
    }
    
    private function delete_cache($var)
    {
      // Deletes the cache
      if(file_exists($path = $this->options['cache_dir'] . $this->options['cache_slash'] . "{$var}." . $this->options['cache_extension']))
      {
        @unlink($path);
        
        return true;
      }
      
      return false;
    }
    
    // Checks if file exists.
    private function is_file($var)
    {
      return @file_exists($this->options['cache_dir'] . "{$var}." . $this->options['cache_extension']);
    }
}
