<?php
//auto load all classes.
require_once('Endpoint.php');
require_once('Message.php');
require_once('MessagePart.php');
require_once('Operation.php');
require_once('Service.php');
require_once('Type.php');
require_once('TypedElement.php');
require_once('WSDLXmlSchemaParser.php');
require_once('Functions.php');
require_once('Namespace.php');
require_once('XMLSchema.php');

class WSDLParser
{
	private $wsdlfile;
	private $XDoc;
	private $service;
	private $xsdParser;
	private $nspaces = array();
	private $errorString = '';
	
	
	public function __construct($url)
	{
		//set the file that is to be parsed and create the XML Reader
		
		//TODO: ADD SOMETHING TO SUPPORT COMPLEX CONTENT ETC.
		try{
        global $context;
        
		$this->wsdlfile = $url;
		$this->XDoc = new DOMDocument();
        //libxml_set_streams_context($context);
		if($this->XDoc->load($this->wsdlfile))
		{
            
			$this->service = new Service();
			$this->xsdParser = new WSDLXmlSchemaParser($this->wsdlfile);
		}
		else
		{
            
			throw new Exception('Failed to access WSDL document, invalid URL : ' . $this->wsdlfile);
		}
		}
		catch(Exception $ex)
		{
            $this->errorString = $ex->getMessage();
            throw $ex;
			
		}
		
		
	}
	
	public function getErrorString()
	{
		return $this->errorString;
	}
	
	public function parseFile()
	{
		try
		{
			$this->errorString = '';
			$this->nspaces = parseNamespaces($this->XDoc);
			$typesdefined = $this->XDoc->getElementsByTagName('schema');
			if($typesdefined->item(0) != NULL && $typesdefined->length > 0)
			{ 
				$this->xsdParser->parse();
               
			}
			$this->parseService();
			return $this->service;
		}
		catch(Exception $ex)
		{
            
            $this->errorString = $ex->getMessage();
			throw $ex;
		}
	}
	

	
	private function parseService()
	{
        try
        {
            $serviceNodeList = $this->XDoc->getElementsByTagName('service');

            $serviceNode = $serviceNodeList->item(0);
            $serviceName = $serviceNode->getAttribute('name');
            $this->service->setName($serviceName);
            
            
            $docNodes = $serviceNode->getElementsByTagName('documentation');
            if($docNodes->length > 0)
            {
                $docNode = $docNodes->item(0);
                $this->service->setDocumentation($docNode->nodeValue);
            }
            $defNodeList = $this->XDoc->getElementsByTagName('definitions');
            $defNode = $defNodeList->item(0);
            $targetNS = $defNode->getAttribute('targetNamespace');
            $this->service->setNamespace($targetNS);

            
            $this->parsePorts($serviceNode);
        }
        catch(Exception $ex)
        {
            throw new Exception("An error occurred whilst parsing the service. ");
        }
		
	}
	
