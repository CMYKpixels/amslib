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
 * File: Amslib_WidgetManager.php
 * Title: Widget manager for component based development
 * Version: 2.6
 * Project: amslib
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_WidgetManager
{
/*******************************************************************************
 *	PROTECTED MEMBERS
 *******************************************************************************/
	protected $xdoc;
	protected $xpath;

	protected $documentRoot;
	protected $widgetPath;
	protected $websitePath;

	protected $api;
	protected $stylesheet;
	protected $javascript;

	protected function setAPI($name)
	{
		$list = $this->xpath->query("//package/object/api");

		$api = false;

		if($list->length > 0){
			$node = $list->item(0);
			if($node){
				Amslib::requireFile($this->widgetPath."/$name/objects/{$node->nodeValue}.php");

				if(method_exists($node->nodeValue,"getInstance")){
					$api = call_user_func(array($node->nodeValue,"getInstance"));
				}else{
					$error = "FATAL ERROR(Amslib_WidgetManager::setAPI) Could not __ERROR__ for widget '$name'<br/>";

					if(!class_exists($node->nodeValue)){
						//	class does not exist
						$error = str_replace("__ERROR__","find class '{$node->nodeValue}'",$error);
					}else{
						//	class exists, but method does not
						$error = str_replace("__ERROR__","find the getInstance method in the API object '{$node->nodeValue}'");
					}

					die($error);
				}
			}
		}

		//	Create a default MVC object
		if($api == false) $api = new Amslib_MVC();

		$this->api[$name] = $api;

		//	Set the widget manager parent
		$api->setWidgetManager($this);
		$api->setWidgetName($name);

		return $this->api[$name];
	}

	protected function isWidgetLoaded($name)
	{
		return (isset($this->api[$name])) ? true : false;
	}

	protected function preparePath($path)
	{
		//	Make sure the path starts with a slash, also remove double slashes
		$path = str_replace("//","/","/{$path}__END__");
		//	Remove any trailing slash from the path
		$path = Amslib_Filesystem::removeTrailingSlash($path);

		return $path;
	}

	protected function loadPackage($path,$name)
	{
		$xml = "$path/$name/package.xml";
		if($p = Amslib_Filesystem::find($xml)) $xml = "$p/$xml";

		$this->xdoc = new DOMDocument('1.0', 'UTF-8');
		if(@$this->xdoc->load($xml)){
			$this->xdoc->preserveWhiteSpace = false;
			$this->xpath = new DOMXPath($this->xdoc);

			return true;
		}else{
			print("XML FILE FAILED TO OPEN: path[$path], name[$name], xml[$xml]<br/>");

			return false;
		}
	}

	protected function loadDependencies()
	{
		$hasDependencies = false;

		$deps = $this->xpath->query("//package/requires/widget");
		for($a=0;$a<$deps->length;$a++){
			$name = $deps->item($a)->nodeValue;

			$this->load($name);

			$hasDependencies = true;
		}

		return $hasDependencies;
	}

	protected function findResource($widget,$node)
	{
		if($node->getAttribute("remote")) return $node->nodeValue;

		$file = $node->nodeValue;
		$path = false;

		//	First try to find the file inside the widget itself
		$wpath = str_replace("//","/","{$this->widgetPath}/$widget/$file");
		//	if you found it inside the widget, use that version over any other
		if(file_exists($wpath)) $path = $wpath;

		//	If you didn't find it, search the path
		if($path == false){
			//	can't find the widget, search the path instead
			$fpath = Amslib_Filesystem::find($file);

			//	if you found it, assign it
			if($fpath) $path = "$fpath/$file";
		}

		//	relativise the path and reduce the double slashes to single ones.
		return str_replace("//","/",$this->getRelativePath($path));
	}

	protected function loadConfiguration($path,$widget)
	{
		$api = $this->setAPI($widget);

		$controllers = $this->xpath->query("//package/controllers/name");
		for($a=0;$a<$controllers->length;$a++){
			$id		=	$controllers->item($a)->getAttribute("id");
			$name	=	$controllers->item($a)->nodeValue;
			$api->setController($id,$name);
		}

		$layouts = $this->xpath->query("//package/layout/name");
		for($a=0;$a<$layouts->length;$a++){
			$id		=	$layouts->item($a)->getAttribute("id");
			$name	=	$layouts->item($a)->nodeValue;
			$api->setLayout($id,$name);
		}

		$views = $this->xpath->query("//package/view/name");
		for($a=0;$a<$views->length;$a++){
			$id		=	$views->item($a)->getAttribute("id");
			$name	=	$views->item($a)->nodeValue;
			$api->setView($id,$name);
		}

		$objects = $this->xpath->query("//package/object/name");
		for($a=0;$a<$objects->length;$a++){
			$id		=	$objects->item($a)->getAttribute("id");
			$name	=	$objects->item($a)->nodeValue;
			$api->setObject($id,$name);
		}

		$theme = $this->xpath->query("//package/theme/name");
		for($a=0;$a<$theme->length;$a++){
			$id		=	$theme->item($a)->getAttribute("id");
			$name	=	$theme->item($a)->nodeValue;
			$api->setTheme($id,$name);
		}

		//	FIXME: why are services treated differently then other parts of the MVC system?
		$services = $this->xpath->query("//package/service/file");
		for($a=0;$a<$services->length;$a++){
			$id		=	$services->item($a)->getAttribute("id");
			$file	=	$services->item($a)->nodeValue;
			$file	=	$this->getRelativePath($this->widgetPath."/$widget/services/$file");
			$api->setService($id,$file);
		}

		$images = $this->xpath->query("//package/image/file");
		for($a=0;$a<$images->length;$a++){
			$id = $images->item($a)->getAttribute("id");
			$file = $this->findResource($widget,$images->item($a));
			$api->setImage($id,$file);
		}

		$javascript = $this->xpath->query("//package/javascript/file");
		for($a=0;$a<$javascript->length;$a++){
			$id = $javascript->item($a)->getAttribute("id");
			$file = $this->findResource($widget,$javascript->item($a));
			$this->setJavascript($id,$file);
		}

		$stylesheet = $this->xpath->query("//package/stylesheet/file");
		for($a=0;$a<$stylesheet->length;$a++){
			$id		=	$stylesheet->item($a)->getAttribute("id");
			$file	=	$this->findResource($widget,$stylesheet->item($a));
			$this->setStylesheet($id,$file);
		}
		
		return $api;
	}

/*******************************************************************************
 *	PUBLIC METHODS
 *******************************************************************************/
	public function __construct()
	{
		//	Setup the basic system like this, it's 99% correct everytime
		$this->setup(Amslib_Filesystem::documentRoot()."/widgets",Amslib_Filesystem::documentRoot());

		$this->stylesheet	=	array();
		$this->javascript	=	array();
	}

	public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new Amslib_WidgetManager();

		return $instance;
	}

	//	initialise all the required basic information
	public function setup($widgetPath,$websitePath=NULL)
	{
		//	Make sure the website path is not null, default to the server root (99% correct everytime)
		if($websitePath == NULL) $websitePath = Amslib_Filesystem::documentRoot();

		$this->documentRoot =	$this->preparePath(Amslib_Filesystem::documentRoot());
		$this->widgetPath	=	$this->preparePath($widgetPath);
		$this->websitePath	=	$this->preparePath($websitePath);

		//	Make website path into an absolute path
		$this->websitePath	=	Amslib_Filesystem::absolute($this->websitePath);

		//	Make widgetPath into an absolute path
		$this->widgetPath	=	Amslib_Filesystem::absolute($this->widgetPath);
	}

	public function getAPI($name)
	{
		return isset($this->api[$name]) ? $this->api[$name] : false;
	}

	/**
	 * method: overrideAPI
	 *
	 * A way to provide a custom API object that deals with your application specific
	 * layer and perhaps provides a customised way to deal with the widget in question
	 * and your qpplication.
	 *
	 * parameters:
	 * 	$name	-	The name of the widget being overriden
	 * 	$api	-	An object which is to be used to override the default widget api
	 *
	 * example:
	 *		require_once("CustomApp_Amstudios_Message_List.php");
	 *		class CustomApp_Amstudios_Message_List extends Amstudios_Message_List{}
	 *		$api = CustomApp_Amstudios_Message_List::getInstance();
	 *		$api->setValue("key","value");
	 *		$widgetManager->overrideAPI("amstudios_message_list",$api);
	 *		$widgetManager->render("amstudios_message_list");
	 */
	public function overrideAPI($name,$api)
	{
		$this->api[$name] = $api;
	}

	public function load($name)
	{
		if($this->isWidgetLoaded($name)){
			return $this->getAPI($name);
		}

		if($this->loadPackage($this->widgetPath,$name)){
			$xdoc	=	$this->xdoc;
			$xpath	=	$this->xpath;

			if($this->loadDependencies()){
				$this->xdoc		=	$xdoc;
				$this->xpath	=	$xpath;
			}

			return $this->loadConfiguration($this->widgetPath,$name);
		}
		
		return false;
	}

	public function getWidgetPath()
	{
		return $this->widgetPath;
	}

	//	FIXME: replace with Amslib_Filesystem::relative()
	public function getRelativePath($path="")
	{
		//	Path is already relative
		if(strpos($path,".") === 0) return $path;

		$root = $this->documentRoot;
		$path = str_replace($root,"",$root.$path);

		return $path;
	}
	
	public function setStylesheet($name,$file)
	{
		if($name && $file){
			$this->stylesheet[$name] = "<link rel='stylesheet' type='text/css' href='$file' />";
		}
	}

	public function getStylesheet()
	{
		return implode("\n",$this->stylesheet);
	}

	public function setJavascript($name,$file)
	{
		if($name && $file){
			$this->javascript[$name] = "<script type='text/javascript' src='$file'></script>";
		}
	}

	public function getJavascript()
	{
		return implode("\n",$this->javascript);
	}

	public function render($name,$parameters=array())
	{
		$api = $this->getAPI($name);

		return ($api) ? $api->render("default",$parameters) : false;
	}
}