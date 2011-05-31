<?php

class Message
{
	private $parts = array(); //contains parameters.
	private $name;
	
	public function __construct()
	{
		
		
	}
	
	public function addPart($part)
	{
		array_push($this->parts, $part);	
	}
	
	public function getParts()
	{
		return $this->parts;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function setName($n)
	{
		$this->name = $n;
	}
	
	
}
?>