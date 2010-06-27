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
 * File: Amslib_Router2.php
 * Title: Version 2.0 of the Core router object
 * Version: 2.0
 * Project: Amslib/Router
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_Router2
{
	protected $activeRoute;
	protected $paramsRoute;
	protected $routerPath;
	protected $source;
	protected $webdir;

	protected function relativePath($path)
	{
		$absolutePath = Amslib_Filesystem::documentRoot().$path;
		return str_replace($this->webdir,"",$absolutePath);
	}

	public function __construct()
	{
		$this->webdir = "";
	}

	public function load($source,$webdir="")
	{
		$this->source = $source;
		$this->webdir = $webdir;
	}

	public function execute()
	{
		Amslib_Router_URL::setRouter($this);

		$this->routerPath = Amslib::getParam("router_path");

		if($this->routerPath !== NULL){
			$this->routerPath = $this->relativePath($this->routerPath);

			Amslib_Router_Language::detect($this->routerPath);

			//	FIXME:	What happens if someone sets up a system
			//			which uses a php file as a router path?
			//			this might not work anymore

			//	Append a / to the string and then replace any // with / (removing any duplicates basically)
			$this->routerPath	=	str_replace("//","/",$this->routerPath."/");
			$this->activeRoute	=	$this->source->getRouteData($this->routerPath);
			$this->paramsRoute	=	($this->activeRoute) ? $this->activeRoute["params"] : array();
		}
	}

	public function getResource($something=NULL)
	{
		if($something == NULL) return $this->activeRoute["resource"];
		//	Have to figure out what this something will do
		return false;
	}

	public function getRoute($name=NULL,$version="default")
	{
		//	Return the current active route
		if($name == NULL){
			return $this->activeRoute["route"];
		}
		//	This is in response to a query for a url
		//	based on the name of the route and version requested
		return $this->source->getRoute($name,$version);
	}

	public function isRouted()
	{
		return ($this->routerPath !== NULL) ? true : false;
	}

	public function getParameters()
	{
		return $this->paramsRoute;
	}

	public function getCurrentRoute()
	{
		return $this->activeRoute;
	}

	public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new Amslib_Router2();

		return $instance;
	}
}