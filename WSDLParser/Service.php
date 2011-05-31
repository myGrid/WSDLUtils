<?php

require_once('Functions.php');

class Service
{
	private $name;
	private $description;
	private $namespace;
	private $ports = array(); //contains port (Endpoint) descriptions.
    private $documentation;
    private $complianceResult;
    private $complianceErrors;
    private $complianceWarnings;
	
	public function __construct()
	{
		
	}

    public function getComplianceResult()
    {
        return $this->complianceResult;
    }

    public function getComplianceErrors()
    {
        return $this->complianceErrors;
    }

    public function setComplianceResult($res)
    {
        $this->complianceResult = $res;
    }

    public function setComplianceErrors($out)
    {
        $this->complianceErrors = $out;
    }

    public function getComplianceWarnings()
    {
        return $this->complianceWarnings;
    }

    public function setComplianceWarnings($out)
    {
        $this->complianceWarnings = $out;
    }


    public function getDocumentation()
    {
        return $this->documentation;
    }

    public function setDocumentation($doc)
    {
        $this->documentation = $doc;
    }
	
	public function getNamespace()
	{
		return $this->namespace;
	}
	
	public function setNamespace($ns)
	{
		$this->namespace = $ns;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function setName($n)
	{
		$this->name = $n;
	}
	
	public function getDescription()
	{
		return $this->description;
	}
	
	public function setDescription($desc)
	{
		$this->description = $desc;
	}
	
	public function addPort($port)
	{
		array_push($this->ports, $port);
	}
	
	public function getPorts()
	{
		return $this->ports;
	}	
	
	public function outputHTMLDescription()
	{
		return ServiceDescriptionOutput($this);	
	}
	
	
}
?>