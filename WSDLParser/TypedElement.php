<?php
//type class

class TypedElement
{
	private $name;
	private $type;
	private $namespace;
    private $description;
	
	
	public function __construct()
	{
			
	}
	
	public function setNamespace($ns)
	{
		$this->namespace = $ns;
	}
	
	public function getNamespace()
	{
		return $this->namespace;
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

    public function setDescription($desc)
    {
        $this->description = $desc;
    }

    public function getDescription()
	{
        return $this->description;
    }
	
	
	
}


?>