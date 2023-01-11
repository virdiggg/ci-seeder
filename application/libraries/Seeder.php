<?php

defined('BASEPATH') or exit('No direct script access allowed');

defined('SEEDER_PATH') or define('SEEDER_PATH', APPPATH . 'migrations');

/**
 * Create seeder file for CodeIgniter 3 from already existing table.
 *
 * Copyright (c) 2022 CI3 Seeder
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * Created by Virdi Gunawan
 * Mail to: virdigunawann@gmail.com
 */

class Seeder
{
    /**
     * Instance CI.
     *
     * @param object
     */
    private $CI;

    /**
     * DB Connection.
     *
     * @param object
     */
    private $db;

    /**
     * Custom DB setting.
     *
     * @param string
     */
    public $conn;

    /**
     * Folder to save seeder file.
     *
     * @param string
     */
    public $path;

    /**
     * File Pointer.
     *
     * @param string
     */
    public $filePointer;

    public function __construct()
    {
        $this->CI = & get_instance();
    }

    /**
     * Create a simple seeder file.
     *
     * @param string $name Table name
     *
     * @return object
     */
    public function seed($name = '')
    {
        if (!$name) {
            return (object) [
                'status' => false,
                'message' => 'PARAMETER NOT FOUND',
            ];
        }

        // This is a different connection.
        // So don't be confused with the one we'are going to print in seeder file
        $this->db = $this->CI->load->database($this->getConn(), TRUE);

        if (!$this->db->table_exists($name)) {
            return (object) [
                'status' => false,
                'message' => 'TABLE "' . $name . '" NOT FOUND IN YOUR DATABASE',
            ];
        }

        $results = $this->db->select()->from(trim($name))
            ->get()->result_array();

        if (count($results) === 0) {
            return (object) [
                'status' => false,
                'message' => 'NO RECORDS IN TABLE "' . $name,
            ];
        }

        // Parse input as printable string.
        $print = $this->parseInputSeeder($name, $results);

        // Get the latest migration file order.
        $count = $this->latest($this->getPath());

        // Create seeder file.
        $this->createFile($this->getPath(), $count . '_seeder_' . $name . '.php');

        // Write to newly created seeder file.
        fwrite($this->filePointer, $print . PHP_EOL);

        return (object) [
            'status' => true,
            'message' => 'SEEDER CREATED'
        ];
    }

    /**
     * Create a simple controller file.
     *
     * @param string $fullName Table name
     * @param array  $param    Optional parameter
     *
     * @return object
     */
    public function controller($fullName = '', $param = [])
    {
        if (!$fullName) {
            return (object) [
                'status' => false,
                'message' => 'PARAMETER NOT FOUND',
            ];
        }

        // File path is before the last slash \. If exists, add another slash.
        $before = $this->beforeLast($fullName, '\\');
        if ($before) {
            $before = DIRECTORY_SEPARATOR . $before;
        }

        // Set path to controllers folder
        $this->setPath(APPPATH . 'controllers' . $before);

        // File name is after the last slash \.
        $name = $this->afterLast($fullName, '\\');

        // Ucfirst for file and class name
        $name = ucfirst(strtolower(trim($name)));

        $withResources = false;
        if (count($param) > 0) {
            if (in_array('--r', $param)) {
                $withResources = true;
            }
        }

        // Parse input as printable string.
        $print = $this->parseInputController($name, $withResources);

        // Create seeder file.
        $this->createFile($this->getPath(), $name . '.php');

        // Write to newly created seeder file.
        fwrite($this->filePointer, $print . PHP_EOL);

        return (object) [
            'status' => true,
            'message' => 'CONTROLLER CREATED'
        ];
    }

