<?php
class Amslib_Plugin_Config_XML
{
	protected $location;

	protected $packageName;

	protected $queryPath;

	//	NOTE:	I chose to make this an array because I could allow it to be
	//			configured and then run it through a generic process step below,
	//			allowing flexibility in how these sections are loaded and processed
	static protected $selectors = array();

	/**
	 * 	method:	__construct
	 *
	 * 	parameters:
	 * 		$package_name - The name of the package name to use when loading configuration files
	 *
	 * 	notes:
	 * 		-	If the $packageName is NULL, "package.xml" will be used as a default
	 */
	public function __construct($packageName="package.xml")
	{
		$this->location		= false;
		$this->packageName	= false;
		$this->queryPath	= false;

		$this->setValue("package",$packageName);
	}

	static public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}

	static public function initialiseSelectors()
	{
		self::$selectors = array("scan"=>array(),"load"=>array());

		//	If you do not load the router first, the routes will come out in reverse order :(
		//	So you need to do this first, before processing all the child plugins which can override them
		self::addScanSelector("package router",				"configRouter");
		//	Create the API object first, then load the selector extensions and then all the child plugins to build the tree
		self::addScanSelector("package object api",			"configAPI");
		self::addScanSelector("package requires extension",	"configExtension");
		self::addScanSelector("package requires plugin",	"configPlugin");

		//	Now add all the load selectors, now that the tree scanning has completed
		self::addLoadSelector("package object name",		"configObject");
		self::addLoadSelector("package controller name",	"configController");
		self::addLoadSelector("package model connection",	"configModelConnection");
		self::addLoadSelector("package model name",			"configModel");
		self::addLoadSelector("package view name",			"configView");
		self::addLoadSelector("package service import",		"configService");
		self::addLoadSelector("package service export",		"configService");
		self::addLoadSelector("package javascript file",	"configJavascript");
		self::addLoadSelector("package stylesheet file",	"configStylesheet");
		self::addLoadSelector("package image file",			"configImage");
		self::addLoadSelector("package font",				"configFont");
		self::addLoadSelector("package translator",			"configTranslator");
		self::addLoadSelector("package value",				"configValue");
		self::addLoadSelector("package path",				"configPath");
		self::addLoadSelector("package",					"configCustom");
	}

	static protected function addSelector($type,$selector,$callback)
	{
		if(!in_array($type,array("scan","load"))) return false;

		if(count($callback) == 2 && is_string($callback[0])){
			$callback[0] = Amslib_Plugin_Manager::getPlugin($callback[0])->getAPI();
		}

		//	With XML, we want to anchor each selector to the package tag, sometimes you can nest
		//	nodes and have nodes which look like they should be part of the amslib package xml
		//	scheme, but are some custom nodes with the same names.  This replacement stops that problem.
		if(strpos($selector,"package") === 0){
			$selector = str_replace("package ","package > ",$selector);
		}

		self::$selectors[$type][$selector] = $callback;

		return true;
	}

	static public function addScanSelector($selector,$callback)
	{
		return self::addSelector("scan",$selector,$callback);
	}

	static public function addLoadSelector($selector,$callback)
	{
		return self::addSelector("load",$selector,$callback);
	}

	public function getScanSelectors()
	{
		return self::$selectors["scan"];
	}

	public function getLoadSelectors()
	{
		return self::$selectors["load"];
	}

	public function getStatus()
	{
		return file_exists($this->getValue("filename"));
	}

	public function setValue($key,$value)
	{
		switch($key){
			case "package":{
				$this->packageName = is_string($value) && strlen($value) ? $value : NULL;
			}break;

			case "location":{
				if($this->packageName !== NULL && is_string($value) && strlen($value)){
					$this->location = $value;
				}
			}break;

			default:{
				$value = false;
			}break;
		}

		return $value;
	}

	public function getValue($key)
	{
		switch($key){
			//	We can put specific keys which do something other than get the value of selectors
			//	But of course this stops us from using those keys as XML Nodes in the code
			//	This isn't really a problem, since I can't see them overlapping right now

			case "filename":{
				return Amslib_File::reduceSlashes(
					Amslib_File::absolute("{$this->location}/{$this->packageName}")
				);
			}break;
		}

		return false;
	}

	public function process($plugin,$key,$callback)
	{
		$callback = is_string($callback) ? array($plugin,$callback) : $callback;

		if(is_array($callback) && count($callback) == 2 && is_string($callback[0])){
			$callback[0] = Amslib_Plugin_Manager::getAPI($callback[0]);
		}

		if(!$this->queryPath || !is_callable($callback)){
			Amslib::errorLog("QueryPath or callback not valid",$plugin->getName(),$key,$callback[0],$callback[1]);
			return;
		}

		try{
			$results = $this->queryPath->branch()->find($key);
		}catch(Exception $e){
			Amslib::errorLog("QueryPath Exception",$e->getMessage);
		}

		foreach($results as $r){
			$r = Amslib_QueryPath::toArray($r);

			call_user_func($callback,$r["tag"],$r,$plugin);
		}
	}

	public function prepare()
	{
		$filename = $this->getValue("filename");

		try{
			$this->queryPath = Amslib_QueryPath::qp($filename);
		}catch(Exception $e){
			Amslib::errorLog("QueryPath Exception",$e->getMessage);
		}
	}
}