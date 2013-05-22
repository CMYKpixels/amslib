<?php
class Amslib_Plugin_Service
{
	const SR = "/amslib/service";
	const VD = "validation/data";
	const VE = "validation/errors";
	const SD = "service/data";
	const SE = "service/errors";
	const DB = "database/errors";
	const HD = "handlers";
	const FB = "feedback";
	const SC = "success";

	protected $successURL;
	protected $failureURL;
	protected $successCB;
	protected $failureCB;
	protected $isAJAX;
	protected $data;
	protected $session;
	protected $source;
	protected $handlerList;
	protected $activeHandler;

	//	Used in the website to retrieve the session data after processing
	static protected $serviceData	=	NULL;
	static protected $handler		=	NULL;
	static protected $var			=	array();

	static protected function getHandlerData($plugin,$default,$key)
	{
		if(!self::$handler){
			//	TODO: move into the logging system intead of here
			error_log("** ".__METHOD__." ** ".Amslib::var_dump(self::$handler,true)." was invalid");
			return NULL;
		}

		return isset(self::$handler[$plugin]) && isset(self::$handler[$plugin][$key])
			? Amslib_Array::valid(self::$handler[$plugin][$key])
			: $default;
	}

	protected function storeData($status)
	{
		if($this->activeHandler["record"]){
			$this->session[self::SC]	= $status;
			
			if(!empty($this->data)){
				$this->session[self::HD][]	= $this->data;
			}
		}
		
		if($this->activeHandler["global"]){
			$this->setVar(NULL,$this->data);
		}

		$this->data = array();
	}

	protected function successPOST()
	{
		$_SESSION[self::SR] = $this->session;

		Amslib_Website::redirect($this->getSuccessURL(),true);
	}

	protected function failurePOST()
	{
		$_SESSION[self::SR] = $this->session;

		Amslib_Website::redirect($this->getFailureURL(),true);
	}

	protected function successAJAX()
	{
		Amslib_Website::outputJSON($this->session,true);
	}

	protected function failureAJAX()
	{
		Amslib_Website::outputJSON($this->session,true);
	}

	protected function sanitiseURL($url)
	{
		//	Capture the http:// part so you can replace it afterwards
		$http = strpos($url,"http://") !== false ? "http://" : "";
		//	strip away the http:// part first, because it won't survive the reduceSlashes otherwise
		return $http.Amslib_File::reduceSlashes(str_replace("http://","",$url));
	}

	public function __construct()
	{
		//	FIXME: we are hardcoding a route "home" which might not exist, this could be a bad idea
		$default_url	=	Amslib_Router::getURL("home");
		$return_url		=	Amslib::rchop(Amslib::postParam("return_url",$default_url),"?");

		$this->setSuccessURL(Amslib::rchop(Amslib::postParam("success_url",$return_url),"?"));
		$this->setFailureURL(Amslib::rchop(Amslib::postParam("failure_url",$return_url),"?"));

		//	Reset the service data and session structures
		$this->data		=	array();
		$this->session	=	array(self::HD=>array());
		//	blank the appropriate session key to stop previous sessions overlapping
		Amslib::getSESSION(self::SR,false,true);
		//	NOTE: this "violates" mixing key types, but it's simpler than not doing it, so I'll "tolerate" it for this situation
		$this->showFeedback();
		$this->setAjax(Amslib::postParam("return_ajax",false));
	}
	
	static public function &getInstance()
	{
		static $instance = NULL;
	
		if($instance === NULL) $instance = new self();
	
		return $instance;
	}

	public function setAjax($status)
	{
		$this->isAJAX		=	$status;
		$this->successCB	=	$this->isAJAX ? "successAJAX" : "successPOST";
		$this->failureCB	=	$this->isAJAX ? "failureAJAX" : "failurePOST";
		
		//	When doing ajax calls, you should never show feedback on the next available page
		if($status) $this->hideFeedback();
	}

	public function setSuccessURL($url)
	{
		$this->successURL = $this->sanitiseURL($url);
	}

	public function getSuccessURL()
	{
		return $this->successURL;
	}

	public function setFailureURL($url)
	{
		$this->failureURL = $this->sanitiseURL($url);
	}

	public function getFailureURL()
	{
		return $this->failureURL;
	}

	public function setReturnURL($url)
	{
		$this->setSuccessURL($url);
		$this->setFailureURL($url);
	}

	public function setVar($key,$data)
	{
		if($key == NULL && is_array($data)){
			self::$var = array_merge(self::$var,$data);
		}else if(is_string($key) && strlen($key)){
			self::$var[$key] = $data;
		}
	}

	public function getVar($key=NULL)
	{
		if($key == NULL) return self::$var;
		
		return is_string($key) && strlen($key) && isset(self::$var[$key])
			? self::$var[$key]
			: NULL;
	}

