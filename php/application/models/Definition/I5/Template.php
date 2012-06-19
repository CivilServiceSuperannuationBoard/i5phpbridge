<?php
class Model_Definition_I5_Template {
    const EXT_PROGRAM = ".PGM";
    const I5_IN = 1;
    const I5_OUT = 2;
    const I5_INOUT = 3;
    const I5_TYPE_CHAR = 1;
    const I5_TYPE_PACKED = 2;
    const PARM_NAME = "Name";
    const PARM_IO = "IO";
    const PARM_TYPE = "Type";
    const PARM_LENGTH = "Length";
    const PARM_VALUE = "Value";
    const OUTPUT_INDEX = "Index";
    const OUTPUT_VALUE = "Value";  

    protected $_schema;
    protected $_program;
    protected $_description;
	protected $_system;
    protected $_inputList = array();
	protected $_overrides = null;

    public function __construct(){

    }

    protected function prepareProgram($description = array(), $inputList = array()){
        $parameterMetaData = array();
        $parmList = array();
        $outputIndex = array();
        $index = 0;
        foreach ($description as $parameter){

            $tempParm = null;
            $tempStorageHolder = null;    		
            $tempParmValue = null;
            $assignedParmValue = null;
            $intHalf = 0;
            $decimalHalf = 0;

            foreach ($inputList as $input){
                if ($input[self::PARM_NAME] == $parameter[self::PARM_NAME]){
                    $assignedParmValue = $input[self::PARM_VALUE];
                    break;
                }
            }    		
            //create a new java parameter.  Assign the value if neccessary if not default to blank or 0    		
            switch ($parameter[self::PARM_TYPE]) {    			
                case self::I5_TYPE_PACKED:
                    $parmlength = $parameter[self::PARM_LENGTH];
                    $decimalPosition = strpos($parmlength, '.');
                    $intHalf = substr($parmlength, 0, $decimalPosition);
                    $decimalHalf = substr($parmlength, $decimalPosition + 1);

                    $rounded = round($assignedParmValue, $decimalHalf);

                    $tempStorageHolder = new Java("com.ibm.as400.access.AS400PackedDecimal", 
                            $intHalf, 
                            $decimalHalf);
                    if (is_null($assignedParmValue)){
                        $tempParmValue = new Java("java.math.BigDecimal", 0.0); 
                    }
                    else{
                        $tempParmValue = new Java("java.math.BigDecimal", strval($rounded));
                    }	

                    $tempParm = new Java("com.ibm.as400.access.ProgramParameter",
                            $tempStorageHolder->toBytes($tempParmValue),
                            intval($parameter[self::PARM_LENGTH]));
                    break;

                case self::I5_TYPE_CHAR:
                    $tempStorageHolder = new Java("com.ibm.as400.access.AS400Text", 
                            intval($parameter[self::PARM_LENGTH]));
                    //if the value exist then we use it otherwise use the default    											  
                    if (is_null($assignedParmValue)){
                        $tempParmValue = new Java("java.lang.String", '');						    											  
                    }
                    else{
                        $tempParmValue = new Java("java.lang.String", strval($assignedParmValue));						
                    }
                    $tempParm = new Java("com.ibm.as400.access.ProgramParameter",
                            $tempStorageHolder->toBytes($tempParmValue),
                            intval($parameter[self::PARM_LENGTH]));
                    break;
                default:
                    throw new Model_Definition_I5_Exception("Invalid program type: " . $parameter[self::PARM_TYPE]);
                    break;
            }

            //retrieve the position of the output
            if ($parameter[self::PARM_IO] == self::I5_OUT || $parameter[self::PARM_IO] == self::I5_INOUT){
                $outputMetaData = array();
                $outputMetaData[self::PARM_NAME] = $parameter[self::PARM_NAME];
                $outputMetaData[self::PARM_TYPE] = $parameter[self::PARM_TYPE];
                $outputMetaData[self::PARM_LENGTH] = $parameter[self::PARM_LENGTH];
                $outputMetaData[self::OUTPUT_INDEX] = $index;
                $outputIndex[] = $outputMetaData;
            }
            //add the jdbc version of parameter to the array list
            $parmList[] = $tempParm;
            $index++;
        }

        $parameterMetaData["parmList"] = $parmList;
        $parameterMetaData["output"] = $outputIndex;    	
        return $parameterMetaData;
    }

    protected function fetch($parameterList, $outputList){
        $result = array();

        foreach ($outputList as $output){					
            $bytes = $parameterList[$output[self::OUTPUT_INDEX]]->getOutputData();

            switch ($output[self::PARM_TYPE]) {
                case self::I5_TYPE_PACKED:					
                    $parmlength = $output[self::PARM_LENGTH];
                    $decimalPosition = strpos($parmlength, '.');
                    $intHalf = substr($parmlength, 0, $decimalPosition);
                    $decimalHalf = substr($parmlength, $decimalPosition + 1);
                    $tempStorageHolder = new Java("com.ibm.as400.access.AS400PackedDecimal", 
                            $intHalf, 
                            $decimalHalf);	
                    if ($decimalHalf == 0) {
                        $result[$output[self::PARM_NAME]] = java_values($tempStorageHolder->toDouble($bytes)->intValue());    	                } else {                                
                            $result[$output[self::PARM_NAME]] = java_values($tempStorageHolder->toDouble($bytes));
                        }  
                    break;
                case self::I5_TYPE_CHAR:
                    $tempStorageHolder = new Java("com.ibm.as400.access.AS400Text", 
                            intval($output[self::PARM_LENGTH]));	
                    $result[$output[self::PARM_NAME]] = java_values($tempStorageHolder->toObject($bytes)); 
                    break;
                default:
                    throw new Model_Definition_I5_Exception("Invalid program type: " . $parameter[self::PARM_TYPE]);
                    break;
            }

        }
        return $result;

    }

