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
 * File: Amslib.php
 * Title: Amslib core utility object
 * Version: 4.5
 * Project: Amslib (antimatter studios library)
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

//	Amslib helper class
class Amslib
{
	const VERSION = 4.5;

	static protected $showErrorTrigger		=	false;
	static protected $originalErrorHandler	=	false;

	//	DEPRECATED: should use findPath instead, makes more sense
	static protected function findFile($filename){ return self::findPath($filename); }
	
	static protected function findPath($filename)
	{
		$includePath = explode(PATH_SEPARATOR,ini_get("include_path"));

		foreach($includePath as $path){
			//	NOTE: Cannot use Amslib_File::reduceSlashes here, chicken/egg type problem
			$test = preg_replace('#//+#','/',"$path/$filename");
			if(@file_exists($test)) return $path;
		}

		return false;
	}

	static public function locate()
	{
		return dirname(__FILE__);
	}

	static public function showErrors()
	{
		//	Enable all error reporting
		ini_set("display_errors", "On");
		error_reporting(E_ALL);
		self::$showErrorTrigger = true;
	}
	
	static public function setErrorHandler($handler)
	{
		self::$originalErrorHandler = set_error_handler($handler);
	}
	
	static public function restoreErrorHandler()
	{
		if(self::$originalErrorHandler){
			set_error_handler(self::$originalErrorHandler);
		}
	}

	static public function lchop($str,$search)
	{
		$p = (strlen($search)) ? strpos($str,$search) : false;
		
		return ($p) !== false ? substr($str,$p+strlen($search)) : $str;
	}

	static public function rchop($str,$search)
	{
		return ($p = strrpos($str,$search)) !== false ? substr($str,0,$p) : $str;
	}
	
	static public function truncateString($string,$length)
	{
		return CakePHP::truncate($string,$length);
	}
	
	//	DEPRECATED: doesn't work very well with non-ascii characters like ü
	static public function htmlCutString($string,$length)
	{
		$output = new HtmlCutString($string, $length);
  		return $output->cut();
	}

	/**
	 *	function:	addIncludePath
	 *
	 *	Add an include path to the PHP include path
	 *
	 *	parameters:
	 *		$path	-	The path to add to the PHP include path
	 */
	static public function addIncludePath($path)
	{
		$includePath = explode(PATH_SEPARATOR,ini_get("include_path"));

		$valid = true;
		foreach($includePath as $p){
			if(strcmp($path,$p) == 0) $valid = false;
		}

		if($valid) array_unshift($includePath,$path);
		ini_set("include_path",implode(PATH_SEPARATOR,$includePath));
	}

	static public function getIncludeContents($filename)
	{
		ob_start();
		Amslib::includeFile($filename);
		$contents = ob_get_clean();
		
		return $contents;
	}
	
	static public function trimString($string,$maxlen,$postfix="...")
	{
		return (strlen($string) > $maxlen) ? substr($string,0,$maxlen).$postfix : $string;
	}
	
	//	blatently stolen code from: http://snipplr.com/view/22741/slugify-a-string-in-php/ :-) thank you!
	//	 
	static public function slugify($text)
	{
		// replace non letter or digits by -
		$text = preg_replace('~[^\\pL\d]+~u', '-', $text);
		 
		// trim
		$text = trim($text, '-');
		 
		// transliterate
		if (function_exists('iconv')) $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
		 
		// lowercase
		$text = strtolower($text);
		 
		// remove unwanted characters
		$text = preg_replace('~[^-\w]+~', '', $text);
		 
		if (empty($text)) return 'n-a';
		 
		return $text;
	}

	static public function var_dump($dump,$preformat=false,$hiddenOutput=false)
	{
		ob_start();
		var_dump($dump);
		$dump = ob_get_clean();

		$hiddenOutput = $hiddenOutput ? "style='display:none'" : "";

		return ($preformat) ? "<pre $hiddenOutput>$dump</pre>" : $dump;
	}

