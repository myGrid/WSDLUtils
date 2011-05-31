<?php

class XMLSchema
{
	private $targetNamespace;
	private $namespaces = array();
	private $nestedSchemas = array();
	private $types = array();
	private $elements = array();
	
	public function __construct($targetNamespace)
	{
		
		$this->targetNamespace = $targetNamespace;	
		
	}	

    public function setTarget($targ)
    {
        $this->targetNamespace = $targ;
    }

	public function getTarget()
	{
		return $this->targetNamespace;
	}
	
	public function addNamespace($ns)
	{
		array_push($this->namespaces, $ns);
	}
	
	public function getNamespaces()
	{
		return $this->namespaces;
	}
	
	public function setNamespaces($nsarr)
	{
		$this->namespaces = $nsarr;
	}
	
	public function addType($type)
	{
		array_push($this->types, $type);
	}
	
	public function getTypes()
	{
		return $this->types;
	}
	
	public function addElement($element)
	{
		array_push($this->elements, $element);
	}
	
	public function getElements()
	{
		return $this->elements;
		
	}
	
	public function getType($t)
	{
		for($i = 0; $i < count($this->types); $i++)
		{
			$type = $this->types[$i];
			
			if($type->getName() == $t)
			{
				return $type;
			}
		}
		
		return NULL;
	}
	
	public function getElement($e)
	{
		for($i = 0; $i < count($this->elements); $i++)
		{
			$element = $this->elements[$i];
			
			if($element->getName() == $e)
			{
				return $element;
			}
		}
		return NULL;
	}
	
	public function getSchemas()
	{
		return $this->nestedSchemas;
	}
	
	public function addSchema($schema)
	{
		array_push($this->nestedSchemas, $schema);
	}

}

?>