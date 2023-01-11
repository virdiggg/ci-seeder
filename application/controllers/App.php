<?php defined('BASEPATH') OR exit('No direct script access allowed');

class App extends CI_Controller
{
	/**
	 * How to use:
	 * 
	 * Open terminal inside root folder, then type "php index.php app seed "tablename""
	 */
	public function __construct()
	{
		parent::__construct();
	}

	public function seed($table = '')
	{
		if (!is_cli()) {
			echo "CANNOT BE ACCESSED OUTSIDE COMMAND PROMP".PHP_EOL;
			echo "RUN: cd c:/xampp/htdocs/codeigniter && php index.php app seed \"tablename\"".PHP_EOL;
			return;
		}

		$this->load->library('Seeder');
		// // set custom connection, default is 'default'
		// $this->seeder->setConn('db_post_office');
		// // set custom path
		// $this->seeder->setPath(APPPATH);
		$res = $this->seeder->seed($table);

		echo $res->message.PHP_EOL;
		return;
	}

	public function controller($table = '', $folder = 'admin')
	{
		if (!is_cli()) {
			echo "CANNOT BE ACCESSED OUTSIDE COMMAND PROMP".PHP_EOL;
			echo "RUN: cd c:/xampp/htdocs/codeigniter && php index.php app controller \"filename\" \"folder\"".PHP_EOL;
			return;
		}

		// Get all arguments passed to this function
		$args = func_get_args();
		// Unset the first arguments (is the same as $table)
		unset($args[0]);
		// Unset the second arguments (is the same as $folder)
		unset($args[1]);
		// Rebase array
		$args = array_values($args);

        $this->load->library('Seeder');
		// // set custom path
		// $this->seeder->setPath(APPPATH);
        $res = $this->seeder->controller($table, $folder, $args);

		echo $res->message.PHP_EOL;
		return;
	}

	public function model($table = '')
	{
		if (!is_cli()) {
			echo "CANNOT BE ACCESSED OUTSIDE COMMAND PROMP".PHP_EOL;
			echo "RUN: cd c:/xampp/htdocs/codeigniter && php index.php app model \"filename\"".PHP_EOL;
			return;
		}

		// Get all arguments passed to this function
		$args = func_get_args();
		// Unset the first arguments (is the same as $table)
		unset($args[0]);
		// Rebase array
		$args = array_values($args);

        $this->load->library('Seeder');
		// // set custom path
		// $this->seeder->setPath(APPPATH);
        $res = $this->seeder->model($table, $args);

		echo $res->message.PHP_EOL;
		return;
	}
}