<?php


class Operation
{
	private $inputMessage;
	private $outputMessage;
	private $faultMessage;
	private $opType;
	private $opDescription; 
	private $action;
	private $name;
    private $documentation;
	
	public function __construct()
	{
		$this->determineType();	
	}	
	
	private function determineType()
	{
		if($this->inputMessage == NULL && $this->outputMessage != NULL)
		{
			$this->opType = 'Response Only';
		}
		elseif($this->inputMessage != NULL && $this->outputMessage == NULL)
		{
			$this->opType = 'Request Only';
		}
		elseif($this->inputMessage != NULL && $this->outputMessage != NULL)
		{
			$this->opType = 'Request - Response';
		}
		else
		{
			$this->opType = 'Undefined';
		}
	}

    public function getDocumentation()
    {
        return $this->documentation;
    }

    public function setDocumentation($doc)
    {
        $this->documentation = $doc;
    }

	public function getType()
	{
		return $this->opType;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function setName($n)
	{
		$this->name = $n;
	}
	
	public function getInputMessage()
	{
		
		return $this->inputMessage;
	}
	
	public function setInputMessage($msg)
	{
		$this->inputMessage = $msg;
		$this->determineType();
	}
	
	public function getOutputMessage()
	{
		return $this->outputMessage;
	}
	
	public function setOutputMessage($msg)
	{
		$this->outputMessage = $msg;
		$this->determineType();
	}
	
	public function getFaultMessage()
	{
		return $this->faultMessage;
	}
	
	public function setFaultMessage($msg)
	{
		$this->faultMessage = $msg;
		$this->determineType();
	}
	
	public function getOperationDescription()
	{
		return $this->opDescription;
	}
	
	public function setOperationDescription($desc)
	{
		$this->opDescription = $desc;
	}
	
	public function getAction()
	{
		return $this->action;
	}
	
	public function setAction($act)
	{
		$this->action = $act;
	}
	
}

?>