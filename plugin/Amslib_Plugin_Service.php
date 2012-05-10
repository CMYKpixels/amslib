<?php
class Amslib_Plugin_Service
{
	const SR = "/amslib/service";
	const VD = "validation/data";
	const VE = "validation/errors";
	const SD = "service/data";
	const SE = "service/errors";
	const FB = "service/feedback";
	const DB = "database/errors";
	const PL = "plugins";
	const SC = "success";

	protected $successURL;
	protected $failureURL;
	protected $successCB;
	protected $failureCB;
	protected $isAJAX;
	protected $data;
	protected $session;

	//	Used in the website to retrieve the session data after processing
	static protected $serviceData = NULL;
	static protected $showFeedback;

	static protected function getData($plugin,$default,$key)
	{
		return isset(self::$serviceData[$plugin]) && isset(self::$serviceData[$plugin][$key])
			? Amslib_Array::valid(self::$serviceData[$plugin][$key])
			: $default;
	}

	protected function storeData($status)
	{
		$this->data[self::SC] = $status;
		$this->session[] = $this->data;

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
		$this->session	=	array();
		//	NOTE: this "violates" mixing key types, but it's simpler than not doing it, so I'll "tolerate" it for this situation
		$this->showFeedback();
		$this->setAjax(Amslib::postParam("return_ajax",false));
	}

	public function setAjax($status)
	{
		$this->isAJAX		=	$status;
		$this->successCB	=	$this->isAJAX ? "successAJAX" : "successPOST";
		$this->failureCB	=	$this->isAJAX ? "failureAJAX" : "failurePOST";
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

	public function setTemp($key,$data)
	{
		Amslib_Keystore::set($key,$data);
	}

	public function getTemp($key)
	{
		return Amslib_Keystore::get($key);
	}

	public function showFeedback()
	{
		$this->session[self::FB] = true;
	}

	public function hideFeedback()
	{
		$this->session[self::FB] = false;
	}

	public function setHandler($plugin,$object,$method)
	{
		//	here we store handlers before we execute them.
		/*
		we need to call the method on the object from the plugin, sending service,source like normal
		*/
		$this->handler[] = array("plugin"=>$plugin,"object"=>$object,"method"=>$method);
	}

	public function runHandler($object,$method)
	{
		if(method_exists($object,$method)){
			return call_user_func(array($object,$method),$this,$_POST);
		}

		//	NOTE:	this might seem a little harsh, but it's a critical error, your object doesn't have
		//			the method you said it would, probably this means something in your code is broken
		//			and you need to know about it and fix it.
		die("FAILURE[p:".get_class($object)."][m:$method]-> method did not exist, so could not be called");
	}

	public function execute()
	{
		$state = false;

		foreach($this->handler as $h){
			//	TODO: investigate why h["plugin"] was returning false??
			$state = $this->runHandler($h["object"],$h["method"]);

			//	Store the result of the service and make ready to start a new service
			$this->storeData($state);

			//	OH NOES! we got brokens, have to stop here, cause something failed :(
			if(!$state) break;
		}

		//	run the failure or success callback to send data back to the receiver
		call_user_func(array($this,$state ? $this->successCB : $this->failureCB));

		//	If you arrive here, something very seriously wrong has happened
		die("FAILURE[p:".get_class($plugin)."][m:$method]-> All services should terminate with redirect or json");
	}

	public function setValidationData($plugin,$data)
	{
		$this->data[$plugin][self::VD] = $data;
		$this->data[self::PL][$plugin] = true;
	}

	public function setValidationErrors($plugin,$errors)
	{
		$this->data[$plugin][self::VE] = $errors;
		$this->data[self::PL][$plugin] = true;
	}

	//	NOTE: Be careful with this method, you could be pushing secret data
	public function setDatabaseErrors($plugin,$errors)
	{
		if(!empty($errors)){
			$this->data[$plugin][self::DB] = $errors;
			$this->data[self::PL][$plugin] = true;
		}
	}

	public function setData($plugin,$name,$value)
	{
		$this->data[$plugin][self::SD][$name] = $value;
		$this->data[self::PL][$plugin] = true;
	}

	public function setError($plugin,$name,$value)
	{
		$this->data[$plugin][self::SE][$name] = $value;
		$this->data[self::PL][$plugin] = true;
	}

	/*****************************************************************************
	 * 	STATIC API TO RETRIEVE SESSION DATA
	*****************************************************************************/
	static public function displayFeedback()
	{
		if(self::$showFeedback){
			self::$showFeedback = self::$serviceData[self::FB];
			unset(self::$serviceData[self::FB]);
		}

		return self::$showFeedback;
	}

	static public function hasData($remove=true)
	{
		if(self::$serviceData === NULL) self::$serviceData = Amslib::sessionParam(self::SR,false,$remove);

		return self::$serviceData ? true : false;
	}

	static public function getRawData()
	{
		return self::$serviceData;
	}

	static public function getStatus()
	{
		$success = isset(self::$serviceData[self::SC]) ? self::$serviceData[self::SC] : false;

		unset(self::$serviceData[self::SC]);

		return $success;
	}

	static public function listPlugins()
	{
		return isset(self::$serviceData[self::PL]) ? array_keys(self::$serviceData[self::PL]) : array();
	}

	static public function getValidationData($plugin,$default=false)
	{
		return self::getData($plugin,$default,self::VD);
	}

	static public function getValidationErrors($plugin,$default=false)
	{
		return self::getData($plugin,$default,self::VE);
	}

	static public function getServiceErrors($plugin,$default=false)
	{
		return self::getData($plugin,$default,self::SE);
	}

	static public function getServiceData($plugin,$default=false,$key=false)
	{
		$data = self::getData($plugin,$default,self::SD);

		return $key && $data && isset($data[$key]) ? $data[$key] : $data;
	}

	//	NOTE: Be careful with this method, it could leak secret data if you didnt sanitise it properly of sensitive data
	static public function getDatabaseErrors($plugin,$default=false)
	{
		return self::getData($plugin,$default,self::DB);
	}

	static public function getDatabaseMessage($plugin,$default=false)
	{
		return Amslib_Array::filterKey(self::getDatabaseErrors($plugin,$default),array("db_error","db_location"));
	}
}