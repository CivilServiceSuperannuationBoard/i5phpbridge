<?php
class Model_Access_Db_Alpha_Library_File {

	public function fetchField($index) {
		$result = 0;
		$file = new Model_Definition_Db_Alpha_Library_File();
		$select = $file->select()
			->from($file, array('PRIMARY'))
			->where('INDEX = ?', $index);
		foreach ($file->fetchAll($select) as $row) {
			$result = (int)$row['INDEX'];
		}
		return $result;
	}

}
?>
