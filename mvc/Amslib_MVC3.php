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
 * File: Amslib_MVC.php
 * Title: Model/View/Controller implementation for use with Amslib projects
 * Version: 3.0
 * Project: amslib
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_MVC3
{
	protected $controller;
	protected $layout;
	protected $object;
	protected $view;
	protected $images;
	protected $service;
	protected $stylesheet;
	protected $javascript;
	protected $translator;

	//	The model object to allow access to the application logic
	protected $model;

	protected $value;
	protected $viewParams;
	protected $routes;

	//	To allow views/html segments to be slotted into an existing layout
	//	extending their capabilities with customised functionality
	protected $slots;

	//	MVC Configuration
	protected $prefix;

	protected $name;
	protected $location;
	protected $plugin;

	protected function getComponentPath($component,$name)
	{
		return $this->location.$this->prefix[$component]."$name.php";
	}

	public function __construct()
	{
		$this->controller	=	array();
		$this->layout		=	array("default"=>false);
		$this->object		=	array();
		$this->view			=	array();
		$this->service		=	array();
		$this->stylesheet	=	array();
		$this->javascript	=	array();
		$this->translator	=	array();

		$this->value		=	array();
		$this->viewParams	=	array();

		//	This stores where all the types components are stored as part of the application
		$this->prefix = array();

		$this->setComponent("controller",	"controllers",	"Ct_");
		$this->setComponent("layout",		"layouts",		"La_");
		$this->setComponent("view",			"views",		"Vi_");
		$this->setComponent("object",		"objects",		"");
		$this->setComponent("service",		"services",		"");
		$this->setComponent("service2",		"services",		"Sv_");
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setLocation($location)
	{
		$this->location = $location;
	}

	public function initialise()
	{
		return $this;
	}

	public function setComponent($component,$directory,$prefix)
	{
		$this->prefix[$component] = "/$directory/$prefix";
	}

	public function setModel($model)
	{
		$this->model = $model;
	}

	public function getModel()
	{
		return $this->model;
	}

	public function setRoute($name,$route)
	{
		$this->routes[$name] = $route;
	}
	
	public function setPlugin($plugin)
	{
		$this->plugin = $plugin;
	}
	
	public function getPlugin()
	{
		return $this->plugin;
	}

	public function getRoute($name=NULL)
	{
		//	NOTE:	If name was not passed/NULL return entire routes array,
		//			otherwise return the route, but if it doesnt exist, return false
		return ($name === NULL) ?
			$this->routes : (isset($this->routes[$name])) ?
				$this->route[$name] : false;
	}

	public function hasRoute($name)
	{
		return (isset($this->routes[$name])) ? true : false;
	}

	public function setValue($name,$value)
	{
		$this->value[$name] = $value;
	}

	public function getValue($name)
	{
		return (isset($this->value[$name])) ? $this->value[$name] : $this->getViewParam($name);
	}
	
	public function listValues()
	{
		return array_keys($this->value);
	}

	//	TODO: need to explain the difference between a value and a view param
	public function setViewParam($parameters)
	{
		$this->viewParams = $parameters;
	}

	public function getViewParam($name)
	{
		return (isset($this->viewParams[$name])) ? $this->viewParams[$name] : NULL;
	}

	public function setController($id,$name,$absolute=false)
	{
		if(!$id || strlen($id) == 0) $id = $name;

		$this->controllers[$id] = ($absolute == false) ? $this->getComponentPath("controller",$name) : $name;
	}

	public function getController($id)
	{
		return $this->controllers[$id];
	}
	
	public function listControllers()
	{
		return array_keys($this->controllers);
	}

	public function setLayout($id,$name,$absolute=false)
	{
		if(!$id || strlen($id) == 0) $id = $name;

		$this->layout[$id] = ($absolute == false) ? $this->getComponentPath("layout",$name) : $name;

		if($this->layout["default"] == false) $this->layout["default"] = $this->layout[$id];
	}

	public function getLayout($id=NULL)
	{
		if($id && isset($this->layout[$id])) return $this->layout[$id];

		return $this->layout;
	}
	
	public function listLayouts()
	{
		return array_keys($this->layout);
	}

	public function setObject($id,$name,$absolute=false)
	{
		if(!$id || strlen($id) == 0) $id = $name;

		$this->object[$id] = ($absolute == false) ? $this->getComponentPath("object",$name) : $name;
	}

	public function getObject($id,$singleton=false)
	{
		if(!isset($this->object[$id])) return false;

		$status = Amslib::requireFile($this->object[$id],array("require_once"=>true));

		if(class_exists($id)){
			if($singleton) return call_user_func(array($id,"getInstance"));

			return new $id;
		}

		return false;
	}
	
	public function listObjects()
	{
		return array_keys($this->object);
	}

	public function setView($id,$name,$absolute=false)
	{
		if(!$id || strlen($id) == 0) $id = $name;

		$this->view[$id] = ($absolute == false) ? $this->getComponentPath("view",$name) : $name;
	}
	
	//	TODO: This method should be called getView in line with all the others
	public function findView($id)
	{
		if($id && isset($this->view[$id])) return $this->view[$id];

		return $this->view;
	}
	
	public function renderView($id,$parameters=array())
	{
		if(is_string($id) && isset($this->view[$id]))
		{
			if(!empty($parameters)) $this->setViewParam($parameters);

			//	TODO: what happens if api, _w and _c are already defined and you just overwrote them?
			//	NOTE: this shouldn't happen, they are special so nobody should use them
			//	NOTE: perhaps we can warn people when they do this? or perhaps move our keys to a more unique "namespace"
			$parameters["api"]	=	$this;
			$parameters["_w"]	=	$this->getTranslator("website");
			$parameters["_c"]	=	$this->getTranslator("content");

			ob_start();
			Amslib::requireFile($this->view[$id],$parameters);
			return ob_get_clean();
		}

		return "";
	}
	
	public function listViews()
	{
		return array_keys($this->view);
	}

	public function setService($id,$name,$absolute=false)
	{
		if(!$id || strlen($id) == 0) $id = $name;

		$this->service[$id] = ($absolute == false) ? Amslib_Website::rel($this->getComponentPath("service", $name)) : $name;

		//	Set this as a service url for the javascript to acquire
		$this->setValue("service:$id", $this->service[$id]);
	}
	
	public function setService2($id,$name,$absolute=false)
	{
		if(!$id || strlen($id) == 0) $id = $name;

		if($absolute){
			$this->service[$id] = $name;
			$this->setValue("service:$id",$name);
		}else{
			$this->service[$id] = Amslib_Website::rel($this->getComponentPath("service2", $name));
			
			//	NOTE: I should recognise that now Amslib_MVC3 is dependant on Amslib_Router3's existence
			//	Attempt to find a routed url for this service
			$url = Amslib_Router3::getURL("Service:$id");
			if(!$url) $url = $this->service[$id];

			//	Set this as a service url for the javascript to acquire
			$this->setValue("service:$id",$url);
		}
	}

	public function getService($id,$url=false)
	{
		if($url) return $this->getValue("service:$id");
		
		return (isset($this->service[$id])) ? $this->service[$id] : NULL;
	}
	
	public function listServices()
	{
		return array_keys($this->service);
	}
	
	public function getServiceURL($id)
	{
		return $this->getValue("service:$id");
	}
	
	public function setTranslator($name,$translator)
	{
		$this->translator[$name] = $translator;
	}
	
	public function getTranslator($name)
	{
		return (isset($this->translator[$name])) ? $this->translator[$name] : reset($this->translator);
	}

	public function callService($id)
	{
		$service = $this->getService($id);
		$service = Amslib_File::absolute($service);

		$parameters["api"] = $this;

		return Amslib::requireFile($service,$parameters);
	}

	public function setStylesheet($id,$file,$conditional=NULL,$autoload=NULL,$media=NULL)
	{
		if(!is_string($id) && $file) return;
		
		$this->stylesheet[$id] = array("file"=>$file,"conditional"=>$conditional,"media"=>$media);

		if($autoload) $this->addStylesheet($id);
	}

	public function addStylesheet($id)
	{
		if(isset($this->stylesheet[$id])){
			$s = $this->stylesheet[$id];
			Amslib_Resource_Compiler::addStylesheet($id,$s["file"],$s["conditional"],$s["media"]);
		}
	}

	public function removeStylesheet($id)
	{
		Amslib_Resource_Compiler::removeStylesheet($id);
	}

	public function setJavascript($id,$file,$conditional=NULL,$autoload=NULL)
	{
		if(!is_string($id) && $file) return;
		
		$this->javascript[$id] = array("file"=>$file,"conditional"=>$conditional);

		if($autoload) $this->addJavascript($id);
	}

	public function addJavascript($id)
	{
		if(isset($this->javascript[$id])){
			$j = $this->javascript[$id];
			Amslib_Resource_Compiler::addJavascript($id,$j["file"],$j["conditional"]);
		}
	}

	public function removeJavascript($id)
	{
		Amslib_Resource_Compiler::removeJavascript($id);
	}
	
	public function setGoogleFont($id,$file,$conditional=NULL,$autoload=NULL)
	{
		if(!is_string($id) && $file) return;
		
		$this->googleFont[$id] = array("file"=>$file,"conditional"=>$conditional);

		if($autoload) $this->addGoogleFont($id);
	}
	
	public function addGoogleFont($id)
	{
		if(isset($this->googleFont[$id])){
			$f = $this->googleFont[$id];
			Amslib_Resource_Compiler::addGoogleFont($id,$f["file"],$f["conditional"]);
		}
	}
	
	public function removeGoogleFont($id)
	{
		Amslib_Resource_Compiler::removeGoogleFont($id);
	}

	/**
	 * method: getHiddenParameters
	 *
	 * This outputs a block of hidden inputs for javascript or a web form to use when posting
	 * or processing data.
	 *
	 * returns:	A string of HTML, containing all the parameters
	 *
	 * notes:
	 * 	-	This is perhaps not the best way, because any value will be output, perhaps even secret information
	 * 		that the user puts accidentally and doesnt realise it'll be output as plain text in the HTML
	 * 	-	I hate the fact that I'm outputting HTML here, but I dont really have any other alternative which is cheap
	 * 		and relatively easy and clean, it's simpler, but not the best way I am sure.
	 *
	 * warning:
	 * 	-	do not change \" for single quote ' or similar, it's done like this to prevent certain types
	 * 		of bugs I found with certain combinations of code, it's important to prevent future problems
	 * 		to keep \" because it was the only way to prevent strings from becoming broken
	 */
	public function getHiddenParameters()
	{
		 $list = "";

		foreach($this->value as $k=>$v){
			if(is_bool($v)) $v = ($v) ? "true" : "false";

			//	WARNING:	do not change \" for single quote ' or similar, it's done like this to prevent
			//				certain types of bugs I found with certain combinations of code, it's important
			//				to prevent future problems to keep \" because it was the only way to prevent strings
			//				from becoming broken
			$list.="<input type=\"hidden\" name=\"$k\" value=\"$v\" />";
		}

		return "<div class='plugin_parameters'>$list</div>";
	}

	public function copyService($src,$id,$dest=NULL)
	{
		if($dest === NULL) $dest = $id;

		$api = Amslib_Plugin_Manager::getAPI($src);
		$this->setService($dest,$api->getService($id));
	}

	public function setImage($id,$file)
	{
		$this->images[$id] = $file;

		$this->setValue("image:$id", $file);
	}

	public function getImage($id)
	{
		return (isset($this->images[$id])) ? $this->images[$id] : false;
	}

	public function setSlot($name,$content,$index=NULL)
	{
		if($index){
			$this->slots[$name][$index] = $content;
		}else{
			$this->slots[$name] = $content;
		}
	}

	public function getSlot($name,$index=NULL)
	{
		if(isset($this->slots[$name])){
			if(is_array($this->slots[$name])){
				return ($index) ? $this->slots[$name][$index] : current($this->slots[$name]);
			}

			return $this->slots[$name];
		}

		return "";
	}

	/**************************************************************************
	 * method: render
	 *
	 * render the output from this MVC, the basic version just renders the
	 * first layout as defined in the XML, without a controller.
	 *
	 * returns:
	 * 	A string of HTML or empty string which represents the first layout
	 *
	 * notes:
	 * 	we only render the first layout in the widget, what happens if there are 10 layouts?
	 */
	public function render($id="default",$parameters=array())
	{
		if(is_string($id) && isset($this->layout[$id])){
			//	TODO: Not sure whether I need to do this with rendering layouts as well as views
			//	NOTE: not 100% sure why I removed this, need to document it's reasoning if I remember
			//if(!empty($parameters)) $this->setViewParam($parameters);

			//	TODO: what happens if api, _w and _c are already defined and you just overwrote them?
			//	NOTE: this shouldn't happen, they are special so nobody should use them
			//	NOTE: perhaps we can warn people when they do this? or perhaps move our keys to a more unique "namespace"
			$parameters["api"]	=	$this;
			$parameters["_w"]	=	$this->getTranslator("website");
			$parameters["_c"]	=	$this->getTranslator("content");

			ob_start();
			Amslib::requireFile($this->layout[$id],$parameters);
			return ob_get_clean();
		}

		return "";
	}
	
	//	DEPRECATED: use renderView() instead
	//	TODO: investigate: this method is very similar to render, can refactor??
	//	TODO: Because getView collides with findView above, we should try to move this method to somewhere more inline with renderView() [which is what it actually does]
	public function getView($id,$parameters=array()){ return $this->renderView($id,$parameters); }
}