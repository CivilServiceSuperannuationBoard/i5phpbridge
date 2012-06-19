<?php
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

	protected $_config;

	function _initConfig() {
		date_default_timezone_set('America/Winnipeg');
		Zend_Locale::setDefault('en_CA');
		Zend_Registry::set('Zend_Locale', new Zend_Locale('en_CA'));
		$this->_config = new Zend_Config_Ini(APPLICATION_PATH 
				. '/configs/application.ini', 'production');
		$frontController = Zend_Controller_Front::getInstance();
		$registry = new Zend_Registry(array(), ArrayObject::ARRAY_AS_PROPS);
		$registry = Zend_Registry::getInstance();
		$autoloader = Zend_Loader_Autoloader::getInstance();
		$autoloader->registerNamespace('Zendext_');
		$cache = Zend_Cache::factory('Core', 'APC', array('automatic_serialization' => true), array());
		Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);

		$bridge = $this->getPluginResource('bridge');
		Zend_Registry::set('alpha', $bridge->getDbAdapter('alpha'));
		Zend_Registry::set('beta', $bridge->getDbAdapter('beta'));

		$registry->config        = $this->_config;
		$registry->cache         = $cache;

		unset($frontController, $registry, $cache, $bridge);
	}

	protected function _initAutoload() {
		return new Zend_Application_Module_Autoloader(array(
					'namespace' => '',
					'basePath'  => APPLICATION_PATH));
	}

	protected function _initDoctype() {
		$this->bootstrap('view');
		$view = $this->getResource('view');
		$view->doctype('XHTML1_STRICT');
	}

}
