<?php
class Model_Definition_I5_Beta_Library2_Program2 extends Model_Definition_I5_Template {	
    protected $_schema = 'LIBRARY2'; 
    protected $_program = 'PROGRAM2';	
	protected $_system = 'beta';

    protected $_description= array(
            array(
                self::PARM_NAME=>"INPUT",
                self::PARM_IO=>self::I5_IN,
                self::PARM_TYPE=>self::I5_TYPE_PACKED,
                self::PARM_LENGTH=>"10.0"
                ),
            array(
                self::PARM_NAME=>"OUTPUT",
                self::PARM_IO=>self::I5_OUT,
                self::PARM_TYPE=>self::I5_TYPE_PACKED,
                self::PARM_LENGTH=>"10.0"
                )
    		array(
    			self::PARM_NAME=>"ERROR",
    			self::PARM_IO=>self::I5_OUT,
    			self::PARM_TYPE=>self::I5_TYPE_CHAR,
    			self::PARM_LENGTH=>"60"
    			),
                );    

    protected $_input;
    protected $_output;
    protected $_error;

    public function __construct(){
    }

    public function setParameters($parms) {
        $this->_input = $parms['input'];
        $this->addInputParameter("INPUT", $parms['input']);
    }

    public function getResults() {
        return array(
            "output" => $this->_output,
            "error" => $this->_error);
    }

    public function exec($parms){

        try {
            $output = parent::exec();
        } catch(Model_Definition_I5_Exception $e){
            throw $e;
        }
		$this->_error = $output['ERROR'];

        return true;
    }
}
?>
