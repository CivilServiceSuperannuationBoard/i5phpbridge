<?php
class Model_Example {

	public function fetchData($index) {
        $example = new Model_Example_Database();
        return $example->fetchData($index);
	}

	public function callProgram($input) {
        $example = new Model_Example_Program();
        return $example->callProgram($input);
	}

}
