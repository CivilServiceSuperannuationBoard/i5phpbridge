<?php
class Model_Access_Db_Beta_Library2_File2 {

	public function fetchField($index) {
		$result = 0;
		$file = new Model_Definition_Db_Beta_Library2_File2();
		$select = $file->select()
			->from($file, array('PRIMARY2'))
			->where('INDEX2 = ?', $index);
		foreach ($file->fetchAll($select) as $row) {
			$result = (int)$row['INDEX2'];
		}
		return $result;
	}

}
?>
