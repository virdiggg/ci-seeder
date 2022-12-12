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

		if (!$res->status) {
			echo $res->message.PHP_EOL;
			return;
		}

		echo $res->message.PHP_EOL;
		return;
	}
}