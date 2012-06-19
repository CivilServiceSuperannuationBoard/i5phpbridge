<?php
class Model_Example_Program {

    protected $registry;

    public function __construct() {
        $this->registry = Zend_Registry::getInstance();
    }

	public function callProgram($input) {
		$result1 = 0;
        $program = Model_Access_I5_Alpha_Library_Program();
        $result1 = $program->callProgram($input);

		$result2 = Model_Access_I5_Beta_Library2_Program2::callProgram($input);

		return array("result1" => $result1,
                "result2" => $result2);
	}
}
?>