	static public function includeFile($file,$data=array())
	{
		$path = "";

		if(!file_exists($file)){
			$path = self::findFile($file);

			if($path !== false && strlen($path)) $path = "$path/";
		}

		//	NOTE: Cannot use Amslib_File::reduceSlashes here, chicken/egg type problem
		$file = preg_replace('#//+#','/',"{$path}$file");

		if(is_file($file) && file_exists($file)){
			if(is_array($data) && count($data)) extract($data, EXTR_SKIP);
			
			if(isset($data["include_once"])) return include_once($file);
			return include($file);
		}

		return false;
	}

	static public function requireFile($file,$data=array())
	{
		if(!is_string($file)) return false;	
		
		$path = "";

		if(!file_exists($file)){
			$path = self::findFile($file);

			if($path !== false && strlen($path)) $path = "$path/";
		}

		//	NOTE: Cannot use Amslib_File::reduceSlashes here, chicken/egg type problem
		$file = preg_replace('#//+#','/',"{$path}$file");		

		if(is_file($file) && file_exists($file)){
			if(is_array($data) && count($data)) extract($data, EXTR_SKIP);

			if(isset($data["require_once"])) return require_once($file);
			return require($file);
		}

		return false;
	}

	static public function autoloader()
	{
		//	Only register it once.
		if(function_exists("amslib_autoload")) return;
		//	add the amslib directory and it's parent to the include path
		//	99.9999% of times, you want this to happen, so I just make it default
		self::addIncludePath(dirname(__FILE__));
		self::addIncludePath(dirname(dirname(__FILE__)));

		function amslib_autoload($class_name)
		{
			if($class_name == __CLASS__) return false;

			//	Special case for the FirePHP library
			if($class_name == "FirePHP"){
				$class_name	=	"util/FirePHPCore/$class_name.class";
			}
			
			if($class_name == "phpQuery"){
				$class_name =	"util/phpquery.php";
			}

			//	Redirect to include the correct path for the translator system
			if(strpos($class_name,"Amslib_Translator") !== false){
				$class_name	=	"translator/$class_name";
			}

			//	Redirect to include the correct path for the router system
			if(strpos($class_name,"Amslib_Router") !== false){
				$class_name	=	"router/$class_name";
			}

			//	Redirect to include the correct path for the database system
			if(strpos($class_name,"Amslib_Database") !== false){
				$class_name	=	"database/$class_name";
			}
			
			//	Redirect to include the correct path for the xml system
			if(strpos($class_name,"Amslib_XML") !== false){
				$class_name =	"xml/$class_name";
			}
			
			//	Redirect to include the correct path for the plugin system
			if(strpos($class_name,"Amslib_Plugin") !== false){
				$class_name	=	"plugin/$class_name";
			}
			
			//	Redirect to include the correct path for the MVC system
			if(strpos($class_name,"Amslib_MVC") !== false){
				$class_name	=	"mvc/$class_name";
			}
			
			//	Redirect to include the correct path for the File system classes
			if(strpos($class_name,"Amslib_File") !== false){
				$class_name	=	"file/$class_name";
			}
			
			//	DEPRECATED: unless I can find a way to fix the utf-8 broken characters like ü
			if(strpos($class_name,"HtmlCutString") !== false){
				$class_name	=	"util/html_cut_string";
			}
			
			if(strpos($class_name,"CakePHP") !== false){
				$class_name =	"util/CakePHP";
			}
			
			$filename = str_replace("//","/","$class_name.php");
			
			return Amslib::requireFile($filename);
		}

		//	register a special autoloader that will include correctly all of the amslib classes
		spl_autoload_register("amslib_autoload");
	}

	static public function findKey($key,$source)
	{
		foreach($source as $k=>$ignore){
			if(strpos($k,$key) !== false) return $k;
		}

		return false;
	}

	/**
	 * 	function:	getParam
	 *
	 * 	Obtain a parameter from the GET global array
	 *
	 * 	parameters:
	 * 		$value		-	The value requested
	 * 		$default	-	The value to return if the value does not exist
	 * 		$erase		-	Whether or not to erase the value after it's been read
	 *
	 * 	returns:
	 * 		-	The value from the GET global array, if not exists, the value of the parameter return
	 */
	static public function getParam($value,$default=NULL,$erase=false)
	{
		return self::arrayParam($_GET,$value,$default,$erase);
	}
	
