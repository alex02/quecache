<?php

  /**
   * Simple cache class - Example plugin
   *
   * Simple, flexible and powerful caching system for you.
   *
   * @package    Simple cache plugin Example plugin
   * @copyright  Copyright (c) 2011 Alex Emilov Georgiev
   * @license http://www.gnu.org/licenses/gpl.html    GNU GPL
   */
  
  if(!class_exists('cache'))
  {
      exit;
  }
  
  class test_addon extends cache
  {
      
      public function test_it($key)
      {
          return parent::get($key);
      }
  }

?>