	private function parsePorts($serviceNode)
	{
        try
        {
            $portsNodeList = $serviceNode->getElementsByTagName('port');


            for($count = 0; $count < $portsNodeList->length; $count++)
            {
                $port = new Endpoint();
                $portNode = $portsNodeList->item($count);
                $portName = $portNode->getAttribute('name');
                $port->setName($portName);
                $locationNodeList = $portNode->getElementsByTagName('address');

                if($locationNodeList->length > 0)
                {
                    $locationNode = $locationNodeList->item(0);
                    $location = $locationNode->getAttribute('location');
                    $port->setLocation($location);
                }

                $binding = $portNode->getAttribute('binding');

                if($binding != NULL)
                {
                    $split = split(":", $binding);

                    if(count($split) >  1)
                    {
                        $binding = $split[1];
                    }
                }
                $bindingNodeList = $this->XDoc->getElementsByTagName('binding');

                for($countA = 0; $countA < $bindingNodeList->length; $countA++)
                {
                    $bindingNode = $bindingNodeList->item($countA);

                    if($bindingNode->getAttribute('name') == $binding)
                    {
                        //need to check for soap

                        $soapNodeList = $bindingNode->getElementsByTagName('binding');
                        if($soapNodeList->length > 0)
                        {
                            $soapNode = $soapNodeList->item(0);
                            $style = $soapNode->getAttribute('style');
                            $port->setStyle($style);
                            $protocol = $soapNode->getAttribute('transport');
                            $port->setProtocol($protocol);
                        }
                        //echo'About to parse operations.<br />';
                        $this->parseOperations($port, $bindingNode);

                        $this->service->addPort($port);
                        //echo'Port Added<br />';
                    }
                }



            }
        }
        catch(Exception $ex)
        {
            throw new Exception("An error occurred whilst parsing ports. ");
        }
		
	}
	
	
	private function parseOperations($port, $bindingNode)
	{
		try
        {
            $bindOpNodeList = $bindingNode->getElementsByTagName('operation');

            for($count = 0; $count < $bindOpNodeList->length; $count+=2)
            {
                $bindOpNode = $bindOpNodeList->item($count);

                $portTypes = $this->XDoc->getElementsByTagName('portType');

                for($typecount = 0; $typecount < $portTypes->length; $typecount++)
                {
                    $portTypeNode = $portTypes->item($typecount);
                    $portType = $portTypeNode->getAttribute('name');

                    $bindingNodeType = $bindingNode->getAttribute('type');
                    $split = split(":", $bindingNodeType);
                    if(count($split) > 1)
                    {
                        $bindingNodeType = $split[1];
                    }
                    else
                    {
                        $bindingNodeType = $split[0];
                    }
                    if($bindingNodeType == $portType)
                    {

                        $operationNodeList = $portTypeNode->getElementsByTagName('operation');

                        for($opcount = 0; $opcount < $operationNodeList->length; $opcount++)
                        {
                            $operation = $operationNodeList->item($opcount);
                            if($bindOpNode->getAttribute('name') == $operation->getAttribute('name'))
                            {

                            $func = new Operation();
                            $func->setName($operation->getAttribute('name'));


                         
                            $docNodes = $operation->getElementsByTagName('documentation');
                            if($docNodes->length > 0)
                            {
                                $docNode = $docNodes->item(0);
                                $func->setDocumentation($docNode->nodeValue);
                            }
                            

                            $actionNodeList = $bindOpNode->getElementsByTagName('operation');
                            if($actionNodeList->length > 0)
                            {
                                $actionNode = $actionNodeList->item(0);
                                $func->setAction($actionNode->getAttribute('soapAction'));
                            }
                            /*$docNodeList = $operation->getElementsByTagName('documentation');
                            $docNode = $docNodeList->item(0);
                            $func->setOperationDescription($docNode
                            */

                            $this->parseInputMessage($func, $operation, $bindOpNode);
                            $this->parseOutputMessage($func, $operation, $bindOpNode);
                            $this->parseFaultMessage($func, $operation, $bindOpNode);

                            $port->addFunction($func);
                            break;
                            }
                            //echo'Operation Added<br />';
                        }
                        break;
                    }
                }

            }
        }
        catch(Exception $ex)
        {
            throw new Exception("An error occurred whilst parsing operations. ");
        }
	}
	
	private function parseInputMessage($functionObj, $operationNode, $bindOpNode)
	{
        try
        {
            $inputMessageList = $operationNode->getElementsByTagName('input');
            $inputMessageName = '';
            if($inputMessageList->length > 0)
            {
                $inputMessage = new Message();
                $inputMessageNode = $inputMessageList->item(0);
                $inputMessageName = $inputMessageNode->getAttribute('message');
                $splitName = split(":", $inputMessageName);
                if(count($splitName) > 1)
                {
                    $inputMessageName = $splitName[1];
                }
                else
                {
                    $intputMessageName = $splitName[0];
                }

                $inputMessage->setName($inputMessageName);

                $this->parseAttributes($inputMessage);

                $functionObj->setInputMessage($inputMessage);

            }
        }
        catch(Exception $ex)
        {
            throw new Exception("An error occurred whilst parsing input message. ");
        }
		
	}
	
