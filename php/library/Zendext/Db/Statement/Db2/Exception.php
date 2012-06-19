<?php
require_once 'Zend/Db/Statement/Exception.php';

class Zendext_Db_Statement_Db2_Exception extends Zend_Db_Statement_Exception {
    /**
     * @var string
     */
    protected $code = '00000';

    /**
     * @var string
     */
    protected $message = 'unknown exception';

    /**
     * @param string $msg
     * @param string $state
     */
    function __construct($msg = 'unknown exception', $state = '00000')
    {
        $this->message = $msg;
        $this->code = $state;
    }

}

