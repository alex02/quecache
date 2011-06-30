<?php

    /**
    * Que Cache class
    *
    * Simple, flexible and powerful caching system for you.
    *
    * @package Que Cache
    * @version 2.0
    * @copyright Copyright (c) 2011 Alex Emilov Georgiev
    * @license http://www.gnu.org/licenses/gpl.html GNU GPL
    */

    class QueCache
    {
        /*
         * Stores geteach and cuteach array values
         */
        public $key = array();
        public $names = array();
        
        /*
         *
         * Check if cache exists
         *
         * $param string $val
         * $param string $dir
         * @return boolean
         *
         */
        
        public function exists($val, $dir = null)  
        {
            global $config;
            
            $directory = ($dir !== null) ? $dir : $config['cache']['directory'];
            if(file_exists($directory . "/{$val}" . $config['cache']['prefix'] . "." . $config['cache']['extension']) && file_exists($directory . "/{$val}." . $config['cache']['extension']))  
            {
                $output = file_get_contents($directory . "/{$val}" . $config['cache']['prefix'] . "." . $config['cache']['extension']);
                $output = str_replace($config['cache']['default'], '', $output);
                $output = preg_replace('/' . $config['cache']['line'] . '/', '', $output, 1);
                if((int) $output >= time() || $output == 0)
                {
                    return true;
                }
                return false;
            }
            return false;  
        }
        
        public function put($key, $val, $timed = '', $dir = null)
        {
            global $config;
            
            if(!is_dir($config['cache']['directory']))
            {
                mkdir($config['cache']['directory'], 0770);
            }
            
            $timed = (!empty($timed)) ? $timed : $config['cache']['time'];
            $timed = (!is_int($timed)) ? strtotime($timed)-time() : $timed;

            $value = $config['cache']['default'];
            $value .= $config['cache']['line'] . $val;

            $time_needed = time()+$timed;
            $time = $config['cache']['default'];
            $time .= $config['cache']['line'] . $time_needed;
            
            $directory = ($dir !== null) ? $dir : $config['cache']['directory'];
            
            if(file_put_contents($directory . "/{$key}." . $config['cache']['extension'], $value) && (file_put_contents($directory . "/{$key}" . $config['cache']['prefix'] . "." . $config['cache']['extension'], $time)))
            {
                return true;
            }
            
            return false;
        }
        
        public function update($key, $val, $mode = 'key', $any = false)
        {
            global $config;
            if($this->exists($key) || (bool) $any === true)
            {
                switch($mode)
                {
                    case 'key':
                    default:
           
                    $value = $config['cache']['default'];
                    $value .= $config['cache']['line'] . $val;
               
                    if(file_put_contents($config['cache']['directory'] . "/{$key}." . $config['cache']['extension'], $value))
                    {
                        return true;
                    } else {
                        return false;
                    }
             
                    break;
                    
                    case 'time':
          
                    $time_needed = time()+$val;
                    $time = $config['cache']['default'];
                    $time .= $config['cache']['line'] . $time_needed;
               
                    if(file_put_contents($config['cache']['directory'] . "/{$key}" . $config['cache']['prefix'] . "." . $config['cache']['extension'], $time))
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
        
        public function get($key, $dir = null) 
        {
            global $config;
            
            $directory = ($dir !== null) ? $dir : $config['cache']['directory'];
            if($this->exists($key, $directory))
            {
                $cache_output = file_get_contents($directory . "/{$key}." . $config['cache']['extension']);
                $cache_output = str_replace($config['cache']['default'], '', $cache_output);
                $cache_output = preg_replace('/' . $config['cache']['line'] . '/', '', $cache_output, 1);
                
                return $cache_output;
            }
            return;
        }
        
        public function get_time($key)
        {
            global $config;
            if($this->exists($key))
            {
                $cache_output_time = file_get_contents($config['cache']['directory'] . "/{$key}" . $config['cache']['prefix'] . "." . $config['cache']['extension']);
                $cache_output_time = str_replace($config['cache']['default'], '', $cache_output_time);
                $cache_output_time = preg_replace('/' . $config['cache']['line'] . '/', '', $cache_output_time, 1);
         
                return (integer) $cache_output_time;
            }
            return;
        }
        
        public function destroy($key, $del_zero = 0, $dir = null)
        {
           global $config;
           
           $directory = ($dir !== null) ? $dir : $config['cache']['directory'];
           if($this->exists($key, $dir))
            {
                if($this->get_time($key) !== 0 || ($this->get_time($key) == 0 && (bool) $del_zero === true))
                {
                    unlink($directory . "/{$key}." . $config['cache']['extension']);
                    unlink($directory . "/{$key}" . $config['cache']['prefix'] . "." . $config['cache']['extension']);
                }
                return true;
            }
            return false;
        }
        
        public function make_zero($key)
        {
            if($this->update($key, -time(), 'time', true))
            {
                return true;
            }
            return false;
        }
        
        public function merge($merge_key, $ary, $timer = '')
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
            
            if($this->put($merge_key, $array_imp, $timer) && $this->put($merge_key . $config['cache']['merge'], $array_imp_merge, $timer))
            {
                return true;
            }
            return false;
        }
        
        public function is_merged($key)
        {
            global $config;
            
            if(preg_match('/' . $config['cache']['delim'] . '/', $this->get($key)))
            {
                return true;
            }
            return false; 
        }
        
        public function get_merge($key, $start = null)
        {
            global $config;
            
            if($this->exists($key))
            {
            
                if($this->is_merged($key . $config['cache']['merge']))
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
        
        function cuteach($key_name, array $array = array(), $time = '')
          {
              global $config;
              $ary = '';
              foreach($array as $key => $name)
              {
                  $key = (empty($key)) ? trim($config['cache']['blank']) : trim($key);
                  $name = (empty($name)) ? $config['cache']['blank'] : $name;
                  $ary .= sprintf($config['cache']['syntax'], $key, $name);
              }
              
              if($this->put($key_name, $ary, $time))
              {
                  return true;
              }
              return false;
          }
          
        function geteach($key_name)
        {
            global $config;
            
            if(preg_match("/(" . str_replace($config['cache']['syntax_type'], "(.*)", $this->parse_regex($config['cache']['syntax'])) . ")+/", $this->get($key_name)))
            {
                $result = preg_split("/[" . str_replace($config['cache']['syntax_type'], '', $this->parse_regex($config['cache']['syntax'])) . "]+/", $this->get($key_name), -1, PREG_SPLIT_NO_EMPTY);
                
                for($i = 0;$i < sizeof($result);$i++)
                {
                    if($i % 2 == 0)
                    {
                        $this->keys[$i] = $result[$i];
                    } else {
                        $this->names[$i] = $result[$i];
                    }
                }
                return (array) array_combine($this->keys, $this->names);
            }
            return false;
        }
          
        public function alter($key, $value)
        {
            global $config;
            
            if($this->exists($key))
            {
                
                if($this->update($key, $this->get($key) . $value, 'key'))
                {
                    return true;
                }
                return false;
            }
            return false;
        }
        
        public function asarray($expr = '/(.*)/')
        {
            global $config;
           
            $dir = scandir($config['cache']['directory'], 1);
            $asarry = array();
            $i = 0;

            foreach($dir as $name)
            {
                if(!preg_match('/' . $config['cache']['extension'] . '/', $name))
                {
                    unset($name);
                }
                
                if(preg_match('/' . $config['cache']['prefix'] . "." . $config['cache']['extension'] . '/', $name))
                {
                    unset($name);
                }
                
                $name = str_replace('.' . $config['cache']['extension'], '', $name);
                
                if($expr[0] <> '/' && $expr[strlen($expr)-1] <> '/')
                {
                    $expr = '/' . $expr . '/';
                }
                
                if(!preg_match($expr, $name))
                {
                    unset($name);
                }
                
                if(empty($name))
                {
                    unset($name);
                }
                
                $asarry[$i] = $name;
                
                if(empty($asarry[$i]))
                {
                    unset($asarry[$i]);
                }
                
                if(!$this->exists($asarry[$i]))
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
        
        public function purge()
        {
            global $config;
            
            $dir = scandir($config['cache']['directory'], 1);

            foreach($dir as $name)
            {
            
                if(isset($name) && !preg_match('/' . $config['cache']['extension'] . '/', $name))
                {
                    unset($name);
                }
                
                if(isset($name) && preg_match('/' . $config['cache']['prefix'] . "." . $config['cache']['extension'] . '/', $name))
                {
                    unset($name);
                }
                
                if(isset($name))
                {
                    $name = str_replace('.' . $config['cache']['extension'], '', $name);
                }
                
                if(empty($name))
                {
                    unset($name);
                }
                
                if(isset($name))
                {
                    if(!$this->exists($name) && $this->get_time($name) !== 0)
                    {
                        unlink($config['cache']['directory'] . "/{$name}." . $config['cache']['extension']);
                        unlink($config['cache']['directory'] . "/{$name}" . $config['cache']['prefix'] . "." . $config['cache']['extension']);
                    }
                }
            }
            return; 
        }
        
         public function remove_all()
         {
            global $config;
            
            $dir = scandir($config['cache']['directory'], 1);
            
            foreach($dir as $name)
            {
                if(!preg_match('/' . $config['cache']['extension'] . '/', $name))
                {
                    unset($name);
                }
                
                if(isset($name) && preg_match('/' . $config['cache']['prefix'] . "." . $config['cache']['extension'] . '/', $name))
                {
                    unset($name);
                }
                
                if(isset($name))
                {
                    $name = str_replace('.' . $config['cache']['extension'], '', $name);
                }
                
                if(empty($name))
                {
                    unset($name);
                }
                
                if(isset($name))
                {
                    unlink($config['cache']['directory'] . "/{$name}." . $config['cache']['extension']);
                    unlink($config['cache']['directory'] . "/{$name}" . $config['cache']['prefix'] . "." . $config['cache']['extension']);
                }
                
             }
            return;
         }

        public function restore($keys = null)
        {
            global $config;
            if(empty($keys) || $keys == null)
            {
                foreach($this->asarray('/(.*)/') as $cachekey)
                {
                    if(file_exists($config['cache']['directory'] . '/' . $cachekey . '.' . $config['cache']['extension']) && !$this->exists($cachekey))
                    {
                        if($this->make_zero($cachekey))
                        {
                            continue;
                        }
                    }
                }
                return true;
            }
            
            if(file_exists($config['cache']['directory'] . '/' . $keys . '.' . $config['cache']['extension']) && !$this->exists($keys))
            {
                if($this->make_zero($keys))
                {
                    return true;
                }
                return false;
            }
            return false;
        }

        private function parse_regex($string, $spec_symbol = '~')
        {
            global $config;
            
            $str = '';
            $string_array = array();

            for($i = 0;$i < strlen($string);$i++)
            {
                if($string[$i] == $config['cache']['syntax_type'][0])
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
                    if($string_array[$j-1] == $config['cache']['syntax_type'])
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

?>