	private function parseOutputMessage($functionObj, $operationNode, $bindOpNode)
	{
        try
        {
            $outputMessageList = $operationNode->getElementsByTagName('output');
            $outputMessageName = '';
            if($outputMessageList->length >0)
            {
                $outputMessage = new Message();
                $outputMessageNode = $outputMessageList->item(0);
                $outputMessageName = $outputMessageNode->getAttribute('message');
                $splitName = split(":", $outputMessageName);
                if(count($splitName) > 1)
                {
                    $outputMessageName = $splitName[1];
                }
                else
                {
                    $outputMessageName = $splitName[0];
                }
                $outputMessage->setName($outputMessageName);

                $this->parseAttributes($outputMessage);

                $functionObj->setOutputMessage($outputMessage);

            }
        }
        catch(Exception $ex)
        {
            throw new Exception("An error occurred whilst parsing output message. ");
        }

		
	}
	
	private function parseFaultMessage($functionObj, $operationNode, $bindOpNode)
	{
        try
        {
            $faultMessageList = $operationNode->getElementsByTagName('fault');
            $faultMessageName = '';
            if($faultMessageList->length >0)
            {
                $faultMessage = new Message();
                $faultMessageNode = $faultMessageList->item(0);
                $faultMessageName = $faultMessageNode->getAttribute('message');
                $splitName = split(":", $faultMessageName);
                if(count($splitName) > 1)
                {
                    $faultMessageName = $splitName[1];
                }
                else
                {
                    $faultMessageName = $splitName[0];
                }

                $faultMessage->setName($faultMessageName);

                $this->parseAttributes($faultMessage);

                $functionObj->setFaultMessage($faultMessage);


            }
        }
        catch(Exception $ex)
        {
            throw new Exception("An error occured whilst parsing fault message.");
        }
		
		
	}
	
	private function parseAttributes($message)
	{
        try
        {
		$messageList = $this->XDoc->getElementsByTagName('message');
		
		
		for($mcount = 0; $mcount < $messageList->length; $mcount++)
		{
			$messageNode = $messageList->item($mcount);
			
			if($messageNode->getAttribute('name') == $message->getName())
			{
				
				$paramlist = $messageNode->getElementsByTagName('part');
				
				for($pcount = 0; $pcount < $paramlist->length; $pcount++)
				{
					$paramNode = $paramlist->item($pcount);
					
					$param = new MessagePart();
					
					$param->setName($paramNode->getAttribute('name'));
				
				
					
					if($paramNode->hasAttribute('element'))
					{					
						$elsplit = split(':', $paramNode->getAttribute('element'));
						$ns = '';
						if(count($elsplit) > 0)
						{
							$elstr = $elsplit[1];
							$ns = $elsplit[0];
							
							$type = $this->xsdParser->findElement($ns, $elstr);
						}
						else
						{
							$elstr = $elsplit[0];
							$type = $this->xsdParser->findElement(NULL, $elstr);
						}
						
						
						
						
					
					}
					
					if($paramNode->hasAttribute('type'))
					{
						$elsplit = split(':', $paramNode->getAttribute('type'));
						
						$type = new TypedElement();
						if(count($elsplit) > 0)
						{
							$elstr = $elsplit[1];
							
							
							$type->setName($elstr);
							
						}
						else
						{
							$elstr = $elsplit[0];
							
							$type->setName($elstr);
							
						}
						
												
						
						
					}
					
						
					
						
					$param->setType($type);	
							
					$message->addPart($param);				
				}
				
				
				break;
			}
		}
	}
					
    
    catch(Exception $ex)
    {
        throw new Exception("An error occurred whilst parsing message parts. ");
    }
	
	
	
}
}

?>