	static public function hasGet($value)
	{
		return (isset($_GET[$value])) ? true : false;
	}

	/**
	 *	function:	insertGetParameter
	 *
	 *	Insert a parameter into the global GET array
	 *
	 *	parameters:
	 *		$parameter	-	The parameter to insert
	 *		$value		-	The value of the parameter being inserted
	 *
	 *	notes:
	 *		-	Sometimes this is helpful, because it can let you build certain types of code flow which arent possible otherwise
	 */
	static public function insertGetParam($parameter,$value)
	{
		$_GET[$parameter] = $value;
	}

	/**
	 * 	function:	postParam
	 *
	 * 	Obtain a parameter from the POST global array
	 *
	 * 	parameters:
	 * 		$value		-	The value requested
	 * 		$default	-	The value to return if the value does not exist
	 * 		$erase		-	Whether or not to erase the value after it's been read
	 *
	 * 	returns:
	 * 		-	The value from the POST global array, if not exists, the value of the parameter return
	 */
	static public function postParam($value,$default=NULL,$erase=false)
	{
		return self::arrayParam($_POST,$value,$default,$erase);
	}
	
	static public function hasPost($value)
	{
		return (isset($_POST[$value])) ? true : false;
	}

	/**
	 *	function:	insertPostParameter
	 *
	 *	Insert a parameter into the global POST array
	 *
	 *	parameters:
	 *		$parameter	-	The parameter to insert
	 *		$value		-	The value of the parameter being inserted
	 *
	 *	notes:
	 *		-	Sometimes this is helpful, because it can let you build certain types of code flow which arent possible otherwise
	 */
	static public function insertPostParam($parameter,$value)
	{
		$_POST[$parameter] = $value;
	}

	/**
	 * 	function:	sessionParam
	 *
	 * 	Obtain a parameter from the SESSION global array
	 *
	 * 	parameters:
	 * 		$value		-	The value requested
	 * 		$default	-	The value to return if the value does not exist
	 * 		$erase		-	Whether or not to erase the value after it's been read
	 *
	 * 	returns:
	 * 		-	The value from the SESSION global array, if not exists, the value of the parameter return
	 */
	static public function sessionParam($value,$default=NULL,$erase=false)
	{
		return self::arrayParam($_SESSION,$value,$default,$erase);
	}
	
	static public function hasSession($value)
	{
		return (isset($_SESSION[$value])) ? true : false;
	}

	static public function insertSessionParam($parameter,$value)
	{
		$_SESSION[$parameter] = $value;
	}

	/**
	 * 	function:	filesParam
	 *
	 * 	Obtain a parameter from the FILES global array
	 *
	 * 	parameters:
	 * 		$value		-	The value requested
	 * 		$default	-	The value to return if the value does not exist
	 * 		$erase		-	Whether or not to erase the value after it's been read
	 *
	 * 	returns:
	 * 		-	The value from the FILES global array, if not exists, the value of the parameter return
	 */
	static public function filesParam($value,$default=NULL,$erase=false)
	{
		return self::arrayParam($_FILES,$value,$default,$erase);
	}

	static public function insertFilesParam($parameter,$value)
	{
		$_FILES[$parameter] = $value;
	}

	static public function arrayParam(&$source,$value,$default=NULL,$erase=false)
	{
		if(isset($source[$value])){
			$default = $source[$value];
			if($erase) unset($source[$value]);
		}

		return $default;
	}

	/**
	 * 	function:	requestParam
	 *
	 * 	Obtain a parameter from the REQUEST global array
	 *
	 * 	parameters:
	 * 		$value		-	The value requested
	 * 		$default	-	The value to return if the value does not exist
	 * 		$erase		-	Whether or not to erase the value after it's been read
	 *
	 * 	returns:
	 * 		-	The value from the REQUEST global array, if not exists, the value of the parameter return
	 */
	static public function requestParam($value,$default=NULL,$erase=false)
	{
		return self::arrayParam($_REQUEST,$value,$default,$erase);
	}

	static public function insertRequestParam($parameter,$value)
	{
		$_REQUEST[$parameter] = $value;
	}
}
