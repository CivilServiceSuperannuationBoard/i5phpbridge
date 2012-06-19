<?php
require_once 'Zend/Db/Adapter/Exception.php';

class Zendext_Db_Adapter_Db2_Exception extends Zend_Db_Adapter_Exception {
    protected $code = '00000';
    protected $message = 'unknown exception';

    function __construct($msg = 'unknown exception', $state = '00000') {
        $this->message = $msg;
        $this->code = $state;
    }
}
?>
