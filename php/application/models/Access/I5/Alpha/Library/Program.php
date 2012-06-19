<?php
class Model_Access_I5_Alpha_Library_Program {
	
    public function callProgram($input) {
		$error = null;
        $output = 0;

        $call = new Model_Definition_I5_Alpha_Library_Program();
        
        $bundle = array("input" => $input);
        $call->setParameters($bundle);
        $execute = $call->exec($bundle);

        $results = $call->getResults();
        $error = $results['error'];
        $output = $results['output'];

		return $output;
	}
}
