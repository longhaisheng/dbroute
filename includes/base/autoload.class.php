<?php
class autoloader {
	public static $loader;

	private function __construct() {
		spl_autoload_register(array(
			$this,
			'base_class'
		));
		spl_autoload_register(array(
			$this,
			'inc_cls'
		));
	}

	public static function init() {
		if (self::$loader == NULL)
			self::$loader = new self();

		return self::$loader;
	}

	private function inc_cls($className) {
		$modulesDir = "";
		$classType = "";
		$classType = substr($className, 0, 4);
		if ($classType == 'cls_') {
			$modulesDir = ROOT_PATH . 'includes/';
		} elseif (substr($className, -5) == 'Model') {
			$modulesDir = ROOT_PATH . 'includes/model/';
		} elseif (substr($className, -10) == 'Controller') {
			$modulesDir = ROOT_PATH . 'includes/controller/';
		}
		$classFileName = "";
		$classFileName = $modulesDir . $className . ".php";
		if (file_exists($classFileName)) {
			include $classFileName;
			return;
		} else {
			$this -> err_fn($className);
		}
	}

	private function base_class($className) {
		$path = array();
		$pathDir = array();
		$path = explode('_', $className);
		$arrCount = count($path) - 1;
		$pathDir = implode("/", array_slice($path, 0, $arrCount));
		set_include_path(ROOT_PATH . "includes/" . $pathDir);
		spl_autoload_extensions('.class.php');
		spl_autoload($path[$arrCount]);
	}

	private function err_fn($className) {
		echo "class $className files includes err!!";
		exit();
	}

}
?>