    /**
     * Create a simple model file.
     *
     * @param string $fullName Table name
     * @param array  $param    Optional parameter
     *
     * @return object
     */
    public function model($fullName = '', $param = [])
    {
        if (!$fullName) {
            return (object) [
                'status' => false,
                'message' => 'PARAMETER NOT FOUND',
            ];
        }

        // File path is before the last slash \. If exists, add another slash.
        $before = $this->beforeLast($fullName, '\\');
        if ($before) {
            $before = DIRECTORY_SEPARATOR . $before;
        }

        // Set path to models folder
        $this->setPath(APPPATH . 'models' . $before);

        // File name is after the last slash \.
        $name = $this->afterLast($fullName, '\\');

        // Ucfirst for file and class name
        $name = ucfirst(strtolower(trim($name)));

        $withResources = false;
        $withController = false;
        if (count($param) > 0) {
            if (in_array('--r', $param)) {
                $withResources = true;
            }
            if (in_array('--c', $param)) {
                $withController = true;
            }
        }

        // Parse input as printable string.
        $print = $this->parseInputModel($name, $withResources);

        // Create seeder file.
        $this->createFile($this->getPath(), 'M_' . $name . '.php');

        // Write to newly created seeder file.
        fwrite($this->filePointer, $print . PHP_EOL);

        if ($withController) {
            $args = [];
            if ($withResources) {
                $args = ['--r'];
            }
            $this->controller($fullName, $args);
        }

        return (object) [
            'status' => true,
            'message' => 'MODEL CREATED'
        ];
    }

    /**
     * Parse input as printable string for seeder file.
     * 
     * @param string $name
     * @param array  $results
     * 
     * @return string
     */
    private function parseInputSeeder($name, $results)
    {
        // Array keys for column name.
        $keys = array_keys($results[0]);

        // Reverse array to Descending.
        // We don't know which incremental value this table has and which one should we use, so we do it manually.
        asort($results);

        // Rebase the array.
        $results = array_values($results);

        $print = "<?php defined('BASEPATH') OR exit('No direct script access allowed');" . PHP_EOL . PHP_EOL;
        $print .= "Class Migration_Seeder_" . $name . " extends CI_Migration {" . PHP_EOL;
        $print .= '    /**' . PHP_EOL;
        $print .= '     * Private function db connection.' . PHP_EOL;
        $print .= '     * ' . PHP_EOL;
        $print .= '     * @param object' . PHP_EOL;
        $print .= '     */' . PHP_EOL;
        $print .= '    private $' . $this->getConn() . ';' . PHP_EOL . PHP_EOL;
        $print .= '    /**' . PHP_EOL;
        $print .= '     * Table name.' . PHP_EOL;
        $print .= '     * ' . PHP_EOL;
        $print .= '     * @param string' . PHP_EOL;
        $print .= '     */' . PHP_EOL;
        $print .= '    private $name;' . PHP_EOL . PHP_EOL;
        $print .= "    public function __construct() {" . PHP_EOL;
        $print .= "        parent::__construct();" . PHP_EOL;
        $print .= '        $this->' . $this->getConn() . ' = $this->load->database(\'' . $this->getConn() . '\', TRUE);' . PHP_EOL;
        $print .= '        $this->name = \'' . $name . '\';' . PHP_EOL;
        $print .= "    }" . PHP_EOL . PHP_EOL; // end public function __construct()
        $print .= '    /**' . PHP_EOL;
        $print .= '     * Migration.' . PHP_EOL;
        $print .= '     * ' . PHP_EOL;
        $print .= '     * @return void' . PHP_EOL;
        $print .= '     */' . PHP_EOL;
        $print .= "    public function up() {" . PHP_EOL;
        $print .= '        $param = [];' . PHP_EOL . PHP_EOL;
        foreach ($results as $key => $res) {
            $print .= '        $param[] = [' . PHP_EOL;
            foreach ($keys as $k) {
                $r = is_null($res[$k]) ? 'null' : '\'' . $res[$k] . '\'';
                $print .= '            \'' . $k . '\' => ' . $r . ',' . PHP_EOL;
            }
            $print .= "        ];" . PHP_EOL; // end $param[]
        }
        $print .= PHP_EOL . '        $this->' . $this->getConn() . '->insert_batch($this->name, $param);' . PHP_EOL;
        $print .= "    }" . PHP_EOL . PHP_EOL; // end public function up()
        $print .= '    /**' . PHP_EOL;
        $print .= '     * Rollback migration.' . PHP_EOL;
        $print .= '     * ' . PHP_EOL;
        $print .= '     * @return void' . PHP_EOL;
        $print .= '     */' . PHP_EOL;
        $print .= "    public function down() {" . PHP_EOL;
        $print .= '        $this->' . $this->getConn() . '->truncate($this->name);' . PHP_EOL;
        $print .= "    }" . PHP_EOL; // end public function down()
        $print .= "}"; // end class

        return $print;
    }

