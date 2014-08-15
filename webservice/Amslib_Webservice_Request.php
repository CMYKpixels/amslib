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
* Contributors/Author:
*    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
*
*******************************************************************************/

class Amslib_Webservice_Request
{
	protected $url;
	protected $params;
	protected $sharedSession;
	protected $debug;

	public function __construct($url,$params=array(),$sharedSession=false)
	{
		$this->setURL($url);
		$this->setParameters($params);
		$this->setSharedSessionState($sharedSession);
		$this->setDebugState(false);
	}

	public function setDebugState($state)
	{
		$this->debug = !!$state;
	}

	public function setURL($url)
	{
		$this->url = $url;
	}

	public function setParameters($parameters)
	{
		$this->parameteres = $parameters;
	}

	public function setSharedSessionState($state)
	{
		$this->sharedSession = $state;
	}

	public function execute($raw=false)
	{
		$reply = $exception = false;

		if($this->debug){
			$raw = true;
		}

		try{
			if(strlen($this->url) == 0) throw new Exception("webservice url was invalid");

			$params = http_build_query(Amslib_Array::valid($this->params));

			$curl = curl_init();

			if($this->sharedSession){
				$key_remote		= "/amslib/webservice/session/remote/";
				$key_request	= "/amslib/webservice/session/request/";

				$id_session = Amslib_SESSION::get($key_remote);

				if($id_session){
					$cookie = "PHPSESSID=$id_session; path=".Amslib_Router_URL::getFullURL();
					curl_setopt($curl,CURLOPT_COOKIE,$cookie);
				}else{
					$params[$key_request] = true;
				}
			}

			curl_setopt($curl,CURLOPT_URL,				$this->url);
			curl_setopt($curl,CURLOPT_POST,				true);
			curl_setopt($curl,CURLOPT_HTTP_VERSION,		1.0);
			curl_setopt($curl,CURLOPT_RETURNTRANSFER,	true);
			curl_setopt($curl,CURLOPT_HEADER,			false);
			curl_setopt($curl,CURLOPT_POSTFIELDS,		$params);

			$reply = curl_exec($curl);
			curl_close($curl);

			$response = new Amslib_Webservice_Response();
			$response->setState("raw",$raw);
			$response->setResponse($reply);

			if($this->sharedSession && !$id_session){
				$data = $response->getRawData();
				if(isset($data[$key_remote])){
					Amslib_SESSION::set($key_remote,$data[$key_remote]);
				}
			}

			return $response;
		}catch(Exception $e){
			$exception = $e->getMessage();
		}

		Amslib::errorLog(
			"EXCEPTION: ",		$exception,
			"WEBSERVICE URL: ",	$this->url,
			"PARAMS: ",			$params,
			"DATA: ",			$reply
		);

		return false;
	}
}