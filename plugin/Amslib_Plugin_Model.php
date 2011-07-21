<?php 
class Amslib_Plugin_Model extends Amslib_Database_MySQL
{
	protected $table;
	protected $prefix;
	
	public function __construct()
	{
		parent::__construct(false);
		$this->copy(Amslib_Database::getSharedConnection());
		
		$this->table = array();
	}
	
	public function setTable($name,$value)
	{
		$this->table[$name] = $this->escape($value);
	}
	
	public function setTablePrefix($prefix)
	{
		foreach($this->table as &$t) $t = str_replace($this->prefix,$prefix,$t);
		
		$this->prefix = $prefix;
	}
}