<?php

class WSDLXmlSchemaParser
{
	private $domdoc; //the full XML Document
	
	private $currentSchemaElement;
	private $schemaElements; //the XML Schema
	

	private $docSchema;
    private $currentSchema;//working schema
    private $backTrace = array();
 
	
	
	public function __construct($url)
	{
		global $context;
        try
        {
            $this->domdoc = new DOMDocument();
            libxml_set_streams_context($context);
            if($this->domdoc->load($url))
            {
            
                $this->schemaElements = $this->domdoc->getElementsByTagName('schema');

                if($this->schemaElements->length > 0)
                {


                    $this->docSchema = new XMLSchema($this->domdoc->documentElement->getAttribute('targetNamespace'));
                    $this->docSchema->setNamespaces(parseNamespaces($this->domdoc));

                    //$this->schema->setTarget($this->currentSchemaElement->getAttribute('targetNamespace'));
                }
            }
            else
            {
                throw new Exception('Failed to access XML Schema component, invalid URL : ' . $url);
            }
        }
        catch(Exception $ex)
        {
            throw $ex;
        }

		
	}	
	
	public function parse()
	{
		try
        {
            if(!is_null($this->schemaElements))
            {
                $sameElements = array();
                $differentElements = array();

                for($i = 0; $i < $this->schemaElements->length; $i++)
                {
                    $schemaElement = $this->schemaElements->item($i);
                    if($schemaElement->hasAttribute('targetNamespace'))
                    {
                        if($schemaElement->getAttribute('targetNamespace') == $this->docSchema->getTarget())
                        {
                            array_push($sameElements, $schemaElement);
                        }
                        else
                        {
                            array_push($differentElements, $schemaElement);
                        }
                    }
                    else
                    {
                        array_push($sameElements, $schemaElement);
                    }
                }

                for($i = 0; $i < count($differentElements); $i++)
                {
                        
                    $this->currentSchemaElement = $differentElements[$i];
                    $this->currentSchema = new XMLSchema($this->currentSchemaElement->getAttribute('targetNamespace'));
                    $this->currentSchema->setNamespaces(parseNamespaces($this->domdoc));

                    $this->parseImports();
                    $this->parseIncludes();
                  
                    $this->parseNamedTypes();
                  
                    $this->parseElements();
                    

                    $this->docSchema->addSchema($this->currentSchema);
                }

                for($i = 0; $i < count($sameElements); $i++)
                {

                    $this->currentSchemaElement = $sameElements[$i];
                    $this->currentSchema = new XMLSchema($this->currentSchemaElement->getAttribute('targetNamespace'));
                    $this->currentSchema->setNamespaces(parseNamespaces($this->domdoc));
                    
                    $this->parseImports();
                    $this->parseIncludes();
                   
                    $this->parseNamedTypes();
                   
                    $this->parseElements();
                   

                    $namespaces = $this->currentSchema->getNamespaces();

                    for($nscount = 0; $nscount < count($namespaces); $nscount++)
                    {
                        $ns = $namespaces[$nscount];

                        if(!in_array($ns, $this->currentSchema->getNamespaces()))
                        {
                            $this->docSchema->addNamespace($ns);
                        }
                    }
                   
                     //merge types
                    $types = $this->currentSchema->getTypes();
                    for($tcount = 0; $tcount < count($types); $tcount++)
                    {
                        $t = $types[$tcount];

                        $this->docSchema->addType($t);
                    }

                    //merge typed elements
                    $elements = $this->currentSchema->getElements();
                    for($ecount = 0; $ecount < count($elements); $ecount++)
                    {
                        $e = $elements[$ecount];

                        $this->docSchema->addElement($e);
                    }

                  //merge schemas
                        $schemas = $this->currentSchema->getSchemas();
                        for($scount = 0; $scount < count($schemas); $scount++)
                        {
                            $s= $schemas[$scount];

                            $this->docSchema->addSchema($s);
                        }

                }

                


            }
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
		
	}
	
	public function getSchema()
	{
		return $this->docSchema;
	}
	
	
	
	private function parseImports()
	{
		try
        {
		$importlist = $this->currentSchemaElement->getElementsByTagName('import');
		
		//echo $importlist->length;
		for($importcount = 0; $importcount < $importlist->length; $importcount++)
		{
			
			$importNode = $importlist->item($importcount);
			
			if($importNode->parentNode == $this->currentSchemaElement)
			{
                
				$url = $importNode->getAttribute('schemaLocation');
                //echo $url . '<br />';
                
				if(!is_null($url) && $url != '')
				{
                    $importParser = new WSDLXmlSchemaParser($url);
                    $importParser->parse();
                    $importSchema = $importParser->getSchema();
                    
                    if($importSchema->getTarget() == $this->currentSchema->getTarget())
                    {
                         //merge namespaces
                        $namespaces = $importSchema->getNamespaces();

                        for($nscount = 0; $nscount < count($namespaces); $nscount++)
                        {
                            $ns = $namespaces[$nscount];

                            if(!in_array($ns, $this->currentSchema->getNamespaces()))
                            {
                                $this->currentSchema->addNamespace($ns);
                            }
                        }

                            //merge types
                        $types = $importSchema->getTypes();
                        for($tcount = 0; $tcount < count($types); $tcount++)
                        {
                            $t = $types[$tcount];

                            $this->currentSchema->addType($t);
                        }

                        //merge typed elements
                        $elements = $importSchema->getElements();
                        for($ecount = 0; $ecount < count($elements); $ecount++)
                        {
                            $e = $elements[$ecount];

                            $this->currentSchema->addElement($e);
                        }
                       

                        //merge schemas
                        $schemas = $importSchema->getSchemas();
                        for($scount = 0; $scount < count($schemas); $scount++)
                        {
                            $s= $schemas[$scount];

                            $this->currentSchema->addSchema($s);
                        }
                    }
                    else
                    {
                    $this->currentSchema->addSchema($importParser->getSchema());
                    }
				}
			}			
		}
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
	}
	
	private function parseIncludes()
	{
        try{


		$includelist = $this->currentSchemaElement->getElementsByTagName('include');
		
		
		for($includecount = 0; $includecount < $includelist->length; $includecount++)
		{
			
			$includeNode = $includelist->item($includecount);
			
			if($includeNode->parentNode == $this->currentSchemaElement)
			{
				$url = $includeNode->getAttribute('schemaLocation');
				if(!is_null($url) && $url != '')
				{
				$includeParser = new WSDLXSDParser($url);
				@$includeParser->parse();

                $includeSchema = $includeParser->getSchema();
				//merge namespaces
				$namespaces = $includeSchema->getNamespaces();
				
				for($nscount = 0; $nscount < count($namespaces); $nscount++)
				{
					$ns = $namespaces[$nscount];
					
					if(!in_array($ns, $this->currentSchema->getNamespaces()))
					{
						$this->currentSchema->addNamespace($ns);
					}
				}
				
				
				//merge types
				$types = $includeSchema->getTypes();
				for($tcount = 0; $tcount < count($types); $tcount++)
				{
					$t = $types[$tcount];
					
					$this->currentSchema->addType($t);
				}
				
				//merge typed elements
				$elements = $includeSchema->getElements();
				for($ecount = 0; $ecount < count($elements); $ecount++)
				{
					$e = $elements[$ecount];
					
					$this->currentSchema->addElement($e);
				}
				
				//merge schemas
				$schemas = $includeSchema->getSchemas();
				for($scount = 0; $scount < count($schemas); $scount++)
				{
					$s= $schemas[$scount];
					
					$this->currentSchema->addSchema($s);
				}
				}
			}			
		}
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
	}
	
	private function parseNamedTypes()
	{
		try
        {
		$complexTypeList = $this->currentSchemaElement->getElementsByTagName('complexType');
		
		for($i = 0; $i < $complexTypeList->length; $i++)
		{
            
			$complexTypeNode = $complexTypeList->item($i);
			
			if($complexTypeNode->hasAttribute('name'))
			{
                //echo $complexTypeNode->getAttribute('name') . ' ';
                array_push($this->backTrace, $complexTypeNode->getAttribute('name'));

                $complexType = $this->parseComplexType($complexTypeNode);
                
				array_pop($this->backTrace);
                
				if(!is_null($complexType))
				{
					$this->currentSchema->addType($complexType);
				}
			
			}
		}
		
		$simpleTypeList = $this->currentSchemaElement->getElementsByTagName('simpleType');
		
		for($i = 0; $i < $simpleTypeList->length; $i++)
		{
			$simpleTypeNode = $simpleTypeList->item($i);
			
			if($simpleTypeNode->hasAttribute('name'))
			{
                array_push($this->backTrace, $simpleTypeNode->getAttribute('name'));
				$simpleType = $this->parseSimpleType($simpleTypeNode);
                array_pop($this->backTrace);
				
				if(!is_null($simpleType))
				$this->currentSchema->addType($simpleType);
			}
		}
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
	}
	
	private function parseComplexType($complexNode)
	{
        try
        {
		$complexType = new Type();

       
		if($complexNode->hasAttribute('name'))
		{
			$nameText  = $complexNode->getAttribute('name');
			
			$namesplit = split(':', $nameText);
			$complexType->setName($nameText);
			
			if(count($namesplit) == 1)
			{
				$ns = new Namespace();
				$ns->setURI($this->currentSchema->getTarget());
				$complexType->setNamespace($ns);
                
				
			}
			else
			{
				$nstext = $namesplit[0];
				
				$namespaces = $this->currentSchema->getNamespaces();
				for($i = 0; $i < count($namespaces); $i++)
				{
					$ns = $namespaces[$i];
					if($ns->getPrefix() == $nstext)
					{
						$complexType->setNamespace($ns);
						break;
					}
				}
                
			}
		}
		
		$elementlist = $complexNode->getElementsByTagName('element'); //list of the nested elements within the type
		for($i = 0; $i < $elementlist->length; $i++)
		{
			
			$elementNode = $elementlist->item($i);
			//echo $elementNode->getAttribute('name') . ' ';
			$element = $this->parseElement($elementNode);
			
			$complexType->addComplexElement($element);
        }
		
		return $complexType; 
								
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
	}
	
	private function parseSimpleType($simpleNode)
	{
        try
        {
		$simpleType = new Type();
		
		if($simpleNode->hasAttribute('name'))
		{
			$nameText  = $simpleNode->getAttribute('name');
			
			$namesplit = split(':', $nameText);
			$simpleType->setName($nameText);
			if(count($namesplit) == 1)
			{
				$ns = new Namespace();
				$ns->setURI($this->currentSchema->getTarget());
				$simpleType->setNamespace($ns);
			}
			else
			{
				$nstext = $namesplit[0];
				
				$namespaces = $this->currentSchema->getNamespaces();
				for($i = 0; $i < count($namespaces); $i++)
				{
					$ns = $namespaces[$i];
					if($ns->getPrefix() == $nstext)
					{
						$simpleType->setNamespace($ns);
						break;
					}
				}
			}	
		}
        
		//TODO: ADD SOMETHING A BIT MORE CLEVER HERE!
		$restrictionNode = $simpleNode->getElementsByTagName('restriction')->item(0);
		if(!is_null($restrictionNode))
        {
            $baseTypeElement = new TypedElement();
            $baseTypeElement->setName('restriction');
            $ns = new Namespace();
            $ns->setURI($this->currentSchema->getTarget());
            $baseTypeElement->setNamespace($ns);

            $baseText = $restrictionNode->getAttribute('base');

            $basesplit = split(':', $baseText);

            if(count($basesplit) > 1)
            {
                $baseType =  $this->findType($basesplit[0], $basesplit[1]);
            }
            else
            {
                $baseType = $this->findType(NULL, $basesplit[0]);
            }

            $baseTypeElement->setType($baseType);
            $simpleType->addComplexElement($baseTypeElement);
        }
        return $simpleType;
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
	}
	
	private function getNamespaceForPrefix($prefix)
	{
        try
        {
		$nss = $this->currentSchema->getNamespaces();
		for($i = 0; $i < count($nss); $i++)
		{
			$ns = $nss[$i];
			
			if($ns->getPrefix() == $prefix)
			{
				return $ns;
			}
		}
		return NULL;
        }
        catch(Exception $ex)
        {
            throw $ex;
            return NULL;
        }
	}

    public function getElementOnDemand($elementName)
    {
        try
        {

        
        $elements = $this->currentSchemaElement->getElementsByTagName('element');

		for($i = 0; $i < $elements->length; $i++)
		{
			$elementNode = $elements->item($i);

			if($elementNode->parentNode->nodeName == $this->currentSchemaElement->nodeName)
			{
                
                if($elementNode->hasAttribute('name'))
                {
                    
                    if($elementNode->getAttribute('name') == $elementName)
                    {
                        
                        return $this->parseElement($elementNode);
                    }
                }
			}
		}
        return NULL;
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }

    public function getTypeOnDemand($typeName)
    {
        try
        {

        
		$complexTypeList = $this->currentSchemaElement->getElementsByTagName('complexType');

		for($i = 0; $i < $complexTypeList->length; $i++)
		{
			$complexTypeNode = $complexTypeList->item($i);

			if($complexTypeNode->hasAttribute('name'))
			{
                if($complexTypeNode->getAttribute('name') == $typeName)
                {
                    return $this->parseComplexType($complexTypeNode);
                }

			}
		}

		$simpleTypeList = $this->currentSchemaElement->getElementsByTagName('simpleType');

		for($i = 0; $i < $simpleTypeList->length; $i++)
		{
			$simpleTypeNode = $simpleTypeList->item($i);

			if($simpleTypeNode->hasAttribute('name'))
			{
                if($simpleTypeNode->getAttribute('name') == $typeName)
                {
                    return $this->parseSimpleType($simpleTypeNode);
                }
				
			}
		}
        return NULL;
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }

	public function findElement($prefix, $elementName)
	{
      try
      {
		$element;
		if(!is_null($prefix))
		{
			$ns = $this->getNamespaceForPrefix($prefix);
			
			if(!is_null($ns))
			{
                
				if($ns->getURI() == $this->currentSchema->getTarget())
				{
              
					$element = $this->currentSchema->getElement($elementName);
					
				}
				else
				{
					$schemas = $this->currentSchema->getSchemas();
					
					for($i = 0; $i < count($schemas); $i++)
					{
						$schema = $schemas[$i];
						
						if($schema->getTarget() == $ns->getURI())
						{
                            
							$element = $schema->getElement($elementName);
							break;
						}
						
					}
				}
			}
			
			
		}
		else
		{
			
			$element = $this->currentSchema->getElement($elementName);
			
		}

        if(is_null($element))
        {
            $element = $this->getElementOnDemand($elementName);
        }

        if(is_null($element))
        {
            $element = new TypedElement();
            $element->setName($elementName);
            $element->setNamespace($ns);
        }
		//ADD SOMETHING HERE
		return $element;
      }
      catch(Exception $ex)
      {
          throw $ex;
          return NULL;
      }
	}
	
	public function findType($prefix, $typeName)
	{
        try
        {
		$type = NULL;
        if(!in_array($typeName, $this->backTrace))
        {
            array_push($this->backTrace, $typeName);
            if(!is_null($prefix))
            {
                $ns = $this->getNamespaceForPrefix($prefix);
                if(!is_null($ns))
                {
                    if($ns->getURI() == $this->currentSchema->getTarget())
                    {

                        $type = $this->currentSchema->getType($typeName);


                    }
                    else
                    {
                        $schemas = $this->currentSchema->getSchemas();

                        for($i = 0; $i < count($schemas); $i++)
                        {
                            $schema = $schemas[$i];

                            if($schema->getTarget() == $ns->getURI())
                            {

                                $type = $schema->getType($typeName);

                                break;
                            }

                        }

                    }
                }

                if(is_null($type))
                {
                    $type = $this->getTypeOnDemand($typeName);
                }

                if(is_null($type))
                {

                    $type = new Type();
                    $type->setName($typeName);
                    $type->setNamespace($ns);
                }
            }
            else
            {

                $type = $this->currentSchema->getType($typeName);

                if(is_null($type))
                {
                    $type = $this->getTypeOnDemand($typeName);
                }

                if(is_null($type))
                {
                    $type = new Type();
                    $type->setName($typeName);
                    $targetNS = new Namespace();
                    $targetNS->setURI($this->currentSchema->getTarget());
                    $type->setNamespace($targetNS);

                }
            }
            array_pop($this->backTrace);
        }
        else
        {
            $type = new Type();
            $type->setName($typeName);
            $targetNS = new Namespace();
            $targetNS->setURI($this->currentSchema->getTarget());
            $type->setNamespace($targetNS);
        }
		return $type;
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
		
	}	
	
	private function parseElement($elementNode)
	{
        try
        {
		$element = new TypedElement();
		$elementName = $elementNode->getAttribute('name');
        
		$namesplit = split(':', $elementName);
			
		if(count($namesplit) == 1)
		{
			$ns = new Namespace();
			$ns->setURI($this->currentSchema->getTarget());
			$element->setNamespace($ns);
			$element->setName($elementName);
		}
		else
		{
			$nstext = $namesplit[0];
			
			$namespaces = $this->currentSchema->getNamespaces();
			for($i = 0; $i < count($namespaces); $i++)
			{
				$ns = $namespaces[$i];
				if($ns->getPrefix() == $nstext)
				{
					$element->setNamespace($ns);
					break;
				}
			}
			$element->setName($namesplit[1]);
		}
	
		$annotationNodes = $elementNode->getElementsByTagName('annotation');
        if(!is_null($annotationNodes) && $annotationNodes->length > 0)
        {
            for($i = 0; $i < $annotationNodes->length; $i++)
            {
                $annoNode = $annotationNodes->item($i);
                $docNodes = $annoNode->getElementsByTagName('documentation');
                for($j = 0; $j < $docNodes->length; $j++)
                {
                    $docNode = $docNodes->item($j);
                    $element->setDescription($docNode->nodeValue);
                }
            }
        }
		$complexTypeNodes = $elementNode->getElementsByTagName('complexType');
		$simpleTypeNodes = $elementNode->getElementsByTagName('simpleType');
		if(!is_null($complexTypeNodes) && $complexTypeNodes->length > 0)
		{
			$complexNode = $complexTypeNodes->item(0);
			
			if($complexNode->parentNode == $elementNode)
			{
			
				$type = $this->parseComplexType($complexNode);
			
				$element->setType($type);
			}
		}
		elseif(!is_null($simpleTypeNodes) && $simpleTypeNodes->length > 0)
		{
			$simpleNode = $simpleTypeNodes->item(0);
			
			if($simpleNode->parentNode == $elementNode)
			{
				$type = $this->parseSimpleType($simpleNode);
				
				$element->setType($type);
				
			}
		}
		elseif($elementNode->hasAttribute('type'))
		{
			
			$elementType = $elementNode->getAttribute('type');
			
			$typeSplit = split(':', $elementType);
			
			if(count($typeSplit) > 1)
			{
                
				$type = $this->findType($typeSplit[0], $typeSplit[1]);
				
			}
			else
			{
                
				$type = $this->findType(NULL, $elementType);
				
			}
			
            
			$element->setType($type);
			
		}
		elseif($elementNode->hasAttribute('ref'))
		{
			$elementRef = $elementNode->getAttribute('ref');
			
			$refSplit = split(':', $elementRef);
			
			if(count($refSplit) > 1)
			{
				$refelement = $this->findElement($refSplit[0], $refSplit[1]);
				
				
			}
			else
			{
				$refelement = $this->findElement(NULL, $elementRef);
				
				
			}
			
           
                $element->setName($refelement->getName());
                $element->setType($refelement->getType());
            
		}
        
			return $element;
        }
        catch(Exception $ex)
        {
            throw $ex;
            return NULL;
        }
	}
	
	private function parseElements()
	{
        try
        {
		$elements = $this->currentSchemaElement->getElementsByTagName('element');
		
		for($i = 0; $i < $elements->length; $i++)
		{
			$elementNode = $elements->item($i);
			
			if($elementNode->parentNode->nodeName == $this->currentSchemaElement->nodeName)
			{
				$element = $this->parseElement($elementNode);	
				$this->currentSchema->addElement($element);
			}
		}
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
	}



    private function contains($str, $content, $ignorecase=true){
    if ($ignorecase){
        $str = strtolower($str);
        $content = strtolower($content);
    }
    
    if(strpos($str,$content) == false)
    {
        return false;

    }
    else
    {
        return true;
    }
}


	
	
	
}

?>