    /**
     * Parse input as printable string for controller file.
     * 
     * @param string $name
     * @param bool   $param
     * 
     * @return string
     */
    private function parseInputController($name, $param = false)
    {
        $print = "<?php defined('BASEPATH') OR exit('No direct script access allowed');" . PHP_EOL . PHP_EOL;
        $print .= "Class " . $name . " extends CI_Controller {" . PHP_EOL;
        $print .= '    /**' . PHP_EOL;
        $print .= '     * Page title.' . PHP_EOL;
        $print .= '     * ' . PHP_EOL;
        $print .= '     * @param string' . PHP_EOL;
        $print .= '     */' . PHP_EOL;
        $print .= '    private $title;' . PHP_EOL . PHP_EOL;
        $print .= "    public function __construct() {" . PHP_EOL;
        $print .= "        parent::__construct();" . PHP_EOL;
        $print .= '        $this->title = \'' . $name . '\';' . PHP_EOL;
        $print .= "    }" . PHP_EOL . PHP_EOL; // end public function __construct()
        $print .= '    /**' . PHP_EOL;
        $print .= '     * Index page.' . PHP_EOL;
        $print .= '     * ' . PHP_EOL;
        $print .= '     * @return view' . PHP_EOL;
        $print .= '     */' . PHP_EOL;
        $print .= "    public function index() {" . PHP_EOL;
        $print .= '        $data = [' . PHP_EOL;
        $print .= '            \'title\' => $this->title,' . PHP_EOL;
        $print .= '        ];' . PHP_EOL . PHP_EOL; // end $data
        $print .= '        $this->load->view(\'layout/wrapper\', $data);' . PHP_EOL;
        $print .= "    }" . PHP_EOL . PHP_EOL; // end public function index()
        if ($param) {
            $print .= '    /**' . PHP_EOL;
            $print .= '     * Page for create a new data.' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @return view' . PHP_EOL;
            $print .= '     */' . PHP_EOL;
            $print .= "    public function create() {" . PHP_EOL;
            $print .= '        //' . PHP_EOL;
            $print .= "    }" . PHP_EOL . PHP_EOL; // end public function create()
            $print .= '    /**' . PHP_EOL;
            $print .= '     * Function to insert data to database.' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @return response' . PHP_EOL;
            $print .= '     */' . PHP_EOL;
            $print .= "    public function store() {" . PHP_EOL;
            $print .= '        //' . PHP_EOL;
            $print .= "    }" . PHP_EOL . PHP_EOL; // end public function store()
            $print .= '    /**' . PHP_EOL;
            $print .= '     * Page for edit a data with $id parameter.' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @param string $id' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @return view' . PHP_EOL;
            $print .= '     */' . PHP_EOL;
            $print .= '    public function edit($id) {' . PHP_EOL;
            $print .= '        //' . PHP_EOL;
            $print .= "    }" . PHP_EOL . PHP_EOL; // end public function edit()
            $print .= '    /**' . PHP_EOL;
            $print .= '     * Function to update data in database with $id parameter.' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @param string $id' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @return response' . PHP_EOL;
            $print .= '     */' . PHP_EOL;
            $print .= '    public function update($id) {' . PHP_EOL;
            $print .= '        //' . PHP_EOL;
            $print .= "    }" . PHP_EOL . PHP_EOL; // end public function update()
            $print .= '    /**' . PHP_EOL;
            $print .= '     * Function to delete a data from databse with $id parameter.' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @param string $id' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @return response' . PHP_EOL;
            $print .= '     */' . PHP_EOL;
            $print .= '    public function destroy($id) {' . PHP_EOL;
            $print .= '        //' . PHP_EOL;
            $print .= "    }" . PHP_EOL . PHP_EOL; // end public function destroy()
            $print .= '    /**' . PHP_EOL;
            $print .= '     * Function for datatables.' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @param string $id' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @return response' . PHP_EOL;
            $print .= '     */' . PHP_EOL;
            $print .= '    public function datatables() {' . PHP_EOL;
            $print .= '        $draw = $this->input->post(\'draw\');' . PHP_EOL;
            $print .= '        $row = $this->input->post(\'length\');' . PHP_EOL;
            $print .= '        $no = $this->input->post(\'start\');' . PHP_EOL;
            $print .= '        $search = strtolower($this->input->post(\'start\'));' . PHP_EOL . PHP_EOL;
            $print .= '        // Your datatables query here.' . PHP_EOL;
            $print .= '        // $totalRecords = 0;' . PHP_EOL;
            $print .= '        // $totalRecordsWithFilter = 0;' . PHP_EOL;
            $print .= '        // $datatables = [];' . PHP_EOL . PHP_EOL;
            $print .= '        $return = [' . PHP_EOL;
            $print .= '            \'status\' => true,' . PHP_EOL;
            $print .= '            \'message\' => \'Data ditemukan\',' . PHP_EOL;
            $print .= '            // \'draw\' => intval($draw),' . PHP_EOL;
            $print .= '            // \'iTotalRecords\' => $totalRecords,' . PHP_EOL;
            $print .= '            // \'iTotalDisplayRecords\' => $totalRecordsWithFilter,' . PHP_EOL;
            $print .= '            // \'aaData\' => $datatables,' . PHP_EOL;
            $print .= '        ];' . PHP_EOL;
            $print .= '        echo json_encode($return);' . PHP_EOL;
            $print .= '        return;' . PHP_EOL;
            $print .= "    }" . PHP_EOL . PHP_EOL; // end public function datatables()
        }
        $print .= "}"; // end class

        return $print;
    }

