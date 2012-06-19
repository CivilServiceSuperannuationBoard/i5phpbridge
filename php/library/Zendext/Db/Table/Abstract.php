<?php
class Zendext_Db_Table_Abstract extends Zend_Db_Table_Abstract {
    public function __construct() {
        $config = null;
        if(isset($this->_use_adapter)) {
            $config = Zend_Registry::get($this->_use_adapter);
        }
        parent::__construct($config);
    }

    public function select() {
        return new Zendext_Db_Table_Select($this);
    }    
}
?>