    public function addInputParameter($inputName, $inputValue){
        $this->_inputList[] = array(self::PARM_NAME=>$inputName, self::PARM_VALUE=>$inputValue);
    }

	public function addOverrides($overrides) {
		$this->_overrides = $overrides;
	}

    public function exec(){
        $i5conn = null;
		$i5Pool = null;
        $pgm = null;		
        $output = null;

        try{
	        $ctx = java_context();
	        $context = $ctx->getAttribute( "php.java.servlet.ServletContext", 100);
			switch ($this->_system) {
				case 'alpha':
					$i5Pool = $context->getAttribute("alphapool");
					break;
				case 'beta':
					$i5Pool = $context->getAttribute("betapool");
					break;
				default:
					$i5Pool = $context->getAttribute("alphapool");
			}
			
            $i5conn = $i5Pool->getConnection();    		

            if (!$i5conn) {	            
                throw new Model_Definition_I5_Exception("Unable to connect to the database.");			
            }

			if ($this->_overrides) {
				foreach ($this->_overrides as $override) {
		            $overrideCommand = new Java("com.ibm.as400.access.CommandCall", $i5conn, $override);
		            $success = java_values($overrideCommand->run());
				}
			}

            //prepare the program by creating jdbc parameters for input and output
            $parmMetaData = $this->prepareProgram($this->_description, $this->_inputList);			

            //Fire off the program - should return a boolean true on success
            $pgm = new Java("com.ibm.as400.access.ProgramCall", 
                    $i5conn, 
                    $this->getProperProgramName(), 
                    $parmMetaData["parmList"]);

            $success = java_values($pgm->run());

            if ($success){
                $output = $this->fetch($parmMetaData["parmList"], $parmMetaData["output"]);	
            }
            // Return the program connection to the pool

			if ($this->_overrides) {
				$i5conn->disconnectAllServices();
			}

            $i5Pool->returnConnection($i5conn);				
        }
        catch (Model_Definition_I5_Exception $e){
            //make sure the handle is close properly
            $pgm = null;

            //make sure the connection is close properly
            if (null != $i5conn)
				if ($this->_overrides) {
					$i5conn->disconnectAllServices();
				}
                $i5Pool->returnConnection($i5conn);	

            //rethrow error to caller
            throw $e;  			
        }
        return $output;
	}

    public function batch(){
        $i5conn = null;
		$i5Pool = null;
        $pgm = null;		
        $output = null;
		$success = false;

        try{
	        $ctx = java_context();
	        $context = $ctx->getAttribute( "php.java.servlet.ServletContext", 100);
			switch ($this->_system) {
				case 'alpha':
					$i5Pool = $context->getAttribute("alphapool");
					break;
				case 'beta':
					$i5Pool = $context->getAttribute("betapool");
					break;
				default:
					$i5Pool = $context->getAttribute("alphapool");
			}
			
            $i5conn = $i5Pool->getConnection();    		

            if (!$i5conn) {	            
                throw new Model_Definition_I5_Exception("Unable to connect to the database.");			
            }

			if ($this->_overrides) {
				foreach ($this->_overrides as $override) {
		            $overrideCommand = new Java("com.ibm.as400.access.CommandCall", $i5conn, $override);
		            $success = java_values($overrideCommand->run());
				}
			}

            //prepare the program by creating jdbc parameters for input and output
            $parmMetaData = $this->prepareProgram($this->_description, $this->_inputList);			

            //Fire off the program - should return a boolean true on success
            $pgm = new Java("com.ibm.as400.access.ProgramCall", 
                    $i5conn, 
                    $this->getProperProgramName(), 
                    $parmMetaData["parmList"]);

            $pgm->run();

            // Return the program connection to the pool

			if ($this->_overrides) {
				$i5conn->disconnectAllServices();
			}

            $i5Pool->returnConnection($i5conn);				
			$success = true;
        }
        catch (Model_Definition_I5_Exception $e){
            //make sure the handle is close properly
            $pgm = null;

            //make sure the connection is close properly
            if (null != $i5conn)
				if ($this->_overrides) {
					$i5conn->disconnectAllServices();
				}
                $i5Pool->returnConnection($i5conn);	

			$success = false;
            //rethrow error to caller
            throw $e;  			
        }
        return $success;
    }

    private function getProperProgramName(){
        return "/QSYS.LIB/" . $this->_schema . ".LIB/" . $this->_program . self::EXT_PROGRAM;
    }
}

?>
