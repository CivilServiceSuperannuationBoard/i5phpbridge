<?php
class EstimateController extends Zend_Controller_Action
{
    protected $registry;

    public function init()
    {
        $this->registry = Zend_Registry::getInstance();
        $this->_helper->layout()->disableLayout();
    }

    public function indexAction() {
    }

    public function databaseAction() {
        $index = $_GET['index'];
        $example = new Model_Example();
        $result = $example->fetchData($index);
    }

    public function programAction() {
        $input = $_GET['input'];
        $result = Model_Example::callProgram($input);
    }

}
