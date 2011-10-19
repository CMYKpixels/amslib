<?php
/*******************************************************************************
 * Copyright (c) {15/03/2008} {Christopher Thomas}
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * file: Amslib_Plugin_Manager2.php
 * title: Antimatter Plugin: Plugin Manager object version 2
 * description: An object to store all the plugins and provide a central method
 * 				to access them all
 * version: 2.0
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_Plugin_Manager2
{
	static protected $plugins	=	array();
	static protected $api		=	array();
	static protected $location	=	array();

	static protected function findPlugin($name,$location=NULL)
	{
		$search = array_merge(array($location),self::$location);

		foreach($search as $location)
		{
			if(file_exists("$location/$name/package.xml")){
				//	double check that the location starts and ends with a slash
				//	something this isn't the case and the programmer forgets
				//	then the plugin doesnt load, all because of a simple missing slash
				return Amslib_File::reduceSlashes("/$location/");
			}
		}

		return false;
	}

	static public function config($name,$location)
	{
		//	Plugin was already loaded, so return it's Plugin Object directly
		if(self::isLoaded($name)) return self::$plugins[$name];
		
		if($location = self::findPlugin($name,$location)){
			//	Plugin was not present, so create it, load everything required and return it's API
			self::$plugins[$name] = new Amslib_Plugin2();
			self::$plugins[$name]->config($name,$location.$name);
		
			return self::$plugins[$name];
		}
		
		//	Plugin was not found
		return false;
	}
	
	static public function load($name,$location=NULL)
	{
		//	Config a plugin to be "preloaded" and available
		$p = self::config($name,$location);
		
		//	Process any import/export directives
		$p->transfer();
				
		//	Load the plugin and all it's children and resources
		$p->load();
		
		//	Insert the plugin, or remove it if something has failed
		if(self::insert($name,$p) == false) self::remove($name);
		
		//	Obtain the API object, or false if it doesn't exist
		return self::getAPI($name);
	}
	
	static public function preload($name,$plugin)
	{
		if($name && $plugin) self::$plugins[$name] = $plugin;
	}

	static public function insert($name,$plugin)
	{
		if($name && $plugin){
			$api = $plugin->getAPI();
			
			if($api){
				self::$api[$name]		=	$api;
				self::$plugins[$name]	=	$plugin;
			
				return true;
			}
		}

		return false;
	}

	static public function remove($name)
	{
		$r = self::$plugins[$name];

		unset(self::$plugins[$name],self::$api[$name]);

		return $r;
	}

	static public function getAPI($name)
	{
		return (isset(self::$api[$name])) ? self::$api[$name] : false;
	}
	
	static public function getPlugin($name)
	{
		return isset(self::$plugins[$name]) ? self::$plugins[$name] : false;
	}

	static public function getPluginNameByRouteName($routeName)
	{
		foreach(self::$api as $name=>$api)
		{
			if($api->hasRoute($routeName)) return $name;
		}

		return false;
	}
	
	static public function listPlugins()
	{
		return array_keys(self::$plugins);
	}
	
	static public function isLoaded($name)
	{
		return isset(self::$plugins[$name]) ? true : false;
	}

	static public function addLocation($location)
	{
		self::$location[] = Amslib_File::absolute($location);
	}

	static public function getLocation()
	{
		return self::$location;
	}
	
	/*******************************************************************
	 	HELPER FUNCTIONS

	 	Below are methods that allow you to plugin functionality
	 	by just knowing the name of the plugin and the manager
	 	will find out which appropriate plugin to call to execute
	 	the functionality
	********************************************************************/
	static public function renderView($plugin,$view,$parameters=array())
	{
		$api = self::getAPI($plugin);

		return $api ? $api->renderView($view,$parameters) : false;
	}
	
	static public function getObject($plugin,$id,$singleton=false)
	{
		$api = self::getAPI($plugin);
		
		return $api ? $api->getObject($id,$singleton) : false;
	}

	static public function setService($plugin,$id,$service)
	{
		$api = self::getAPI($plugin);

		return $api ? $api->setService($id,$service) : false;
	}

	static public function getService($plugin,$service)
	{
		$api = self::getAPI($plugin);

		return $api ? $api->getService($service) : false;
	}
	
	static public function getServiceURL($plugin,$service)
	{
		$api = self::getAPI($plugin);

		return $api ? $api->getServiceURL($service) : false;
	}

	static public function callService($plugin,$service)
	{
		$api = self::getAPI($plugin);

		return $api ? $api->callService($service) : false;
	}

	static public function setStylesheet($plugin,$id,$file,$conditional=NULL)
	{
		$api = self::getAPI($plugin);

		return $api ? $api->setStylesheet($id,$file,$conditional) : false;
	}

	static public function addStylesheet($plugin,$stylesheet)
	{
		$api = self::getAPI($plugin);

		return $api ? $api->addStylesheet($stylesheet) : false;
	}

	static public function setJavascript($plugin,$id,$file,$conditional=NULL)
	{
		$api = self::getAPI($plugin);

		return $api ? $api->setJavascript($id,$file,$conditional) : false;
	}

	static public function addJavascript($plugin,$javascript)
	{
		$api = self::getAPI($plugin);

		return $api ? $api->addJavascript($javascript) : false;
	}

	static public function render($plugin,$layout="default",$parameters=array())
	{
		$api = self::getAPI($plugin);
		
		return $api ? $api->render($layout,$parameters) : false;
	}
}