	public function showFeedback()
	{
		$this->session[self::FB] = true;
	}

	public function hideFeedback()
	{
		$this->session[self::FB] = false;
	}

	public function setHandler($plugin,$object,$method,$record,$global,$failure)
	{
		//	here we store handlers loaded from the service path before we execute them.
		$this->handlerList[] = array(
			"plugin"	=>	$plugin,
			"object"	=>	$object,
			"method"	=>	$method,
			"record"	=>	$record,
			"global"	=>	$global,
			"failure"	=>	$failure
		);
	}

	public function runHandler($object,$method)
	{
		if(method_exists($object,$method)){
			return call_user_func(array($object,$method),$this,$this->source);
		}
		
		if(!is_object($object)){
			error_log(__METHOD__.": \$object parameter is not an object, ".Amslib::var_dump($object));
		}

		//	NOTE:	this might seem a little harsh, but it's a critical error, your object doesn't have
		//			the method you said it would, probably this means something in your code is broken
		//			and you need to know about it and fix it.
		die("FAILURE[p:".get_class($object)."][m:$method]-> method did not exist, so could not be called");
	}

	//	NOTE: the code is ready however the system is not
	//	NOTE: the problem is that service handlers are not programmed to understand the extra attributes
	//	NOTE: the other problem is that service import/export definitions are not programmed as well
	//	NOTE: so the idea will have to stay here until I can dedicate time to implementing those.
	public function runManagedHandler($rules,$object,$method)
	{
		//	This method needs to exist on the object to retrieve the validation rules
		$getRules = array($object,"getValidationRules");

		//	continue only if the method is available to call
		if(!method_exists($getRules)) return false;

		$rules = call_user_func($getRules,$rules);

		//	continue only if the rules are valid and a non-empty array
		if(!$rules || !is_array($rules) || empty($rules)) return false;

		//	Now lets execute the handler!
		$v = new Amslib_Validator($this->source);
		$v->addRules($rules);

		$s = $v->execute();
		$d = $v->getValidData();

		if($s){
			//	Set the source to the valid data
			$this->source = $d;

			//	Here we call the handler, this is a SUCCESS only handler, although the data might fail, the data was valid
			return $this->runHandler($object,$method);
		}else{
			$service->setValidationData($object,$d);
			$service->setValidationErrors($object,$v->getErrors());
		}

		$service->setDatabaseErrors($object,$object->getDBErrors());

		return false;
	}

	public function execute()
	{
		$state = false;

		foreach($this->handlerList as $h){
			//	TODO: investigate why h["plugin"] was returning false??
			$this->activeHandler = $h;

			//	Set the source to what was requested, or default in any other case to the $_POST array
			$this->source = isset($h["source"]) && $h["source"] == "get" ? $_GET : $_POST;

			//	Run the handler, either in managed or unmanaged mode
			$state = isset($h["managed"])
				? $this->runManagedHandler($h["managed"],$h["object"],$h["method"])
				: $this->runHandler($h["object"],$h["method"]);
			
			//	Store the result of the service and make ready to start a new service
			$this->storeData($state);

			//	OH NOES! we got brokens, have to stop here, cause something failed :(
			if($h["failure"] && !$state) break;
		}
		
		//	run the failure or success callback to send data back to the receiver
		call_user_func(array($this,$state ? $this->successCB : $this->failureCB));

		//	If you arrive here, something very seriously wrong has happened
		die("FAILURE[p:".get_class($plugin)."][m:$method]-> All services should terminate with redirect or json");
	}

	public function pluginToName($plugin)
	{
		if(is_object($plugin)) $plugin = get_class($plugin);
		if(!is_string($plugin) && !is_numeric($plugin)) $plugin = "__ERROR_PLUGIN_UNKNOWN__";

		return $plugin;
	}

	public function setValidationData($plugin,$data)
	{
		$this->data[$this->pluginToName($plugin)][self::VD] = $data;
	}

	public function setValidationErrors($plugin,$errors)
	{
		if(empty($errors)){
			$errors[] = "No Errors set and array was empty, perhaps validation failed because source was empty?";	
		}
		
		$this->data[$this->pluginToName($plugin)][self::VE] = $errors;
	}

	//	NOTE: Be careful with this method, you could be pushing secret data
	public function setDatabaseErrors($plugin,$errors)
	{
		if(!empty($errors)) $this->data[$this->pluginToName($plugin)][self::DB] = $errors;
	}

	public function setData($plugin,$name,$value)
	{
		$plugin = $this->pluginToName($plugin);
		
		if($name == NULL && is_array($value)){
			$this->data[$plugin][self::SD] = $value;	
		}else{
			$this->data[$plugin][self::SD][$name] = $value;
		}
	}
	
