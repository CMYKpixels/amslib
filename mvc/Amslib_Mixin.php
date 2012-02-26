<?php 
class Amslib_Mixin
{
	private $mixin = array();
	
	public function __call($name,$args)
	{
		if(in_array($name,array_keys($this->mixin))){
			return call_user_func_array(array($this->mixin[$name],$name),$args);
		}

		return false;
	}
	
	public function addMixin($object,$filterOut=array())
	{
		if(!is_array($filterOut)) $filterOut = array();
		
		if(get_class($object)){
			$list = get_class_methods($object);

			foreach($list as $m){
				//	Block some requested methods and then some obvious methods from being added to the mixin
				if(!empty($filterOut) && in_array($m,$filterOut) || in_array($m,array("__construct","getInstance"))){
					continue;
				}
				
				$this->mixin[$m] = $object;
			}
		}
		
		return $object;
	}
}