<?php

#Directory separator shotrcut
if(!defined('DS'))
{
    define('DS', DIRECTORY_SEPARATOR);
}

class jqGridLoader
{
	protected $root_path;
	protected $grid_path;

	protected $settings = array(
		'grid_path'	   => null,
		'encoding'     => 'utf-8',

		'db_driver'	   => 'Pdo',

		'pdo_dsn'      => null,
		'pdo_user'     => 'root',
		'pdo_pass'     => '',
		'pdo_options'  => null,

		'debug_output' => false,

		'input_grid'   => 'jqgrid',
		'input_oper'   => 'oper',
	);
	
	public function __construct()
	{
		#Root_path
		$this->root_path = dirname(__FILE__) . DS;
		$this->settings['grid_path'] = $this->root_path . 'grids' . DS;

		#Load base grid class
		require_once($this->root_path . 'jqGrid.php');

		#Register autoload
		spl_autoload_register(array($this, 'autoload'));
	}

	/**
	 * Access grid public methods via this function
	 * $jq_loader->render('jq_example');
	 */
	public function __call($func, $arg)
	{
		try
		{
			$grid = $this->load($arg[0]);
			unset($arg[0]);

			return call_user_func_array(array($grid, $func), $arg);
		}
		catch(jqGrid_Exception $e)
		{
			#Grid internal exception
			if(isset($grid))
			{
				return $grid->catchException($e);
			}
			#Loader exception
			else
			{
				return $e;
			}
		}
	}
	
	public function set($key, $val)
	{
		$this->settings[$key] = $val;
	}

	public function get($key)
	{
		return isset($this->settings[$key]) ? $this->settings[$key] : null;
	}

	public function load($name)
	{
		$file = $this->settings['grid_path'] . $name . '.php';
		
		if(!is_file($file))
		{
			throw new jqGrid_Exception_Render($name . ' not found!');
		}
		
		require_once $file;
		return new $name($this);
	}

	public function loadDB()
	{
		$class = 'jqGrid_DB_' . ucfirst($this->settings['db_driver']);
		
		return new $class($this);
	}

	/**
	 * Sample controller function
	 */
	public function autorun()
	{
		$name = isset($_REQUEST[$this->settings['input_grid']]) ? $_REQUEST[$this->settings['input_grid']] : '';

		if($name)
		{
			if(isset($_REQUEST[$this->settings['input_oper']]))
			{
				$this->oper($name, $_REQUEST[$this->settings['input_oper']]);
			}
			else
			{
				$this->output($name);
			}

			exit;
		}
	}

	/**
	 * jqGridPHP autoloader
	 * It will process only class names starting with 'jqGrid_'
	 */
	protected function autoload($class)
	{
		#Not a jqGrid class
		if(strpos($class, 'jqGrid_') !== 0)
		{
			return;
		}

		$parts = explode('_', $class);

		#Root class
		if(count($parts) == 2)
		{
			$path = $this->root_path . $parts[1] . DS . $parts[1] . '.php';
		}
		#Extend class
		else
		{
			$path = $this->root_path . implode(DS, array_slice($parts, 1)) . '.php';
		}

		#Do not interfere with other autoloads
		if(file_exists($path))
		{
			require $path;
		}
	}
}