	public function getData($plugin,$name=NULL,$default=NULL)
	{
		$plugin = $this->pluginToName($plugin);
		
		if(!isset($this->data[$plugin])) return $default;
		
		$plugin = $this->data[$plugin];
					
		if($name == NULL) return $plugin[self::SD];

		return isset($plugin[self::SD][$name]) ? $plugin[self::SD][$name] : $default;
	}
	
	public function deleteData($plugin,$name=NULL)
	{
		$plugin	=	$this->pluginToName($plugin);
		$copy	=	NULL;
		
		if(isset($this->data[$plugin])){
			if($name && isset($this->data[$plugin][self::SD][$name])){
				$copy = $this->data[$plugin][self::SD][$name];
				
				unset($this->data[$plugin][self::SD][$name]);
			}else{
				$copy = $this->data[$plugin][self::SD];
				
				unset($this->data[$plugin][self::SD]);
			}
			
			//	clean up empty arrays in the return structure
			if(empty($this->data[$plugin])) unset($this->data[$plugin]);
		}

		return $copy;
	}
	
	public function moveData($dst,$src,$name=NULL)
	{
		$this->setData(
			$dst,
			$name,
			$this->deleteData($src,$name)
		);
	}

	public function setError($plugin,$name,$value)
	{
		$plugin = $this->pluginToName($plugin);
		
		if($name == NULL && is_array($value)){
			$this->data[$plugin][self::SE] = $value;
		}else{
			$this->data[$plugin][self::SE][$name] = $value;
		}
	}

	public function getError($plugin=NULL,$name=NULL)
	{
		if($plugin === NULL){
			$errors = array();

			foreach(array_keys($this->data) as $plugin){
				if(!isset($this->data[$plugin]) || !isset($this->data[$plugin][self::SE])){
					continue;
				}

				$errors[$plugin] = $name !== NULL && isset($this->data[$plugin][self::SE][$name])
					? $this->data[$plugin][self::SE][$name]
					: $this->data[$plugin][self::SE];
			}

			return $errors;
		}

		if($name === NULL && isset($this->data[$plugin])){
			return $this->data[$plugin][self::SE];
		}

		return $this->data[$plugin][self::SE][$name];
	}

	/*****************************************************************************
	 * 	STATIC API TO RETRIEVE SESSION DATA
	*****************************************************************************/
	static public function displayFeedback()
	{
		return self::$serviceData[self::FB];
	}

	static public function hasData($remove=true)
	{
		if(self::$serviceData === NULL || $remove == false) self::$serviceData = Amslib::sessionParam(self::SR,false,$remove);

		return self::$serviceData ? true : false;
	}

	static public function getRawData()
	{
		return self::$serviceData;
	}

	static public function countHandler()
	{
		return count(self::$serviceData[self::HD]);
	}

	static public function processHandler($id=0)
	{
		self::$handler = isset(self::$serviceData[self::HD][$id]) ? self::$serviceData[self::HD][$id] : array();
		error_log("HANDLERS = ".Amslib::var_dump(self::$handler,true));

		return array_keys(Amslib_Array::valid(self::$handler));
	}

	static public function getStatus()
	{
		$success = isset(self::$serviceData[self::SC]) ? self::$serviceData[self::SC] : false;

		//	WTF: why do I remove this? commenting it out for now
		//unset(self::$serviceData[self::SC]);

		return $success;
	}

	static public function getValidationData($plugin,$default=array())
	{
		return Amslib_Array::valid(self::getHandlerData($plugin,$default,self::VD));
	}

	static public function getValidationErrors($plugin,$default=false)
	{
		return self::getHandlerData($plugin,$default,self::VE);
	}

	static public function getServiceErrors($plugin,$default=false)
	{
		return self::getHandlerData($plugin,$default,self::SE);
	}

	static public function getServiceData($plugin,$default=false,$key=false)
	{
		$data = self::getHandlerData($plugin,$default,self::SD);

		return $key && $data && isset($data[$key]) ? $data[$key] : $data;
	}

	//	NOTE: Be careful with this method, it could leak secret data if you didnt sanitise it properly of sensitive data
	static public function getDatabaseErrors($plugin,$default=false)
	{
		return self::getHandlerData($plugin,$default,self::DB);
	}

	static public function getDatabaseMessage($plugin,$default=false)
	{
		return Amslib_Array::filterKey(self::getDatabaseErrors($plugin,$default),array("db_error","db_location"));
	}
	
	
	//////////////////////////////////////////////////////////////////
	//	DEPRECATED METHODS
	//////////////////////////////////////////////////////////////////
	public function setTemp($key,$value){	$this->setVar($key,$value);	}
	public function getTemp($key){ return $this->getVar($key); }
}