    /**
     * Parse input as printable string for model file.
     * 
     * @param string $name
     * @param bool   $param
     * 
     * @return string
     */
    private function parseInputModel($name, $param = false)
    {
        $print = "<?php defined('BASEPATH') OR exit('No direct script access allowed');" . PHP_EOL . PHP_EOL;
        $print .= "Class M_" . $name . " extends CI_model {" . PHP_EOL;
        $print .= '    /**' . PHP_EOL;
        $print .= '     * Default table name.' . PHP_EOL;
        $print .= '     * ' . PHP_EOL;
        $print .= '     * @param string' . PHP_EOL;
        $print .= '     */' . PHP_EOL;
        $print .= '    private $table;' . PHP_EOL . PHP_EOL;
        $print .= "    public function __construct() {" . PHP_EOL;
        $print .= "        parent::__construct();" . PHP_EOL;
        $print .= '        $this->load->database();' . PHP_EOL;
        $print .= '        $this->table = \'' . strtolower($name) . '\';' . PHP_EOL;
        $print .= "    }" . PHP_EOL . PHP_EOL; // end public function __construct()
        $print .= '    /**' . PHP_EOL;
        $print .= '     * Get all data from database.' . PHP_EOL;
        $print .= '     * ' . PHP_EOL;
        $print .= '     * @return array' . PHP_EOL;
        $print .= '     */' . PHP_EOL;
        $print .= "    public function get() {" . PHP_EOL;
        $print .= '        return $this->db->select()->from($this->table)->get()->result();' . PHP_EOL;
        $print .= "    }" . PHP_EOL . PHP_EOL; // end public function get()
        $print .= '    /**' . PHP_EOL;
        $print .= '     * Find data based on $id.' . PHP_EOL;
        $print .= '     * ' . PHP_EOL;
        $print .= '     * @param string $id' . PHP_EOL;
        $print .= '     * ' . PHP_EOL;
        $print .= '     * @return object|null' . PHP_EOL;
        $print .= '     */' . PHP_EOL;
        $print .= '    public function find($id) {' . PHP_EOL;
        $print .= '        return $this->db->get_where($this->table, [\'id\' => $id])->row();' . PHP_EOL;
        $print .= "    }" . PHP_EOL . PHP_EOL; // end public function find()
        if ($param) {
            $print .= '    /**' . PHP_EOL;
            $print .= '     * Insert data to database based on $id.' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @param array $param' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @return object' . PHP_EOL;
            $print .= '     */' . PHP_EOL;
            $print .= '    public function create($param) {' . PHP_EOL;
            $print .= '        $this->db->insert($this->table, $param);' . PHP_EOL;
            $print .= '        return $this->find($this->db->insert_id());' . PHP_EOL;
            $print .= "    }" . PHP_EOL . PHP_EOL; // end public function create()
            $print .= '    /**' . PHP_EOL;
            $print .= '     * Update data to database based on $id.' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @param string|int $id' . PHP_EOL;
            $print .= '     * @param array      $param' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @return object' . PHP_EOL;
            $print .= '     */' . PHP_EOL;
            $print .= '    public function update($id, $param) {' . PHP_EOL;
            $print .= '        $this->db->where(\'id\', $id);' . PHP_EOL;
            $print .= '        $this->db->update($this->table, $param);' . PHP_EOL;
            $print .= '        $result = (bool) $this->db->affected_rows();' . PHP_EOL;
            $print .= '        if (!$result) {' . PHP_EOL;
            $print .= '            return $result;' . PHP_EOL;
            $print .= '        }' . PHP_EOL;
            $print .= '        return $this->find($id);' . PHP_EOL;
            $print .= "    }" . PHP_EOL . PHP_EOL; // end public function update()
            $print .= '    /**' . PHP_EOL;
            $print .= '     * Delete data from database based on $id.' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @param string|int $id' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @return bool' . PHP_EOL;
            $print .= '     */' . PHP_EOL;
            $print .= '    public function destroy($id) {' . PHP_EOL;
            $print .= '        $this->db->where(\'id\', $id)->delete($this->table);' . PHP_EOL;
            $print .= '        return (bool) $this->db->affected_rows();' . PHP_EOL;
            $print .= "    }" . PHP_EOL . PHP_EOL; // end public function destroy()
            $print .= '    /**' . PHP_EOL;
            $print .= '     * Total all records.' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @return int' . PHP_EOL;
            $print .= '     */' . PHP_EOL;
            $print .= '    public function totalRecords() {' . PHP_EOL;
            $print .= '        return $this->db->select(\'id\')->from($this->table)->count_all_results();' . PHP_EOL;
            $print .= "    }" . PHP_EOL . PHP_EOL; // end public function totalRecords()
            $print .= '    /**' . PHP_EOL;
            $print .= '     * Total all records with filter.' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @param string|null $search' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @return int' . PHP_EOL;
            $print .= '     */' . PHP_EOL;
            $print .= '    public function totalRecordsWithFilter($search = null) {' . PHP_EOL;
            $print .= '        $this->db->select(\'id\');' . PHP_EOL;
            $print .= '        $this->db->from($this->table);' . PHP_EOL;
            $print .= '        if ($search) {' . PHP_EOL;
            $print .= '            // Your LIKE query.' . PHP_EOL;
            $print .= '            // $this->db->group_start();' . PHP_EOL;
            $print .= '            // $this->db->like(\'LOWER(name)\', strtolower($search));' . PHP_EOL;
            $print .= '            // $this->db->group_end();' . PHP_EOL;
            $print .= '        }' . PHP_EOL;
            $print .= '        return $this->db->count_all_results();' . PHP_EOL;
            $print .= "    }" . PHP_EOL . PHP_EOL; // end public function totalRecordsWithFilter()
            $print .= '    /**' . PHP_EOL;
            $print .= '     * Datatables.' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @param string|int  $length' . PHP_EOL;
            $print .= '     * @param string|int  $start' . PHP_EOL;
            $print .= '     * @param string|null $search' . PHP_EOL;
            $print .= '     * ' . PHP_EOL;
            $print .= '     * @return array' . PHP_EOL;
            $print .= '     */' . PHP_EOL;
            $print .= '    public function datatables($length = 10, $start = 0, $search = null) {' . PHP_EOL;
            $print .= '        $this->db->select();' . PHP_EOL;
            $print .= '        $this->db->from($this->table);' . PHP_EOL;
            $print .= '        if ($search) {' . PHP_EOL;
            $print .= '            // Your LIKE query.' . PHP_EOL;
            $print .= '            // $this->db->group_start();' . PHP_EOL;
            $print .= '            // $this->db->like(\'LOWER(name)\', strtolower($search));' . PHP_EOL;
            $print .= '            // $this->db->group_end();' . PHP_EOL;
            $print .= '        }' . PHP_EOL;
            $print .= '        $this->db->limit($length, $start);' . PHP_EOL;
            $print .= '        return $this->db->get()->result();' . PHP_EOL;
            $print .= "    }" . PHP_EOL . PHP_EOL; // end public function datatables()
        }
        $print .= "}"; // end class

        return $print;
    }

