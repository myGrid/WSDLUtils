<?php

	function parseNamespaces($el)
	{
			
			$sel = simplexml_import_dom($el);
			
			$nss = $sel->getDocNamespaces(true);
			$nspaces = array();
			
			foreach($nss as $prefix => $url)
			{
				$ns = new Namespace();
				$ns->setPrefix($prefix);
				$ns->setURI($url);
                
				array_push($nspaces, $ns);
			
			}
			
			
			return $nspaces;
			
	}
	
	function ServiceDescriptionOutput($service)
	{
		$output = '';
		
		
		$output = $output . '<hr />';
		$output = '<h1>' . $service->getName() . '</h1>';
		$output = $output . '<p><b>Namespace:</b> ' . $service->getNamespace() . '<p>';
        $output = $output . '<p><i>' . $service->getDocumentation() . '</i></p>';
		
		
		$ports = $service->getPorts();
		
		for($portcount = 0; $portcount < count($ports); $portcount++)
		{
			$port = $ports[$portcount];
            $output = $output . '<hr />';
			$output = $output . '<div class="metaOperations"><ul>';
			$output = $output . '<li><b>Port:</b> ' . $port->getName() . '</li>';
			$output = $output . '<li><b>Location:</b> ' . $port->getLocation() . '</li>';
			$output = $output . '<li><b>Protocol:</b> ' . $port->getProtocol() . '</li>';
			$output = $output . '<li><b>Default Style:</b> ' . $port->getStyle() . '</li></ul>';
			$output = $output . '</div>';
			
			$functions = $port->getFunctions();
			
			$output .= '<hr />';
			$output .= '<h1>Operations</h1>';
			
		
			for($functioncount = 0; $functioncount < count($functions); $functioncount++)
			{
				$func = $functions[$functioncount];
				$output .= '<div class="operation"><h2>' . $func->getName() . '</h2>';
                $output .= '<p><i>' . $func->getDocumentation() . '</i></p>';
				$output .= '<ul>';
				$output .= '<li><b>SOAP Action:</b> ' . $func->getAction() . '</li>';
				$inputMessage = $func->getInputMessage();
				if($inputMessage != NULL)
				{
					$output .= '<li><b>Input Message:</b> ' . $inputMessage->getName();
					
					$output .= '<ul>';
					$parray = NULL;
					$parray = $inputMessage->getParts();
					
					for($outerp = 0; $outerp < count($parray); $outerp++)
					{
							$outparam = $parray[$outerp];
							
							$output .= printMessagePart($outparam);
								
					}
					
					$output .= '</ul>';
				//input message stuff here 
					$output .= '</li>';
				}
				$outputMessage = $func->getOutputMessage();
				if($outputMessage != NULL)
				{
					$output .= '<li><b>Output Message:</b> ' . $outputMessage->getName();
					
					$output .= '<ul>';
					$parray = NULL;
					$parray = $outputMessage->getParts();
					
					for($outerp = 0; $outerp < count($parray); $outerp++)
					{
							$outparam = $parray[$outerp];
							
							$output .= printMessagePart($outparam);
					}
					
					$output .= '</ul>';

				//output message stuff here
					$output .= '</li>';
				}
				$faultMessage = $func->getFaultMessage();
				if($faultMessage != NULL)
				{
					$output .= '<li><b>Fault Message:</b> ' . $faultMessage->getName();
					
					$output .= '<ul>';
					$parray = NULL;
					$parray = $faultMessage->getParts();
					
					
					for($outerp = 0; $outerp < count($parray); $outerp++)
					{
							$outparam = $parray[$outerp];
						
							$output .= printMessagePart($outparam);
								
						
					}
					
					$output .= '</ul>';

				//fault message stuff here
					$output .= '</li>';
				}
				$output .= '</ul></div>';
			}
			
		}
		
		
	
	
		return $output;
		
	}
	
	function printMessagePart($msgp)
	{
		$rtnStr = '<li>' . $msgp->getName() . '<ul>';
		$rtnStr .= printType($msgp->getType()) . '</ul></li>';
		
		return $rtnStr;
	}	
	
	function printType($type)
	{
		$rtnStr = '';
		
	
		
		if($type != NULL)
		{
            
			$childType = $type->getType();
			
			if($childType != NULL)
			{
				
			$rtnStr .= '<li>' . $type->getName() . ' <i>type</i> ' . $childType->getName() . '<br /><ul>';
			$cels = $childType->getComplexElements();
			for($tcount = 0; $tcount < count($cels); $tcount++)
			{
				$rtnStr .= printType($cels[$tcount]);
			}
		
			$rtnStr .= '</ul></li>';
			}
			else
			{
				$rtnStr .= '<li>' . $type->getName();
				
				$rtnStr .= '</li>';
			}
		}
		
		return $rtnStr;
		
	}
	
	



?>