<?php

class Endpoint
{
	private $name = '';
	private $protocol = '';
	private $style = '';
	private $location = '';
	private $functions = array(); //contains function (WSDLFunction) objects
	
	public function __construct()
	{
		
	}
	
	public function getFunctions()
	{
		return $this->functions;
	}
	
	public function addFunction($func)
	{
		array_push($this->functions, $func);
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function setName($name)
	{
		$this->name = $name;
	}
	
	public function getProtocol()
	{
		return $this->protocol;
	}
	
	public function setProtocol($prot)
	{
		$this->protocol = $prot;
	}
	
	public function getStyle()
	{
		return $this->style;
	}
	
	public function setStyle($style)
	{
		$this->style = $style;
	}
	
	public function getLocation()
	{
		return $this->location;
	}
	
	public function setLocation($location)
	{
		$this->location = $location;
	}	
	
	
}
?>