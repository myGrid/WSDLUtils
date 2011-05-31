<?php

class XMLNamespace
{
	private $prefix;
	private $uri;
	
	public function __construct()
	{
		
	}
	
	public function setPrefix($pr)
	{
		$this->prefix = $pr;
	}
	
	public function getPrefix()
	{
		return $this->prefix;
	}
	
	public function setURI($uri)
	{
		$this->uri = $uri;
	}
	
	public function getURI()
	{
		return $this->uri;
	}	
	
}




?>