    /**
     * Create seeder file. Drop if already exists, then create a new one.
     *
     * @param string $path
     * @param string $name
     *
     * @return void
     */
    private function createFile($path, $name)
    {
        $this->folderPermission($path);

        $fullPath = $path . $name;

        $old = umask(0);

        $file = $fullPath;
        // If file exists, drop it.
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        $file = fopen($fullPath, 'a') or exit("Can't open $fullPath!");
        umask($old);

        $this->filePointer = $file;
    }

    /**
     * Create folder with 0777 (rwxrwxrwx) permission if doesn't exist.
     * If exists, change its permission to 0777 (rwxrwxrwx).
     *
     * @param string $path
     * @param string $mode
     *
     * @return void
     */
    private function folderPermission($path, $mode = 0777)
    {
        if (!is_dir($path)) {
            // If folder doesn't exist, create a new one with permission (rwxrwxrwx).
            $old = umask(0);
            mkdir($path, $mode, TRUE);
            umask($old);
        } else {
            // If exists, change its permission to 0777 (rwxrwxrwx).
            $old = umask(0);
            @chmod($path, $mode);
            umask($old);
        }
    }

    /**
     * Return the remainder of a string after the last occurrence of a given value.
     * Stolen from laravel helper.
     *
     * @param  string  $subject
     * @param  string  $search
     * @return string
     */
    private function afterLast($subject, $search)
    {
        if ($search === '') {
            return $subject;
        }

        $position = strrpos($subject, (string) $search);

        if ($position === false) {
            return $subject;
        }

        return substr($subject, $position + strlen($search));
    }

