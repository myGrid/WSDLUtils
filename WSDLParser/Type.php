<?php
//external type class

class Type
{
	private $name;
	private $namespace;
    private $groupType;
	private $complexElements = array();
	
	public function __construct()
	{
		
	}

    public function getGroupType()
    {
        return $this->groupType;
    }

    public function setGroupType($gt)
    {
        $this->groupType = $gt;
    }
	
	public function setNamespace($ns)
	{
		$this->namespace = $ns;
	}
	
	public function getNamespace()
	{
		return $this->namespace;
	}
	
	public function setName($n)
	{
		$this->name = $n;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function addComplexElement($el)
	{
		array_push($this->complexElements, $el);
	}
	
	public function getComplexElements()
	{
		return $this->complexElements;
	}
	
	
	
	
}

?>