<?php



class ValidatorWrapper
{
    
    private $VALIDATION_INCOMPLETE = 2;
    private $VALIDATION_COMPLETE_FAIL = 1;
    private $VALIDATION_COMPLETE_PASS = 0;
    private $uri;
    private $state;
    private $analyzer_error;

    public function __construct($inURI)
    {
        global $wsi_install_path;
        $this->uri = $inURI;
        $this->state = -1;
        $this->analyzer_error = '';
        putenv('JAVA_HOME=/Library/Java/Home');
        putenv('WSI_HOME=' . $wsi_install_path. '/wsi-test-tools');
        putenv('XMLBEANS_HOME=' . $wsi_install_path . '/xmlbeans-2.4.0');
        putenv('PATH=' . getenv('PATH') . ':' . getenv('XMLBEANS_HOME') . '/bin');
        putenv('CLASSPATH=' . getenv('XMLBEANS_HOME') . '/lib/xbean.jar:' . getenv('XMLBEANS_HOME') . '/lib/samp');
    }

    public function execute()
    {
        
        global $wsi_install_path;


        $validation_output;
        exec('java -classpath '. $wsi_install_path . '/net/embraceregistry/wsianalyzer/ -jar ' . $wsi_install_path . '/net/embraceregistry/wsianalyzer/runanalyzer.jar ' . $this->uri, $validation_output);

        
      
        if($validation_output[count($validation_output) - 1] == '1')
        {
            $this->state = $this->VALIDATION_COMPLETE_FAIL;
            
            $this->analyzer_error = implode("\n", $validation_output);
               
            
            
            return $this->state;
        }
        else
        {
            $this->state = $this->VALIDATION_COMPLETE_PASS;
            return $this->state;
        }

        

    }

    public function getMessages()
    {
       
        global $wsi_install_path;
        $error_output;
        exec('java -classpath '. $wsi_install_path . '/net/embraceregistry/wsianalyzer/ -jar ' . $wsi_install_path . '/net/embraceregistry/wsianalyzer/reportparser.jar', $error_output);
        
        $result = trim($error_output[1]);
        $returnData = array();
        $returnData["result"] = $result;
        $returnData["errors"] = "";
        $returnData["warnings"] = "";
        switch($result)
        {
            case 1:
                $this->state = $this->VALIDATION_COMPLETE_FAIL;

                $errorsec = false;
                $warnsec = false;
                for($i = 0; $i < count($error_output); $i++)
                {


                    if(strstr($error_output[$i], 'ERROR'))
                    {
                        $errorsec = true;
                        $warnsec = false;
                        $i++;
                    }
                    if(strstr($error_output[$i], 'WARNING'))
                    {
                        $warnsec = true;
                        $errorsec = false;
                        $i++;
                    }
                    if($warnsec)
                    {
                        $returnData["warnings"] .= trim($error_output[$i]);
                    }
                    if($errorsec)
                    {
                        $returnData["errors"] .= trim($error_output[$i]);
                    }


                }
                break;
            case 2:
                $this->state = $this->VALIDATION_INCOMPLETE;
                $returnData["errors"] .= $this->analyzer_error;
                break;
            default:
                break;
        }

        return $returnData;
        
    }

    public function getReportPath()
    {
        return '';
    }
}



?>