    /**
     * Parse the given arguments to determine if they are name string or arguments.
     *
     * @param array $args
     * 
     * @return array
     */
    public function parseParam($args)
    {
        if (!$args) {
            return (object) [
                'name' => '',
                'args' => [],
            ];
        }

		$name = $param = [];
		foreach ($args as $key => $arg) {
			if ($this->startsWith($arg, '--')) {
				$param[] = $arg;
			} else {
				$name[] = $arg;
			}
		}

        return (object) [
            'name' => join(DIRECTORY_SEPARATOR, $name), // Implode/Join array name with DIRECTORY_SEPARATOR.
            'args' => array_values(array_unique($param)), // Distinct, then rebase the arguments array.
        ];
    }

    /**
     * Determine if a given string starts with a given substring. Case sensitive.
     * Stolen from laravel helper.
     *
     * @param  string  $haystack
     * @param  string|string[]  $needles
     * @return bool
     */
    private function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ((string) $needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the portion of a string before the first occurrence of a given value.
     * Stolen from laravel helper.
     *
     * @param  string  $subject
     * @param  string  $search
     * @return string
     */
    private function before($subject, $search)
    {
        if ($search === '') {
            return $subject;
        }

        $result = strstr($subject, (string) $search, true);

        return $result === false ? $subject : $result;
    }

    /**
     * Get the portion of a string before the last occurrence of a given value.
     * Stolen from laravel helper.
     *
     * @param  string  $subject
     * @param  string  $search
     * @return string
     */
    private function beforeLast($subject, $search)
    {
        if ($search === '') {
            return $subject;
        }

        $pos = mb_strrpos($subject, $search);

        if ($pos === false) {
            return $subject;
        }

        return substr($subject, 0, $pos);
    }

    /**
     * Get latest migration order.
     * Default is sequential, if there is no migration file exist.
     *
     * @param string $path
     *
     * @return string
     */
    private function latest($path)
    {
        // Get all migration files.
        $seeders = $path . '*.php';
        $globs = array_filter(glob($seeders), 'is_file');
        if (count($globs) > 0) {
            // Reverse the array.
            rsort($globs);

            // Get the latest array order.
            $latestMigration = (int) $this->before($this->afterLast($globs[0], '\\'), '_');
            $count = $latestMigration + 1;
        } else {
            // Default is sequential order, not timestamp.
            $count = '001';
        }

        return $count;
    }

    /**
     * Set path to seeder folder.
     *
     * @param string $path
     *
     * @return void
     */
    public function setPath($path = SEEDER_PATH)
    {
        // Path shouldn't have trailing slash or backslash.
        // We'are going to add DIRECTORY_SEPARATOR after the path ourself.
        $path = rtrim(rtrim($path, '/'), '\\');
        $this->path = $path . DIRECTORY_SEPARATOR;
    }

    /**
     * Set which connection used for this seeder.
     *
     * @param string $conn
     *
     * @return void
     */
    public function setConn($conn = 'default')
    {
        $this->conn = $conn;
    }

    /**
     * Get path to seeder folder. Default to constant SEEDER_PATH.
     *
     * @return string
     */
    private function getPath()
    {
        return $this->path ?? SEEDER_PATH . DIRECTORY_SEPARATOR;
    }

    /**
     * Get which connection used for seeder. Default is 'default'.
     *
     * @return string
     */
    private function getConn()
    {
        return $this->conn ?? 'default';
    }
}