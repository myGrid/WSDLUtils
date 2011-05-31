<?php

class MessagePart
{
	private $name;
	private $type;
	
	
	public function __construct()
	{
			
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function setName($n)
	{
		$this->name = $n;
	}
	
	public function getType()
	{
		return $this->type;
	}
	
	public function setType($t)
	{
		$this->type = $t;
	}
	
	
	
	
}
?>