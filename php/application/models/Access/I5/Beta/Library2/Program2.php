<?php
class Model_Access_I5_Beta_Library2_Program2 {
	
    public function callProgram2($input) {
		$error = null;
        $output = 0;

        $call = new Model_Definition_I5_Beta_Library2_Program2();
        
        $bundle = array("input" => $input);
        $call->setParameters($bundle);
        $execute = $call->exec($bundle);

        $results = $call->getResults();
        $error = $results['error'];
        $output = $results['output'];

		return $output;
	}
}
