<?php

  /**
   * Que Cache class
   *
   * Simple, flexible and powerful caching system for you.
   *
   * @package    Que Cache
   * @copyright  Copyright (c) 2011 Alex Emilov Georgiev
   * @license http://www.gnu.org/licenses/gpl.html    GNU GPL
   */

  define('CACHE_PHP_EXTENSION', end(explode(".", __FILE__)));

  class cache
  {

   /**
    * Where to save cache.
    *
    * @var string
    */
    private $cache_dir = './cache';
    
   /**
    * Default cache life, 1 year.
    *
    * @var integer
    */
    private $cache_time = 31556926;
    
   /**
    * Prefix for time cache filename.
    *
    * @var string
    */
    private $cache_prefix = '_time';
    
   /**
    * Prefix for merged caches.
    *
    * @var string
    */
    private $merged_prefix = '_merged';
    
   /**
    * The default code, the start of the cache file.
    * Better don't change it, this code stops the direct access to cache files.
    *
    * @var string
    */
    private $cache_default = '<?php exit; ?>';
    
   /**
    * The delimiter for merged caches.
    *
    * @var string
    */
    private $merge_delim = '--#--';
    
   /**
    * The cache files extension. 
    * Its auto-generated.
    *
    * @var string
    */
    private $cache_extension = CACHE_PHP_EXTENSION;
    
   /**
    * Specify the plugins folder.
    * The place where plugins go.
    * Please add slash at the end.
    *
    * @var string
    */
    private $cache_plugins_dir = './plugins';
    
    /**
    * Plugin prefix.
    *
    * @var string
    */
    private $cache_plugins_prefix = 'plugins';

    /**
     * Public key and names for saving arrays for some functions
     *
     */
    public $key = array();
    
    public $names = array();
    
    /**
     * Special characters that should be replaced from
     * the content for functions get and cut each.
     *
     */
    private $special_chars = array('.', '*', '(', ')', ':', ';', '\\', '/', '%', '`', '~', '\'', '"', '|', '^', '{', '}', '@', '$', '+', '=', '-', '[', ']');
    
    private $qc_special_chars = array('#DOT#', '#ALL#', '#SYMB1#', '#SYMB2#', '#SYMB3#', '#SYMB4#', '#SYMB5#', '#SYMB6#', '#SYMB7#', '#SYMB8#', '#SYMB9#', '#SYMB10#', '#SYMB11#', '#SYMB12#', '#SYMB13#', '#SYMB14#', '#SYMB15#', '#SYMB16#', '#SYMB17#', '#SYMB18#', '#SYMB19#', '#SYMB20#', '#SYMB21#', '#SYMB22#');

    /**
     * Please don't change " to ' for this new_line
     *
     */
    private $new_line = "\n";
    
    /**
     * Setup custom sytax for each cache cut.
     * Better not change this.Use only %s.If you want
     * to save other type of information use other (like %d),
     * but by default %s is good.Use it only twice here.
     * Use this wisely.
     *
     */
    private $each_syntax = '{%s}:{%s}';
    
    private $default_syntax_type = '%s';
    
    /**
     * The blank parameter for null array keys or values
     *
     */
    private $blank = 'blank';

    /**
    * Really simple function to get
    * cache class constants in plugins
    *
    * @param string $key
    *
    */
    protected function constget($key)
    {
        if(isset($this->$key))
        {
            return $this->$key;
        }
        return;
    }

    /*
     * Check if cache file is in place,
     * and it has valid timing.
     *
     * @param string $val
     * @return boolean
     */
     
    public function exists($val)  
    {
        if(file_exists($this->cache_dir . "/{$val}" . $this->cache_prefix . "." . $this->cache_extension) && file_exists($this->cache_dir . "/{$val}." . $this->cache_extension))  
        {
            $output = file_get_contents($this->cache_dir . "/{$val}" . $this->cache_prefix . "." . $this->cache_extension);
            $output = str_replace($this->cache_default, '', $output);
            $output = preg_replace('/' . $this->new_line . '/', '', $output, 1);
            if((int) $output >= time() || $output == 0)
            {
                return true;
            }
            return false;
        }
        return false;  
    }

    /*
     * Saves values in cache file.
     *
     * @param string $key
     * @param string $val
     * @param integer|string $timed
     *
     * $timed uses both string and integer,
     * because instead of the default timing,
     * we can use the strtotime timing method.
     *
     * @return boolean
     */
    public function put($key, $val, $timed = '')
    {
        /**
         * Check for directory, if not try to create it
         * Setup correct permissions, too (0770 +).
         *
         */
        if(!is_dir($this->cache_dir))
        {
            mkdir($this->cache_dir, 0770);
        }
     
        /**
         * The cache details are setup from here
         *
         */
         
        $timed = (!empty($timed)) ? $timed : $this->cache_time;
        $timed = (!is_int($timed)) ? strtotime($timed)-time() : $timed;
  
        $value = $this->cache_default;
        $value .= $this->new_line . $val;
  
        $time_needed = time()+$timed;
        $time = $this->cache_default;
        $time .= $this->new_line . $time_needed;
     
        /**
         * Return :: If saved ? true : false
         *
         */
         
        if(file_put_contents($this->cache_dir . "/{$key}." . $this->cache_extension, $value) && (file_put_contents($this->cache_dir . "/{$key}" . $this->cache_prefix . "." . $this->cache_extension, $time)))
        {
            return true;
        }
        return false;
    }


    /*
     * Update (key or time) values,
     * from selected cache.
     *
     * @param string $key
     * @param integer $val
     * @param string $mode
     * @return boolean
     *
     */
    public function update($key, $val, $mode = 'key')
    {
        if($this->exists($key))
        {
            switch($mode)
            {
                case 'key':
                default:
       
                $value = $this->cache_default;
                $value .= $this->new_line . $val;
           
                if(file_put_contents($this->cache_dir . "/{$key}." . $this->cache_extension, $value))
                {
                    return true;
                } else {
                    return false;
                }
         
                break;
                
                case 'time':
      
                $time_needed = time()+$val;
                $time = $this->cache_default;
                $time .= $this->new_line . $time_needed;
           
                if(file_put_contents($this->cache_dir . "/{$key}" . $this->cache_prefix . "." . $this->cache_extension, $time))
                {
                    return true;
                } else {
                    return false;
                }
                break;
            }
        }
        return false;
    }


    /**
     * Extract value from cached file.
     *
     * @param string $key
     *
     * @return string
     */
    public function get($key) 
    {
       /**
        * If cached, load it
        *
        */
        if($this->exists($key))
        {
            $cache_output = file_get_contents($this->cache_dir . "/{$key}." . $this->cache_extension);
            $cache_output = str_replace($this->cache_default, '', $cache_output);
            $cache_output = preg_replace('/' . $this->new_line . '/', '', $cache_output, 1);
            
            return $cache_output;
        }
        return;
    }
  
    
    /**
     * Extract time value from cached file.
     *
     * @param string $key
     *
     * @return integer
     */
    public function get_time($key)
    {
        if($this->exists($key))
        {
            $cache_output_time = file_get_contents($this->cache_dir . "/{$key}" . $this->cache_prefix . "." . $this->cache_extension);
            $cache_output_time = str_replace($this->cache_default, '', $cache_output_time);
            $cache_output_time = preg_replace('/' . $this->new_line . '/', '', $cache_output_time, 1);
     
            return (integer) $cache_output_time;
        }
        return;
    }


    /**
     * Delete cache file, by key
     *
     * @param string $key
     * @param boolean $del_zero
     *
     * @return boolean
     *
     */
    public function destroy($key, $del_zero = 0)
    {
       if($this->exists($key))
        {
            if($this->get_time($key) !== 0 || ($this->get_time($key) == 0 && (bool) $del_zero === true))
            {
                unlink($this->cache_dir . "/{$key}." . $this->cache_extension);
                unlink($this->cache_dir . "/{$key}" . $this->cache_prefix . "." . $this->cache_extension);
            }
            return true;
        }
        return false;
    }
    
    
    /**
     * Make some cache file with time 0.
     * That means that it can't be delete
     * by default.
     *
     * @param string $key
     *
     * @return boolean
     */
    public function make_zero($key)
    {
        if($this->update($key, -time(), 'time')) /** Default is Nth+time, now -time+time = 0 */
        {
            return true;
        }
        return false;
    }

    /**
     * Merge some cache files in one.
     *
     * @param string $merge_key
     * @param array $ary
     * @param integer or string $timer
     *
     * $timer uses both string and integer,
     * because instead of the default timing,
     * we can use the strtotime timing method.
     *
     * @return boolean
     */
    public function merge($merge_key, $ary, $timer = '')
    {
        /**
         * Setup as arrays
         */
        $array = array();
        $array_merge = array();
     
        foreach($ary as $num => $name)
        {
            if($this->exists($name))
            {
                /**
                 * If exists setup details for merging
                 */
                $array[$num] = $this->get($ary[$num]);
                $array_imp = implode($this->new_line, $array);
          
                $array_merge[$num] = $this->get($ary[$num]);
                $array_imp_merge = implode($this->merge_delim, $array_merge);
            }
        }
        
        /**
         * If merged succesfully then return
         */
        if($this->put($merge_key, $array_imp, $timer) && $this->put($merge_key . $this->merged_prefix, $array_imp_merge, $timer))
        {
            return true;
        }
        return false;
    }

    /**
     * Check if some key is already merged cache.
     *
     * @param string $key
     *
     * @return boolean
     */
    public function is_merged($key)
    {
        /**
         * Simple function to check if the key its cached as merge.
         */
        if(preg_match('/' . $this->merge_delim . '/', $this->get($key)))
        {
            return true;
        }
        return false; 
    }
    
    /**
     * Return values of merged cache.
     *
     * @param string $key
     * @param integer $start
     *
     * @return array or string
     *
     * Return values depend if
     * $start parameter is specified
     *
     * if its not specified
     *
     * @return array
     *
     * if specified
     *
     * @return string
     *
     */
    public function get_merge($key, $start = null)
    {
        if($this->exists($key))
        {
            /**
             * If merged, we don't want to extract unmerged caches.
             */
            if($this->is_merged($key . $this->merged_prefix))
            {
               /**
                * If null return whole array, use print_r($cache->get_merge('key'))
                */
                if(is_null($start))
                {
                    return $this->get($key . $this->merged_prefix);
                }
               /**
                * Split this array and return what we want, by number.
                */
           
                $merge = preg_split('/' . $this->merge_delim . '/', $this->get($key . $this->merged_prefix));
       
                return $merge[$start];
            }
            return;
        }
        return;
    }
    
    /**
     * Cut cache file into pieces
     *
     * @param string $key
     * @param string $piece
     *
     * @return array
     */
    public function cut($key, $piece)
    {
       /**
        * Cut only if exists & return pieces.
        */
        if($this->exists($key))
        {
            return explode($piece, $this->get($key));
        }
        return;
    }
    
    /**
     * This function is similar to cache::cut, but instead of cutting it with auto-numeric key [0], [1], [2] key
     * it sets a key name defined by you and then its easy to recognise and use. [your_name], [your_key], [other_key]
     *
     * @param string $key_name
     * @param array $array
     * @param string | integer $time
     *
     * @return boolean
     *
     */
    function cuteach($key_name, array $array = array(), $time = '')
      {
          $ary = '';
          foreach($array as $key => $name)
          {
              $key = (empty($key)) ? $this->blank : $key;
              $name = (empty($name)) ? $this->blank : $name;
              $name = str_replace($this->special_chars, $this->qc_special_chars, $name);
              $ary .= sprintf($this->each_syntax, $key, $name);
          }
          
          if($this->put($key_name, $ary, $time))
          {
              return true;
          }
          return false;
      }
      
      /**
       * This is a special function that should be used only from cache::cuteach.The prevous function only does the
       * content saving and this makes the cutting and returning of the values.
       *
       * @param string $keyname
       *
       * @return string | boolean
       *
       */
      function geteach($key_name)
      {
          if(preg_match("/[" . $this->parse_regex($this->each_syntax) . "]+/", $this->get($key_name)))
          {
              $result = preg_split("/[" . $this->parse_regex($this->each_syntax) . "]+/", $this->get($key_name), -1, PREG_SPLIT_NO_EMPTY);
              for($i = 0;$i < sizeof($result);$i++)
              {
                  if($i % 2 == 0)
                  {
                      $result[$i] = str_replace($this->qc_special_chars, $this->special_chars, $result[$i]);
                      $this->keys[$i] = $result[$i];
                  } else {
                      $result[$i] = str_replace($this->qc_special_chars, $this->special_chars, $result[$i]);
                      $this->names[$i] = $result[$i];
                  }
              }
              return (array) array_combine($this->keys, $this->names);
          }
          return false;
      }
    
    /**
     * Include content into cached file, 
     * without replacing the old content.
     *
     * @param string $key
     * @param string $val
     * @param string $method
     *
     * @return boolean
     */
    public function alter($key, $val, &$method = '')
    {
        if($this->exists($key))
        {
            /**
             * Method should not be changed.
             * See cache::update for reference.
             * Parsed as not needed param and
             * as unchangeable value.
             * 
             */
            $method = 'key';
            
            /**
             * Return true if new cache file is updated.
             *
             */
            if($this->update($key, $this->get($key) . $val, $method))
            {
                return true;
            }
            return false;
        }
        return false;
    }
    
   /**
    * Return found cache keys,
    * as array, by some keyword.
    *
    * @param string $expr
    * @param string $asarry
    *
    * @return array
    */
    public function asarray($expr, &$asarry = '')
    {
       
       /**
        * Scan the files in this dir
        *
        */
        $dir = scandir($this->cache_dir, 1);
        $asarry = array();
        $i = 0;
 
        foreach($dir as $name)
        {
           /**
            * Remove if its not php file
            *
            */
            if(!preg_match('/' . $this->cache_extension . '/', $name))
            {
                unset($name);
            }
      
           /**
            * Remove time prefixes from names
            *
            */
            if(preg_match('/' . $this->cache_prefix . "." . $this->cache_extension . '/', $name))
            {
                unset($name);
            }
      
           /**
            * Replace php extension from filename
            *
            */
            $name = str_replace('.' . $this->cache_extension, '', $name);
            
           /**
            * If not regex, regex it.
            *
            */
            if($expr[0] <> '/' && $expr[strlen($expr)-1] <> '/')
            {
                $expr = '/' . $expr . '/';
            }
            
           /**
            * Remove if no match.
            *
            */
            if(!preg_match($expr, $name))
            {
                unset($name);
            }
            
           /**
            * Remove empty sets
            *
            */
            if(empty($name))
            {
                unset($name);
            }
            
           /**
            * Match in new array
            *
            */
            $asarry[$i] = $name;
            
           /**
            * Only useful values for our array.
            *
            */
            if(empty($asarry[$i]))
            {
                unset($asarry[$i]);
            }
            
           /**
            * List only active caches.
            *
            */
            if(!$this->exists($asarry[$i]))
            {
                unset($asarry[$i]);
            }
            
           /**
            * If matches, count it.
            * With that the new array has correct key.
            *
            */
            if(isset($asarry[$i]))
            {
                $i++;
            }
        }
        
        return (array) $asarry;
    }
    
    /**
     * Insert multiple caches with one function.
     *
     * @param array $array
     *
     * @return boolean
     *
     */
    public function multiput($array)
    {
        /**
         * If not array stop.
         *
         */
        if(!is_array($array))
        {
            return false;
        }
        
        /**
         * List values and insert them
         *
         */
        foreach($array as $key => $content)
        {
            /**
             * If timing is specified then we should insert as array
             * or just put content with default timing ($cache->cache_time).
             *
             */
            if(is_array($content))
            {
                if($this->put($key, $content[0], $content[1]))
                {
                    continue;
                }
            } elseif($this->put($key, $content)) {
                continue;
            }
        }
        return true;
    }

    /**
     * Delete all expired caches.
     *
     * @return empty
     */
    public function purge()
    {
        /**
         * Scan the files in this dir
         */
        $dir = scandir($this->cache_dir, 1);
 
        foreach($dir as $name)
        {
           /**
            * Remove if its not php file
            *
            */
            if(isset($name) && !preg_match('/' . $this->cache_extension . '/', $name))
            {
                unset($name);
            }
      
           /**
            * Remove time prefixes from names
            *
            */
            if(isset($name) && preg_match('/' . $this->cache_prefix . "." . $this->cache_extension . '/', $name))
            {
                unset($name);
            }
      
           /**
            * Replace php extension from filename
            *
            */
            if(isset($name))
            {
                $name = str_replace('.' . $this->cache_extension, '', $name);
            }
     
           /**
            * Remove empty sets
            *
            */
            if(empty($name))
            {
                unset($name);
            }
            
            if(isset($name))
            {
                if(!$this->exists($name) && $this->get_time($name) !== 0)
                {
                    unlink($this->cache_dir . "/{$name}." . $this->cache_extension);
                    unlink($this->cache_dir . "/{$name}" . $this->cache_prefix . "." . $this->cache_extension);
                }
            }
        }
        return; 
    }
     
     /**
     * Deletes all cache files.
     *
     *
     * @return empty
     */
     public function remove_all()
     {
       /**
        * Scan the files in this dir
        *
        */
        $dir = scandir($this->cache_dir, 1);
        
        foreach($dir as $name)
        {
            /**
             * Remove if its not php file
             */
            if(!preg_match('/' . $this->cache_extension . '/', $name))
            {
                unset($name);
            }
      
            /**
             * Remove time prefixes from names
             */
            if(isset($name) && preg_match('/' . $this->cache_prefix . "." . $this->cache_extension . '/', $name))
            {
                unset($name);
            }
      
            /**
             * Replace php extension from filename
             */
            if(isset($name))
            {
                $name = str_replace('.' . $this->cache_extension, '', $name);
            }
     
            /**
             * Remove empty sets
             */
            if(empty($name))
            {
                unset($name);
            }
            
            if(isset($name))
            {
                unlink($this->cache_dir . "/{$name}." . $this->cache_extension);
                unlink($this->cache_dir . "/{$name}" . $this->cache_prefix . "." . $this->cache_extension);
            }
            
         }
        return;
     }

    private function parse_regex($string, $spec_symbol = '~', &$type = '%s')
    {

        $str = '';
        $type = $this->default_syntax_type;
        $string_array = array();

        for($i = 0;$i < strlen($string);$i++)
        {
            if($string[$i] == $type[0])
            {
                $string_array[] = $string[$i] . $string[$i+1];
                $string[$i+1] = $spec_symbol;
            } else {
                $string_array[] = str_replace($string[$i], '\\' . $string[$i], $string[$i]);
            }
        }
        for($j = 0;$j < sizeof($string_array);$j++)
        {
            if($string_array[$j] == '\\' . $spec_symbol)
            {
                if($string_array[$j-1] == $type)
                {
                    unset($string_array[$j]);
                }
            }
        }

        foreach($string_array as $str_symbol)
        {
            $str .= $str_symbol;
        }

        return $str;
    }

  }
  
  class plugin extends cache
  {
  
      function __construct($object)
      {
          if(is_array($object))
          {
              foreach($object as $key => $name)
              {
                  $this->plugin_setup($object[$key]);
                  if($this->plugin_exists($object[$key]))
                  {
                      $this->plugin_init($object[$key]);
                      $this->$object[$key] = new $object[$key]();
                      $this->destroy('_' . parent::constget('cache_plugins_prefix') .  '__' . $object[$key], 1);
                  }
              }
          } else {
              $this->plugin_setup($object);
              if($this->plugin_exists($object))
              {
                  $this->plugin_init($object);
                  $this->$object = new $object();
                  $this->destroy('_' . parent::constget('cache_plugins_prefix') .  '__' . $object, 1);
              }
          }
      }
      
     /**
      * Activate the plugin.
      *
      * @param string $name
      * @param boolean $active
      *
      * @return boolean
      *
      */
      public function plugin_setup($name)
      {
          $plugin = parent::constget('cache_plugins_dir') . '/' . $name . '.' . parent::constget('cache_extension');
          if($this->put('_' . parent::constget('cache_plugins_prefix') . '__' . $name, 1, -time()))
          {
              return true;
          }
          return false;
      }
      
     /**
      * Listing all valid plugins
      *
      * @return array
      *
      */
      public function plugin_check()
      {
          $dir = scandir(parent::constget('cache_plugins_dir'), 1);
          $dir_arry = array();
 
          foreach($dir as $name)
          {
              /**
               * Remove if its not php file
              */
              if(isset($name) && !preg_match('/' . parent::constget('cache_extension') . '/', $name))
              {
                  unset($name);
              }
      
              /**
              * Remove time prefixes from names
              */
              if(isset($name) && preg_match('/' . parent::constget('cache_prefix') . "." . parent::constget('cache_extension') . '/', $name))
              {
                  unset($name);
              }
      
             /**
              * Replace php extension from filename
              *
              */
              if(isset($name))
              {
                  $name = str_replace('.' . parent::constget('cache_extension'), '', $name);
              }
     
             /**
              * Remove empty sets
              *
              */
              if(empty($name))
              {
                  unset($name);
              }
              
              if(isset($name))
              {
                  $dir_arry[] = $name;
              }
          }
          return (array) $dir_arry;
      }
      
     /**
      * Before using the plugin, we should check if its valid.
      *
      * @param string $name
      *
      * @return boolean
      */
      public function plugin_exists($name)
      {
      
          if(file_exists(parent::constget('cache_plugins_dir') . '/' . $name . '.' . parent::constget('cache_extension')))
          {
              $plugin_content = file_get_contents(parent::constget('cache_plugins_dir') . '/' . $name . '.' . parent::constget('cache_extension'));
              if(preg_match('/\s?\s?\s?if\(!class_exists\(\'(cache)\'\)\)\s?\s?\s?\{?\s?\s?\s?\s?\s?\s?\s?(exit|die)?(\((.*)\))?\;?\s?\s?\s?\}?/', $plugin_content))
              {
                  if($this->get('_' . parent::constget('cache_plugins_prefix') . '__' . $name) == 1)
                  {
                      return true;
                  }
                  return false;
              }
              return false;
          }
          return false;
      }
    
     /**
      * Simple checking if plugin is activated.
      *
      * @param string $name
      *
      * @return boolean
      */
      public function plugin_status($name)
      {
          return ((bool) $this->get('_' . parent::constget('cache_plugins_prefix') . '__' . $name) === true) ? true : false;
      }
      
     /**
      * Prepare plugin for using
      *
      * @param string $name
      *
      * @return null
      */
      public function plugin_init($name)
      {
          if($this->plugin_status($name))
          {
              require_once(parent::constget('cache_plugins_dir') . '/' . $name . '.' . parent::constget('cache_extension'));
              return true;
          }
          return false;
      }
  }

?>