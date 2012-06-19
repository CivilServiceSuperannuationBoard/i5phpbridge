<?php
class Model_Example_Database {

    protected $registry;

    public function __construct()
    {
        $this->registry = Zend_Registry::getInstance();
    }

	public function fetchData($index) {
		$result1 = 0;
        $file1 = new Model_Access_Db_Alpha_Library_File();
        $result1 = $file1->fetchField($index);

        $result2 = Model_Access_Db_Beta_Library2_File2::fetchField(index);

        return array("result1" => $result1,
                "result2" => $result2);
	}
}
?>
