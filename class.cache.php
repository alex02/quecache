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
    public $options = array();
    
    function __construct($atts = null)
    {
        $this->options = array(
            'cache_dir'   =>    'cache/',
            'cache_time'    =>    31556926,
            'cache_extension'   =>    substr(strrchr(__FILE__, "."), 1),
            'cache_default'   =>    '<?php /* QueCache */ exit; ?>',
            'cache_merge'   =>    '_merged',
            'cache_delim'   =>    '--#--',
            'newline'   =>    PHP_EOL,
        );
        
        if( is_array($atts) )
        {
            $this->options = array_merge($this->options, $atts);
        }
        
        $this->options['cache_slash'] =  ($this->options['cache_dir'][strlen($this->options['cache_dir'])-1] == '/') ? '' : '/';
    }
    /*
     * Checks if cache exists
     * by key name and/or dir
     *
     * $param string $val
     * $param string $dir
     *
     * @return boolean
     *
     */
    
    public function exists($key, $directory = null)
    {
        $dir = (is_dir($directory)) ? $directory : $this->options['cache_dir'];
        $this->options['cache_slash'] = $this->dir2slash($dir);
        
        if(file_exists($dir . "{$this->options['cache_slash']}{$key}." . $this->options['cache_extension']))  
        {
            $output = file_get_contents($dir . "{$this->options['cache_slash']}{$key}." . $this->options['cache_extension']);
            $output = str_replace($this->options['cache_default'], '', $output);
            $output = preg_replace('/[^0-9]+/', '', $output);
            $output = substr($output, 0, 10);
            
            $this->options['cache_slash'] =  $this->dir2slash(null);
            if((integer) $output >= time() || $output == 0)
            {
                return true;
            }
            return false;
        }
        return false;  
    }
    
    /*
     * Checks if the cache
     * is expired
     *
     * @param string $key
     * @param boolean $listAny
     *
     * @return boolean
     *
     */
    
    public function expire($key, $directory = null)
    {
        if($this->put($key, $this->get($key), -time()+1, $directory))
        {
            return true;
        }
        
        return false;
    }
    
    /*
     * Stores value
     * into cache
     *
     * @param string $key
     * @param string $value
     * @param string|integer $time
     * @param string $dir
     *
     */
    
    public function put($key, $value, $time = '', $directory = null)
    {
        $dir = ($directory) ? $directory : $this->options['cache_dir'];
        $this->options['cache_slash'] = $this->dir2slash($dir);
        
        if(!is_dir($dir))
        {
            @mkdir($dir, 0770);
        }
        
        $time = (!empty($time)) ? $time : $this->options['cache_time'];
        $time = (!is_int($time)) ? strtotime($time)-time() : $time;
        $time = time()+$time;

        $prepared_value = <<<QCS
{$this->options['cache_default']}
$time{$this->options['newline']}$value
QCS;

        $this->options['cache_slash'] =  $this->dir2slash(null);
        if(@file_put_contents($dir . "{$this->options['cache_slash']}{$key}." . $this->options['cache_extension'], $prepared_value))
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
     * @param string $key
     * @param string $value
     * @param string $mode (key|name|time|timing)
     * @param boolean $appendNew
     * @param string $directory
     * @param boolean $any
     *
     * @return boolean
     *
     */
    
    public function update($key, $value, $mode = 'key', $appendNew = false, $directory = null, $any = false)
    {
        $dir = (is_dir($directory)) ? $directory : $this->options['cache_dir'];
        $this->options['cache_slash'] = $this->dir2slash($dir);
        
        if($this->exists($key) || !! $any === true)
        {
            switch($mode)
            {
                default:
                case 'name':
                case 'key':
       
                $prepared_value = @file_get_contents($dir . "{$this->options['cache_slash']}{$key}." . $this->options['cache_extension']);
                $prepared_value = str_replace($this->options['cache_default'] . $this->options['newline'], '', $prepared_value);
                $prepared_value = preg_replace('/[^0-9]+/', '', $prepared_value, 1);
                $prepared_value = substr($prepared_value, 0, 10);
                
                if(!! $appendNew === true)
                {
                    $value = $this->get($key) . $value;
                }
                
                $prepared_value = <<<QCS
{$this->options['cache_default']}
{$prepared_value}{$this->options['newline']}{$value}
QCS;
           
                if(@file_put_contents($dir . "{$this->options['cache_slash']}{$key}." . $this->options['cache_extension'], $prepared_value))
                {
                    $this->options['cache_slash'] =  $this->dir2slash(null);
                    return true;
                } else {
                    $this->options['cache_slash'] =  $this->dir2slash(null);
                    return false;
                }
         
                break;
                
                case 'time':
                case 'timing':
                
                $value = (!empty($value)) ? $value : $this->options['cache_time'];
                $value = (!is_int($value)) ? strtotime($value)-time() : $value;
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
                if(@file_put_contents($dir . "{$this->options['cache_slash']}{$key}" . "." . $this->options['cache_extension'], $prepared_value))
                {
                    $this->options['cache_slash'] =  $this->dir2slash(null);
                    return true;
                } else {
                    $this->options['cache_slash'] =  $this->dir2slash(null);
                    return false;
                }
                
                break;
            }
            $this->options['cache_slash'] =  $this->dir2slash(null);
            return false;
        }
        $this->options['cache_slash'] =  $this->dir2slash(null);
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
    
    public function get($key, $getAny = false, $directory = null) 
    {
        $dir = (is_dir($directory)) ? $directory : $this->options['cache_dir'];
        $this->options['cache_slash'] = $this->dir2slash($dir);
        
        if($this->exists($key, $dir) || !! $getAny === true)
        {
            $cache_output = @file_get_contents($dir . "{$this->options['cache_slash']}{$key}." . $this->options['cache_extension']);
            $cache_output = str_replace($this->options['cache_default'], '', $cache_output);
            $cache_output = preg_replace('/(' . $this->options['newline'] . '[0-9]+' . $this->options['newline'] . ')/', '', $cache_output, 1);
            
            $this->options['cache_slash'] =  $this->dir2slash(null);
            return $cache_output;
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
        
        if($this->exists($key, $dir))
        {
            $cache_output_time = @file_get_contents($dir . "{$this->options['cache_slash']}{$key}" . "." . $this->options['cache_extension']);
            $cache_output_time = str_replace($this->options['cache_default'], '', $cache_output_time);
            $cache_output_time = preg_replace('/[^' . $this->options['newline'] . '[0-9]+' . $this->options['newline'] . ']/', '', $cache_output_time, 1);
            
            $this->options['cache_slash'] =  $this->dir2slash(null);
            return (integer) $cache_output_time;
        }
        return;
    }
    
    /*
     * Destroy single or specific caches
     *
     * @param string $key
     * @param boolean $del_zero
     * @param string $prefix
     * @param string $directory
     *
     * @return boolean
     *
     */
    
    public function destroy($key, $del_zero = false, $prefix = '', $directory = null)
    {
        $dir = (is_dir($directory)) ? $directory : $this->options['cache_dir'];
        $this->options['cache_slash'] = $this->dir2slash($dir);
       
        if($prefix == '' || $prefix == null || $prefix == false)
        {
            if($this->exists($key, $dir))
            {
               if($this->gettime($key) !== 0 || ($this->gettime($key) == 0 && !! $del_zero === true))
               {
                   @unlink($dir . "{$this->options['cache_slash']}{$key}." . $this->options['cache_extension']);
               }
               $this->options['cache_slash'] =  $this->dir2slash(null);
               return true;
            }
            $this->options['cache_slash'] =  $this->dir2slash(null);
            return false;
        } else {
            foreach($this->asarray($prefix, $dir, ((!! $del_zero === true) ? true : false)) as $keyName)
            {
                if($this->gettime($keyName) !== 0 || ($this->gettime($keyName) == 0 && !! $del_zero === true))
                {
                    @unlink($dir . "{$this->options['cache_slash']}{$keyName}." . $this->options['cache_extension']);
                    continue;
                }
            }
            $this->options['cache_slash'] =  $this->dir2slash(null);
            return true;
        }
        $this->options['cache_slash'] =  $this->dir2slash(null);
        return false;
    }
    
    /*
     * Makes cache with
     * zero time.Can't
     * be deleted by default
     *
     * @param string $key
     * @param string $directory
     *
     * @return boolean
     *
     */
    
    public function makezero($key, $directory = null)
    {
        $dir = (is_dir($directory)) ? $directory : $this->options['cache_dir'];
        $this->options['cache_slash'] = $this->dir2slash($dir);
        
        if($this->put($key, $this->get($key), -time(), $dir))
        {
            $this->options['cache_slash'] =  $this->dir2slash(null);
            return true;
        }
        $this->options['cache_slash'] =  $this->dir2slash(null);
        return false;
    }
    
    /*
     * Merge several caches
     * into one via array
     *
     * @param string $merge_key
     * @param array $ary
     * @param string|integer $time
     *
     * @return boolean
     *
     */
    
    public function merge($merge_key, $ary, $time = '')
    {
        $array = array();
        $array_merge = array();
     
        foreach($ary as $num => $name)
        {
            if($this->exists($name))
            {
                $array[$num] = $this->get($ary[$num]);
                $array_imp = implode($this->options['newline'], $array);
          
                $array_merge[$num] = $this->get($ary[$num]);
                $array_imp_merge = implode($this->options['cache_delim'], $array_merge);
            }
        }
        
        if($this->put($merge_key . $this->options['cache_merge'], $array_imp_merge, $time))
        {
            return true;
        }
        return false;
    }
    
    /*
     * Returns merged part
     * of cache via array
     *
     * @param string $key
     * @param integer $start
     *
     * @return string
     *
     */
    
    public function getmerge($key, $start = null)
    {
        if($this->exists($key))
        {
        
            if(preg_match('/' . $this->options['cache_delim'] . '/', $this->get($key . $this->options['cache_merge'])))
            {
            
                if($start === null)
                {
                    return $this->get($key . $this->options['cache_merge']);
                }
           
                $merge = preg_split('/' . $this->options['cache_delim'] . '/', $this->get($key . $this->options['cache_merge']));
       
                return $merge[$start];
            }
            return;
        }
        return;
    }
    
    /*
     * Return array with key names
     * by keyword/s or regex
     *
     * @param string|integer $expr
     * @param boolean $listAll
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
            if($name == '.' || $name == '..') continue;
            
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
        
        $this->options['cache_slash'] =  $this->dir2slash(null);
        return (array) $asarry;
    }
    
    /*
     * Insert multiple caches
     * by array
     *
     * @param array $array
     *
     * @return boolean
     *
     */
    
    public function multiput($array)
    {
        if(!is_array($array))
        {
            return false;
        }
        
        foreach($array as $key => $content)
        {
            if(is_array($content))
            {
                if($this->put($key, $content[0], $content[1]))
                {
                    continue;
                }
            }
            elseif($this->put($key, $content))
            {
                continue;
            }
        }
        return true;
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
            if($name == '.' || $name == '..') continue;
            
            if(is_dir($dir . $name))
            {
                $this->removeall($dir . $name . $this->options['cache_slash']);
                continue;
            }
            
            if(isset($name) && !preg_match('/' . $this->options['cache_extension'] . $this->options['cache_slash'], $name))
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
                if(!$this->exists($name, $dir) && $this->gettime($name, $dir) !== 0)
                {
                    @unlink($dir . "{$this->options['cache_slash']}{$name}." . $this->options['cache_extension']);
                }
            }
        }
        $this->options['cache_slash'] =  $this->dir2slash(null);
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
            if($name == '.' || $name == '..') continue;
            
            if(is_dir($dir . $name))
            {
                $this->removeall($dir . $name . $this->options['cache_slash']);
                continue;
            }
            
            if(!preg_match('/' . $this->options['cache_extension'] . $this->options['cache_slash'], $name))
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
                @unlink($dir . "{$this->options['cache_slash']}{$name}." . $this->options['cache_extension']);
            }
        }
        $this->options['cache_slash'] =  $this->dir2slash(null);
        return true;
    }
    
    /*
     * Restores expired cache
     *
     * @param string $keys
     * @param boolean $updateAll
     * @param string $directory
     *
     * @return boolean
     *
     */
    
    public function restore($keys = null, $updateAll = false, $directory = null)
    {
        $dir = (is_dir($directory)) ? $directory : $this->options['cache_dir'];
        $this->options['cache_slash'] = $this->dir2slash($dir);
        
        if(empty($keys) || $keys == null)
        {
            foreach($this->asarray(null, $dir, (!! $updateAll === true) ? true : false) as $cachekey)
            {
                if(file_exists(((is_dir($directory)) ? $this->options['cache_dir'] . $directory : $this->options['cache_dir']) . $this->options['cache_slash'] . $cachekey . '.' . $this->options['cache_extension']) && !$this->exists($cachekey))
                {
                    if($this->makezero($cachekey, $dir))
                    {
                        continue;
                    }
                }
            }
            $this->options['cache_slash'] =  $this->dir2slash(null);
            return true;
        }
        
        if(file_exists($dir . $this->options['cache_slash'] . $keys . '.' . $this->options['cache_extension']))
        {
            if(!! $updateAll === false)
            {
                if(!$this->exists($keys, $dir))
                {
                    if($this->makezero($keys, $dir))
                    {
                        $this->options['cache_slash'] =  $this->dir2slash(null);
                        return true;
                    }
                    $this->options['cache_slash'] =  $this->dir2slash(null);
                    return false;
                }
            } else {
                if($this->makezero($keys, $dir))
                {
                    $this->options['cache_slash'] =  $this->dir2slash(null);
                    return true;
                }
                $this->options['cache_slash'] =  $this->dir2slash(null);
                return false;
            }
            $this->options['cache_slash'] =  $this->dir2slash(null);
            return false;
        }
        $this->options['cache_slash'] =  $this->dir2slash(null);
        return false;
    }
    
    /*
     * Simple private function
     * to find out if the directory
     * needs a slash at its end
     *
     * @param string $dir
     *
     * @return string
     *
     */
    private function dir2slash($dir = null)
    {
        if($dir)
        {
            return ( $dir[strlen($dir)-1] == '/' ) ? '' : '/';
        } else {
            return ( $this->options['cache_dir'][strlen($this->options['cache_dir'])-1] == '/' ) ? '' : '/';
        }
    }
}
