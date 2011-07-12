<?php

    /**
    * Que Cache class
    *
    * Simple, flexible and powerful caching system for you.
    *
    * @package Que Cache
    * @version 2.0
    * @copyright Copyright (c) 2011 Alex Emilov Georgiev
    * @license Dual license http://www.gnu.org/licenses/gpl.html GNU GPL || http://www.opensource.org/licenses/mit-license.php MIT
    */

    class QueCache
    {
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
        
        public function exists($key, $dir = null)
        {
            global $config;
            
            $directory = (is_dir($dir)) ? $dir : $config['cache']['directory'];
            
            if(file_exists($directory . "/{$key}" . $config['cache']['prefix'] . "." . $config['cache']['extension']) && file_exists($directory . "/{$key}." . $config['cache']['extension']))  
            {
                $output = file_get_contents($directory . "/{$key}" . $config['cache']['prefix'] . "." . $config['cache']['extension']);
                $output = str_replace($config['cache']['default'], '', $output);
                $output = preg_replace('/[' . $config['cache']['line'] . ']/', '', $output, 1);
                
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
        
        public function expired($key, $listAny = false)
        {
            if(!! $listAny === true)
            {
                if($this->get_time($key, true) >= time())
                {
                    return true;
                }
                return false;
            }
            
            if($this->get_time($key) >= time())
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
            global $config;
            
            $dir = ($directory) ? $directory : $config['cache']['directory'];
            
            if(!is_dir($dir))
            {
                @mkdir($dir, 0770);
            }
            
            $time = (!empty($time)) ? $time : $config['cache']['time'];
            $time = (!is_int($time)) ? strtotime($time)-time() : $time;

            $prepared_value = $config['cache']['default'];
            $prepared_value .= $config['cache']['line'] . $value;

            $time_needed = time()+$time;
            $prepared_time = $config['cache']['default'];
            $prepared_time .= $config['cache']['line'] . $time_needed;
            
            if(@file_put_contents($dir . "/{$key}." . $config['cache']['extension'], $prepared_value) && (@file_put_contents($dir . "/{$key}" . $config['cache']['prefix'] . "." . $config['cache']['extension'], $prepared_time)))
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
         * @param string $directory
         * @param boolean $any
         *
         * @return boolean
         *
         */
        
        public function update($key, $value, $mode = 'key', $directory = null, $any = false)
        {
            global $config;
            
            $dir = (is_dir($directory)) ? $directory : $config['cache']['directory'];
            
            if($this->exists($key) || !! $any === true)
            {
                switch($mode)
                {
                    case 'name':
                    case 'key':
                    default:
           
                    $prepared_value = $config['cache']['default'];
                    $prepared_value .= $config['cache']['line'] . $value;
               
                    if(@file_put_contents($dir . "/{$key}." . $config['cache']['extension'], $prepared_value))
                    {
                        return true;
                    } else {
                        return false;
                    }
             
                    break;
                    
                    case 'time':
                    case 'timing':
          
                    $time_needed = time()+$value;
                    $prepared_time = $config['cache']['default'];
                    $prepared_time .= $config['cache']['line'] . $time_needed;
               
                    if(@file_put_contents($dir . "/{$key}" . $config['cache']['prefix'] . "." . $config['cache']['extension'], $prepared_time))
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
         * @return string|integer|boolean|object|array
         *
         */
        
        public function get($key, $getAny = false, $directory = null) 
        {
            global $config;
            
            $dir= (is_dir($dir)) ? $directory : $config['cache']['directory'];
            
            if($this->exists($key, $dir) || !! $getAny === true)
            {
                $cache_output = @file_get_contents($dir . "/{$key}." . $config['cache']['extension']);
                $cache_output = str_replace($config['cache']['default'], '', $cache_output);
                $cache_output = preg_replace('/' . $config['cache']['line'] . '/', '', $cache_output, 1);
                
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
        
        public function get_time($key, $directory = null)
        {
            global $config;
            
            $dir = (is_dir($directory)) ? $directory : $config['cache']['directory'];
            
            if($this->exists($key, $dir))
            {
                $cache_output_time = @file_get_contents($dir . "/{$key}" . $config['cache']['prefix'] . "." . $config['cache']['extension']);
                $cache_output_time = str_replace($config['cache']['default'], '', $cache_output_time);
                $cache_output_time = preg_replace('/' . $config['cache']['line'] . '/', '', $cache_output_time, 1);
         
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
            global $config;
           
            $dir = (is_dir($dir)) ? $directory : $config['cache']['directory'];
           
            if($prefix == '' || $prefix == null || $prefix == false)
            {
                if($this->exists($key, $dir))
                {
                   if($this->get_time($key) !== 0 || ($this->get_time($key) == 0 && !! $del_zero === true))
                   {
                       @unlink($dir . "/{$key}." . $config['cache']['extension']);
                       @unlink($dir . "/{$key}" . $config['cache']['prefix'] . "." . $config['cache']['extension']);
                   }
                   return true;
                }
                return false;
            } else {
                foreach($this->asarray($prefix, $dir, ((!! $del_zero === true) ? true : false)) as $keyName)
                {
                    if($this->get_time($keyName) !== 0 || ($this->get_time($keyName) == 0 && !! $del_zero === true))
                    {
                        @unlink($dir . "/{$keyName}." . $config['cache']['extension']);
                        @unlink($dir . "/{$keyName}" . $config['cache']['prefix'] . "." . $config['cache']['extension']);
                        continue;
                    }
                }
                return true;
            }
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
        
        public function make_zero($key, $directory = null)
        {
            global $config;
            
            $dir = (is_dir($directory)) ? $directory : $config['cache']['directory'];
            
            if($this->update($key, -time(), 'time', $dir, true))
            {
                return true;
            }
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
            global $config;
            
            $array = array();
            $array_merge = array();
         
            foreach($ary as $num => $name)
            {
                if($this->exists($name))
                {
                    $array[$num] = $this->get($ary[$num]);
                    $array_imp = implode($config['cache']['line'], $array);
              
                    $array_merge[$num] = $this->get($ary[$num]);
                    $array_imp_merge = implode($config['cache']['delim'], $array_merge);
                }
            }
            
            if($this->put($merge_key, $array_imp, $time) && $this->put($merge_key . $config['cache']['merge'], $array_imp_merge, $time))
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
        
        public function get_merge($key, $start = null)
        {
            global $config;
            
            if($this->exists($key))
            {
            
                if(preg_match('/' . $config['cache']['delim'] . '/', $this->get($key . $config['cache']['merge'])))
                {
                
                    if($start === null)
                    {
                        return $this->get($key . $config['cache']['merge']);
                    }
               
                    $merge = preg_split('/' . $config['cache']['delim'] . '/', $this->get($key . $config['cache']['merge']));
           
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
            global $config;
            
            $dir = (is_dir($directory)) ? $directory : $config['cache']['directory'];
            $asarry = array();
            $i = 0;

            foreach(scandir($dir, 1) as $name)
            {
                if(!preg_match('/' . $config['cache']['extension'] . '/', $name))
                {
                    unset($name);
                    continue;
                }
                
                if(preg_match('/' . $config['cache']['prefix'] . "." . $config['cache']['extension'] . '/', $name))
                {
                    unset($name);
                    continue;
                }
                
                $name = str_replace('.' . $config['cache']['extension'], '', $name);
                
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
            global $config;

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
                } elseif($this->put($key, $content)) {
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
            global $config;
            
            $dir = (is_dir($directory)) ? $directory : $config['cache']['directory'];

            foreach(scandir($dir, 1) as $name)
            {
            
                if(isset($name) && !preg_match('/' . $config['cache']['extension'] . '/', $name))
                {
                    unset($name);
                    continue;
                }
                
                if(isset($name) && preg_match('/' . $config['cache']['prefix'] . "." . $config['cache']['extension'] . '/', $name))
                {
                    unset($name);
                    continue;
                }
                
                if(isset($name))
                {
                    $name = str_replace('.' . $config['cache']['extension'], '', $name);
                }
                
                if(empty($name))
                {
                    unset($name);
                    continue;
                }
                
                if(isset($name))
                {
                    if(!$this->exists($name, $dir) && $this->get_time($name, $dir) !== 0)
                    {
                        @unlink($dir . "/{$name}." . $config['cache']['extension']);
                        @unlink($dir . "/{$name}" . $config['cache']['prefix'] . "." . $config['cache']['extension']);
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
        
        public function removeAll($directory = null)
        {
            global $config;
            
            $dir = (is_dir($directory)) ? $directory : $config['cache']['directory'];
            
            foreach(scandir($dir, 1) as $name)
            {
                if(!preg_match('/' . $config['cache']['extension'] . '/', $name))
                {
                    unset($name);
                    continue;
                }
                
                if(isset($name) && preg_match('/' . $config['cache']['prefix'] . "." . $config['cache']['extension'] . '/', $name))
                {
                    unset($name);
                    continue;
                }
                
                if(isset($name))
                {
                    $name = str_replace('.' . $config['cache']['extension'], '', $name);
                }
                
                if(empty($name))
                {
                    unset($name);
                    continue;
                }
                
                if(isset($name))
                {
                    @unlink($dir . "/{$name}." . $config['cache']['extension']);
                    @unlink($dir . "/{$name}" . $config['cache']['prefix'] . "." . $config['cache']['extension']);
                }
            }
            return;
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
            global $config;
            
            $dir = (is_dir($directory)) ? $directory : $config['cache']['directory'];
            
            if(empty($keys) || $keys == null)
            {
                foreach($this->asarray(null, $dir, (!! $updateAll === true) ? true : false) as $cachekey)
                {
                    if(file_exists(((is_dir($directory)) ? $directory : $config['cache']['directory']) . '/' . $cachekey . '.' . $config['cache']['extension']) && !$this->exists($cachekey))
                    {
                        if($this->make_zero($cachekey, $dir))
                        {
                            continue;
                        }
                    }
                }
                return true;
            }
            
            if(file_exists($dir . '/' . $keys . '.' . $config['cache']['extension']))
            {
                if(!! $updateAll === false)
                {
                    if(!$this->exists($keys, $dir))
                    {
                        if($this->make_zero($keys, $dir))
                        {
                            return true;
                        }
                        return false;
                    }
                } else {
                    if($this->make_zero($keys, $dir))
                    {
                        return true;
                    }
                    return false;
                }
                return false;
            }
            return false;
        }
    }
?>