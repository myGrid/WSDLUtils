<?php

// NON-PROXY OPTS
$opts = array (
		'http' => array (
				'user_agent' => 'PHP libxml agent' 
		) 
);

// PROXY OPTS
// $opts = array('http' => array('proxy' => 'http://127.0.0.1:8080', 'request_fulluri' => true, 'user_agent' => 'PHP libxml agent'));

$context = stream_context_create ( $opts );

$wsi_install_path = dirname ( __FILE__ ) . "/wsi_tools";
include_once ('./WSDLParser/WSDLParser.php');
include_once ('ValidatorWrapper.php');

if (((array_key_exists ( 'wsdl_uri', $_POST ) && ! is_null ( $_POST ['wsdl_uri'] )) && (array_key_exists ( 'method', $_POST ) && ! is_null ( $_POST ['method'] ))) || ((array_key_exists ( 'wsdl_uri', $_GET ) && ! is_null ( $_GET ['wsdl_uri'] )) && (array_key_exists ( 'method', $_GET ) && ! is_null ( $_GET ['method'] )))) {
	if (array_key_exists ( 'wsdl_uri', $_POST ) && ! is_null ( $_POST ['wsdl_uri'] )) {
		$wsdlURI = $_POST ['wsdl_uri'];
	} elseif (array_key_exists ( 'wsdl_uri', $_GET ) && ! is_null ( $_GET ['wsdl_uri'] )) {
		$wsdlURI = $_GET ['wsdl_uri'];
	}
	
	if (array_key_exists ( 'method', $_POST ) && ! is_null ( $_POST ['method'] )) {
		$method = $_POST ['method'];
	} elseif (array_key_exists ( 'method', $_GET ) && ! is_null ( $_GET ['method'] )) {
		$method = $_GET ['method'];
	}
	
	switch ($method) {
		case 'compare' :
			$dom = new DOMDocument ();
			libxml_set_streams_context ( $context );
			$dom->load ( "wsdl_cache.xml" );
			compareWSDL ( $wsdlURI );
			/*
			 * On calling compareWSDL for the first time the WSDL will hashed and cached for retrieval next time the WSDL is passed to the web service.
			 */
			break;
		case 'parse' :
			$dom = new DOMDocument ( '1.0', 'iso-8859-1' );
			parseWSDL ( $wsdlURI );
			break;
		default :
			echo 'Invalid method parameter, please use either "parse" or "compare"';
	}
} else {
	echo 'Please supply arguments "wsdl_uri" and "method" via POST or GET';
}
function parseWSDL($wsdlURI) {
	global $context;
	try {
		error_log ( "\nWSDLUtils: WSDL doc parsing started for: " . $wsdlURI );
		
		$parser = new WSDLParser ( $wsdlURI );
		$service = @$parser->parseFile ();
		
		$validator = new ValidatorWrapper ( $wsdlURI );
		$res = $validator->execute ();
		if ($res == 1) {
			$output = $validator->getMessages ();
			$service->setComplianceResult ( $output ["result"] );
			$service->setComplianceErrors ( $output ["errors"] );
			$service->setComplianceWarnings ( $output ["warnings"] );
			error_log ( "WSDLUtils: WSDL doc validation errors: " . $output ["errors"] );
			error_log ( "WSDLUtils: WSDL doc validation warnings: " . $output ["warnings"] );
		} else {
			$service->setComplianceResult ( $res );
		}
		
		error_log( "WSDLUtils: converting the parsed service element to XML." );
		serviceToXML ( $service );
	} catch ( Exception $ex ) {
		errorToXML ( $ex->getMessage () );
	}
}
function compareWSDL($wsdlURI) {
	global $dom;
	global $context;
	
	$hash = hashURI ( $wsdlURI );
	
	$nodeList = $dom->getElementsByTagName ( "wsdl" );
	
	$found = false;
	for($i = 0; $i < $nodeList->length; $i ++) {
		$wsdl_node = $nodeList->item ( $i );
		
		if ($wsdl_node->getAttribute ( "hash" ) == $hash) {
			$found = true;
			break;
		}
	}
	
	if ($found) {
		try {
			$remoteParser = new WSDLParser ( $wsdlURI );
			$remoteService = @$remoteParser->parseFile ();
			
			$originalParser = new WSDLParser ( "wsdl_cache/" . $hash . ".wsdl" );
			$originalService = @$originalParser->parseFile ();
			
			$compOut = compareServiceObjects ( $originalService, $remoteService );
			
			compareToXML ( $compOut );
			
			if (! is_null ( $compOut )) {
				// re-cache;
				$saveDOM = new DOMDocument ();
				libxml_set_streams_context ( $context );
				$saveDOM->load ( $wsdlURI );
				$saveDOM->save ( "wsdl_cache/" . $hash . ".wsdl" );
				chmod ( "wsdl_cache/" . $hash . ".wsdl", 0777 );
			}
		} catch ( Exception $ex ) {
			errorToXML ( $ex->getMessage () );
		}
	} else {
		try {
			// test for parse Errors
			$remoteParser = new WSDLParser ( $wsdlURI );
			$remoteService = @$remoteParser->parseFile ();
			// cache wsdl;
			$wsdl_dom = new DOMDocument ();
			libxml_set_streams_context ( $context );
			$wsdl_dom->load ( $wsdlURI );
			$wsdl_dom->save ( "wsdl_cache/" . $hash . ".wsdl" );
			chmod ( "wsdl_cache/" . $hash . ".wsdl", 0777 );
			// record caching;
			$dom_root = $dom->documentElement;
			$wsdl_el = $dom->createElement ( "wsdl" );
			$wsdl_el->setAttribute ( "hash", $hash );
			$dom_root->appendChild ( $wsdl_el );
			
			compareToXML ( NULL );
		} catch ( Exception $ex ) {
			errorToXML ( $ex->getMessage () );
		}
	}
	
	$dom->save ( "wsdl_cache.xml" );
}
function hashURI($wsdlURI) {
	return hash ( "md5", $wsdlURI );
}
function errorToXML($errorText) {
	header ( "content-type: application/xml; charset=ISO-8859-15" );
	
	$errorXML = new DOMDocument ();
	
	$wsdl_xml = $errorXML->createElement ( "wsdl" );
	$error_xml = $errorXML->createElement ( "parseError", $errorText );
	$wsdl_xml->appendChild ( $error_xml );
	
	$errorXML->appendChild ( $wsdl_xml );
	
	print $errorXML->saveXML ();
}
function compareToXML($compareMessages) {
	header ( "content-type: application/xml; charset=ISO-8859-15" );
	$compDOM = new DOMDocument ();
	
	$wsdl_xml = $compDOM->createElement ( "wsdl" );
	
	if (is_null ( $compareMessages )) {
		$result_xml = $compDOM->createElement ( "result", 0 );
		$wsdl_xml->appendChild ( $result_xml );
	} else {
		
		$result_xml = $compDOM->createElement ( "result", 1 );
		$wsdl_xml->appendChild ( $result_xml );
		
		$comps_xml = $compDOM->createElement ( "diffList" );
		
		$compares = explode ( "\n", $compareMessages );
		
		for($i = 0; $i < count ( $compares ); $i ++) {
			$change_xml = $compDOM->createElement ( "diff", $compares [$i] );
			$comps_xml->appendChild ( $change_xml );
		}
		
		$wsdl_xml->appendChild ( $comps_xml );
	}
	$compDOM->appendChild ( $wsdl_xml );
	
	print $compDOM->saveXML ();
}
function serviceToXML($serv) {
	header ( "content-type: application/xml; charset=ISO-8859-15" );
	
	global $dom;
	
	$wsdl_xml = $dom->createElement ( "wsdl" );
	
	$comp_res_xml = $dom->createElement ( "compliance_result", $serv->getComplianceResult () );
	$wsdl_xml->appendChild ( $comp_res_xml );
	error_log ( "WSDLUtils: adding the compliance_result element." );
	
	$comp_error_xml = $dom->createElement ( "compliance_errors", $serv->getComplianceErrors () );
	$wsdl_xml->appendChild ( $comp_error_xml );
	error_log ( "WSDLUtils: adding the compliance_errors element." );
	
	$comp_warn_xml = $dom->createElement ( "compliance_warnings", $serv->getComplianceWarnings () );
	$wsdl_xml->appendChild ( $comp_warn_xml );
	error_log ( "WSDLUtils: adding the compliance_warnings element." );
	
	$service_xml = $dom->createElement ( "service" );
	$service_xml->setAttribute ( "name", $serv->getName () );
	error_log ( "WSDLUtils: adding the service element." );
	
	$desc_xml = $dom->createElement ( "description", htmlentities ( $serv->getDescription () ) );
	$service_xml->appendChild ( $desc_xml );
	error_log ( "WSDLUtils: adding the service description element." );
	
	$namesp_xml = $dom->createElement ( "namespace", $serv->getNamespace () );
	$service_xml->appendChild ( $namesp_xml );
	error_log ( "WSDLUtils: adding the service namespace element." );
	
	$docs_xml = $dom->createElement ( "documentation", htmlentities ( $serv->getDocumentation () ) );
	$service_xml->appendChild ( $docs_xml );
	error_log ( "WSDLUtils: adding the service documentation element." );
	
	$ports_xml = $dom->createElement ( "ports" );
	
	$port_list = $serv->getPorts ();
	error_log ( "WSDLUtils: adding the service ports elements." );
	
	for($i = 0; $i < count ( $port_list ); $i ++) {
		$port = $port_list [$i];
		
		if (is_null($port)){
			error_log("WSDLUtils: port element is NULL." );
			continue;	
		}
		else{
			error_log("WSDLUtils: adding the service port element " . $port->getName() . ".");
		}
		$port_xml = $dom->createElement ( "port" );
		$port_xml->setAttribute ( "name", $port->getName () );
		
		$prot_xml = $dom->createElement ( "protocol", $port->getProtocol () );
		$port_xml->appendChild ( $prot_xml );
		
		$style_xml = $dom->createElement ( "style", $port->getStyle () );
		$port_xml->appendChild ( $style_xml );
		
		$loc_xml = $dom->createElement ( "location", $port->getLocation () );
		$port_xml->appendChild ( $loc_xml );
		
		$funcs_xml = $dom->createElement ( "operations" );
		
		$op_list = $port->getFunctions ();
		
		for($j = 0; $j < count ( $op_list ); $j ++) {
			$op = $op_list [$j];
			if (is_null($op)){
				error_log ( "WSDLUtils: operation element for port " . $port -> getName() . " is NULL." );
				continue;
			}
			else{
				error_log ( "WSDLUtils: adding the operation element " . $op -> getName() . " for port " . $port -> getName() . ".");
			}
			
			$op_xml = $dom->createElement ( "operation" );
			$op_xml->setAttribute ( "name", $op->getName () );
			
			$op_desc_xml = $dom->createElement ( "description", htmlentities ( $op->getOperationDescription () ) );
			$op_xml->appendChild ( $op_desc_xml );
			
			$act_xml = $dom->createElement ( "action" );
			$op_xml->appendChild ( $act_xml );
			
			$op_doc_xml = $dom->createElement ( "documentation", htmlentities ( $op->getDocumentation () ) );
			$op_xml->appendChild ( $op_doc_xml );
			
			$op_type_xml = $dom->createElement ( "type" );
			$op_xml->appendChild ( $op_type_xml );
			
			$messages_xml = $dom->createElement ( "messages" );
			
			// -----------------------------------------
			
			$inputMessage = $op->getInputMessage ();
			if (! is_null ( $inputMessage )) {
				$message_in_xml = $dom->createElement ( "inputmessage" );
				$message_in_xml->setAttribute ( "name", $inputMessage->getName () );
				
				$parts_in_xml = $dom->createElement ( "parts" );
				$message_in_xml->appendChild ( $parts_in_xml );
				
				$parts = $inputMessage->getParts ();
				for($k = 0; $k < count ( $parts ); $k ++) {
					$part = $parts [$k];
					
					$part_xml = $dom->createElement ( "part" );
					$part_xml->setAttribute ( "name", $part->getName () );
					
					$part_type_xml = typeToXML ( $part->getType () );
					
					$part_xml->appendChild ( $part_type_xml );
					
					$parts_in_xml->appendChild ( $part_xml );
				}
				
				$message_in_xml->appendChild ( $parts_in_xml );
				$messages_xml->appendChild ( $message_in_xml );
			}
			
			$outputMessage = $op->getOutputMessage ();
			if (! is_null ( $outputMessage )) {
				$message_out_xml = $dom->createElement ( "outputmessage" );
				$message_out_xml->setAttribute ( "name", $outputMessage->getName () );
				
				$parts_out_xml = $dom->createElement ( "parts" );
				$message_out_xml->appendChild ( $parts_out_xml );
				
				$parts = $outputMessage->getParts ();
				for($k = 0; $k < count ( $parts ); $k ++) {
					$part = $parts [$k];
					
					$part_xml = $dom->createElement ( "part" );
					$part_xml->setAttribute ( "name", $part->getName () );
					
					$part_type_xml = typeToXML ( $part->getType () );
					
					$part_xml->appendChild ( $part_type_xml );
					
					$parts_out_xml->appendChild ( $part_xml );
				}
				
				$message_out_xml->appendChild ( $parts_out_xml );
				$messages_xml->appendChild ( $message_out_xml );
			}
			
			$faultMessage = $op->getFaultMessage ();
			if (! is_null ( $faultMessage )) {
				$message_fault_xml = $dom->createElement ( "faultmessage" );
				$message_fault_xml->setAttribute ( "name", $faultMessage->getName () );
				
				$parts_fault_xml = $dom->createElement ( "parts" );
				$message_fault_xml->appendChild ( $parts_fault_xml );
				
				$parts = $faultMessage->getParts ();
				for($k = 0; $k < count ( $parts ); $k ++) {
					$part = $parts [$k];
					
					$part_xml = $dom->createElement ( "part" );
					$part_xml->setAttribute ( "name", $part->getName () );
					
					$part_type_xml = typeToXML ( $part->getType () );
					
					$part_xml->appendChild ( $part_type_xml );
					
					$parts_fault_xml->appendChild ( $part_xml );
				}
				
				$message_fault_xml->appendChild ( $parts_fault_xml );
				$messages_xml->appendChild ( $message_fault_xml );
			}
			
			$op_xml->appendChild ( $messages_xml );
			
			// -----------------------------------------
			
			$funcs_xml->appendChild ( $op_xml );
		}
		$port_xml->appendChild ( $funcs_xml );
		$ports_xml->appendChild ( $port_xml );
	}
	
	$service_xml->appendChild ( $ports_xml );
	$wsdl_xml->appendChild ( $service_xml );
	$dom->appendChild ( $wsdl_xml );
	
	print $dom->saveXML ();
}
function typeToXML($type) {
	global $dom;
	
	$type_xml = $dom->createElement ( "type" );
	$type_xml->setAttribute ( "name", $type->getName () );
	
	$desc = $type->getDescription ();
	if (! is_null ( $desc )) {
		$doc_xml = $dom->createElement ( "documentation", $desc );
		$type_xml->appendChild ( $doc_xml );
	}
	
	$childType = $type->getType ();
	if (! is_null ( $childType )) {
		$hasName = false;
		if (! is_null ( $childType->getName () )) {
			$hasName = true;
		}
		
		if ($hasName) {
			$child_type_xml = $dom->createElement ( "type" );
			$child_type_xml->setAttribute ( "name", $childType->getName () );
		}
		$nestedTypeList = $childType->getComplexElements ();
		for($i = 0; $i < count ( $nestedTypeList ); $i ++) {
			$complexElement = $nestedTypeList [$i];
			
			if ($hasName) {
				$child_type_xml->appendChild ( typeToXML ( $complexElement ) );
			} else {
				$type_xml->appendChild ( typeToXML ( $complexElement ) );
			}
		}
		
		if ($hasName) {
			$type_xml->appendChild ( $child_type_xml );
		}
	}
	
	return $type_xml;
}
function compareServiceObjects($old, $new) {
	if (is_null ( $old ) || is_null ( $new )) {
		return NULL;
	}
	$compareout = '';
	
	if ($old->getName () != $new->getName ()) {
		$compareout .= "Service name changed to: " . $new->getName () . "\n";
	}
	if ($old->getNamespace () != $new->getNamespace ()) {
		$compareout .= "Service namespace changed to: " . $new->getNamespace () . "\n";
	}
	
	$oldports = $old->getPorts ();
	$newports = $new->getPorts ();
	
	// unset($old);
	// unset($new);
	for($oldPortCount = 0; $oldPortCount < count ( $oldports ); $oldPortCount ++) {
		$portFound = false;
		$op = $oldports [$oldPortCount];
		
		for($newPortCount = 0; $newPortCount < count ( $newports ); $newPortCount ++) {
			$np = $newports [$newPortCount];
			if ($op->getName () == $np->getName ()) {
				$portFound = true;
				if ($op->getLocation () != $np->getLocation ()) {
					$compareout .= "Port named " . $np->getName () . " has new location: " . $np->getLocation () . "\n";
				}
				if ($op->getProtocol () != $np->getProtocol ()) {
					$compareout .= "Port named " . $np->getName () . " protocol is now: " . $np->getProtocol () . "\n";
				}
				if ($op->getStyle () != $np->getStyle ()) {
					$compareout .= "Port named " . $np->getName () . " style is now: " . $np->getStyle () . "\n";
				}
				// check functions
				
				$newfunctions = $np->getFunctions ();
				$oldfunctions = $op->getFunctions ();
				
				for($oldFunctionCount = 0; $oldFunctionCount < count ( $oldfunctions ); $oldFunctionCount ++) {
					$opFound = false;
					$of = $oldfunctions [$oldFunctionCount];
					
					for($newFunctionCount = 0; $newFunctionCount < count ( $newfunctions ); $newFunctionCount ++) {
						$nf = $newfunctions [$newFunctionCount];
						
						if ($of->getName () == $nf->getName ()) {
							$opFound = true;
							if ($of->getAction () != $nf->getAction ()) {
								$compareout .= "Operation named " . $nf->getName () . " has changed action to: " . $nf->getAction () . "\n";
							}
							
							if ($of->getType () != $nf->getType ()) {
								$compareout .= "Operation named " . $nf->getName () . " has changed type to: " . $nf->getType () . "\n";
							}
							
							// compare messages
							
							// compare input message
							$newInputMessage = $nf->getInputMessage ();
							$oldInputMessage = $of->getInputMessage ();
							$newOutputMessage = $nf->getOutputMessage ();
							$oldOutputMessage = $of->getOutputMessage ();
							$newFaultMessage = $nf->getFaultMessage ();
							$oldFaultMessage = $of->getFaultMessage ();
							
							if ($newInputMessage) {
								if ($newInputMessage->getName () == $oldInputMessage->getName ()) {
									$newParts = $newInputMessage->getParts ();
									$oldParts = $oldInputMessage->getParts ();
									
									// unset($newInputMessage);
									// unset($oldInputMessage);
									
									for($oPartCount = 0; $oPartCount < count ( $oldParts ); $oPartCount ++) {
										$partFound = false;
										$oPart = $oldParts [$oPartCount];
										
										for($nPartCount = 0; $nPartCount < count ( $newParts ); $nPartCount ++) {
											$nPart = $newParts [$nPartCount];
											
											if ($oPart->getName () == $nPart->getName ()) {
												$partFound = true;
												if ($oPart->getType () != $nPart->getType ()) {
													$compareout .= "Operation named " . $nf->getName () . " - Input message part type has changed" . "\n";
												}
												break;
											}
										}
										if (! $partFound) {
											$compareout .= "Operation named " . $nf->getName () . " - Input message part removed" . "\n";
										}
									}
									for($nPartCount = 0; $nPartCount < count ( $newParts ); $nPartCount ++) {
										$nPart = $newParts [$nPartCount];
										$partFound = false;
										
										for($oPartCount = 0; $oPartCount < count ( $oldParts ); $oPartCount ++) {
											$oPart = $oldParts [$oPartCount];
											
											if ($nPart->getName () == $oPart->getName ()) {
												$partFound = true;
												break;
											}
										}
										if (! $partFound) {
											$compareout .= "Operation named " . $nf->getName () . " - Input message part added." . "\n";
										}
									}
								} else {
									$compareout .= "Operation named " . $nf->getName () . " input message has changed name to: " . $newInputMessage->getName () . "\n";
								}
							}
							
							// compare output message
							
							if ($newOutputMessage) {
								if ($newOutputMessage->getName () == $oldOutputMessage->getName ()) {
									$newParts = $newOutputMessage->getParts ();
									$oldParts = $oldOutputMessage->getParts ();
									
									// unset($newOutputMessage);
									// unset($oldOutputMessage);
									
									for($oPartCount = 0; $oPartCount < count ( $oldParts ); $oPartCount ++) {
										$partFound = false;
										$oPart = $oldParts [$oPartCount];
										
										for($nPartCount = 0; $nPartCount < count ( $newParts ); $nPartCount ++) {
											$nPart = $newParts [$nPartCount];
											
											if ($oPart->getName () == $nPart->getName ()) {
												$partFound = true;
												if ($oPart->getType () != $nPart->getType ()) {
													$compareout .= "Operation named " . $nf->getName () . " - Output message part type has changed" . "\n";
												}
												break;
											}
										}
										if (! $partFound) {
											$compareout .= "Operation named " . $nf->getName () . " - Output message part removed" . "\n";
										}
									}
									for($nPartCount = 0; $nPartCount < count ( $newParts ); $nPartCount ++) {
										$nPart = $newParts [$nPartCount];
										$partFound = false;
										
										for($oPartCount = 0; $oPartCount < count ( $oldParts ); $oPartCount ++) {
											$oPart = $oldParts [$oPartCount];
											
											if ($nPart->getName () == $oPart->getName ()) {
												$partFound = true;
												break;
											}
										}
										if (! $partFound) {
											$compareout .= "Operation named " . $nf->getName () . " - Output message part added." . "\n";
										}
									}
								} else {
									$compareout .= "Operation named " . $nf->getName () . " output message has changed name to: " . $newOutputMessage->getName () . "\n";
								}
							}
							
							// compare fault message
							
							if ($newFaultMessage) {
								if ($newFaultMessage->getName () == $oldFaultMessage->getName ()) {
									$newParts = $newFaultMessage->getParts ();
									$oldParts = $oldFaultMessage->getParts ();
									
									// unset($newFaultMessage);
									// unset($oldFaultMessage);
									for($oPartCount = 0; $oPartCount < count ( $oldParts ); $oPartCount ++) {
										$partFound = false;
										$oPart = $oldParts [$oPartCount];
										
										for($nPartCount = 0; $nPartCount < count ( $newParts ); $nPartCount ++) {
											$nPart = $newParts [$nPartCount];
											
											if ($oPart->getName () == $nPart->getName ()) {
												$partFound = true;
												if ($oPart->getType () != $nPart->getType ()) {
													$compareout .= "Operation named " . $nf->getName () . " - Fault message part type has changed" . "\n";
												}
												break;
											}
										}
										if (! $partFound) {
											$compareout .= "Operation named " . $of->getName () . " - Fault message part removed" . "\n";
										}
									}
									for($nPartCount = 0; $nPartCount < count ( $newParts ); $nPartCount ++) {
										$nPart = $newParts [$nPartCount];
										$partFound = false;
										
										for($oPartCount = 0; $oPartCount < count ( $oldParts ); $oPartCount ++) {
											$oPart = $oldParts [$oPartCount];
											
											if ($nPart->getName () == $oPart->getName ()) {
												$partFound = true;
												break;
											}
										}
										if (! $partFound) {
											$compareout .= "Operation named " . $nf->getName () . " - Fault message part added." . "\n";
										}
									}
								} else {
									$compareout .= "Operation named " . $nf->getName () . " Fault message has changed name to: " . $newFaultMessage->getName () . "\n";
								}
							}
							break;
						}
					}
					if (! $opFound) {
						$compareout .= "Operation named " . $of->getName () . " has been removed." . "\n";
					}
				}
				
				for($newFunctionCount = 0; $newFunctionCount < count ( $newfunctions ); $newFunctionCount ++) {
					$opFound = false;
					$nf = $newfunctions [$newFunctionCount];
					
					for($oldFunctionCount = 0; $oldFunctionCount < count ( $oldfunctions ); $oldFunctionCount ++) {
						$of = $oldfunctions [$oldFunctionCount];
						if ($nf->getName () == $of->getName ()) {
							$opFound = true;
							break;
						}
					}
					if (! $opFound) {
						$compareout .= "An operation named " . $nf->getName () . " has been added." . "\n";
					}
				}
			}
		}
		if (! $portFound) {
			$compareout .= "Port at " . $op->getLocation () . " has been removed." . "\n";
		}
	}
	for($newPortCount = 0; $newPortCount < count ( $newports ); $newPortCount ++) {
		$np = $newports [$newPortCount];
		$portFound = false;
		for($oldPortCount = 0; $oldPortCount < count ( $oldports ); $oldPortCount ++) {
			$op = $oldports [$oldPortCount];
			
			if ($np->getName () == $op->getName ()) {
				$portFound = true;
				break;
			}
		}
		if (! $portFound) {
			$compareout .= "A port has been added at location: " . $np->getLocation () . "\n";
		}
	}
	
	if ($compareout == '') {
		
		return NULL;
	} else {
		
		return $compareout;
	}
}

?>
