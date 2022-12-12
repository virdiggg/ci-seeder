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
        // This is a different connection.
        // So don't be confused with the one we'are going to print in seeder file
        $this->db = $this->CI->load->database($this->getConn(), TRUE);
    }

    /**
     * Create a simple seeder file.
     *
     * @param string $name Table name
     *
     * @return object
     */
    public function seed($name = null)
    {
        if (!$name) {
            return (object) [
                'status' => false,
                'message' => 'PARAMETER NOT FOUND.',
            ];
        }

        if (!$this->db->table_exists($name)) {
            return (object) [
                'status' => false,
                'message' => 'TABLE "' . $name . '" NOT FOUND IN YOUR DATABASE.',
            ];
        }

        $results = $this->db->select()->from(trim($name))
            ->get()->result_array();

        if (count($results) === 0) {
            return (object) [
                'status' => false,
                'message' => 'NO RECORDS IN TABLE "' . $name . '".',
            ];
        }

        // Array keys for column name.
        $keys = array_keys($results[0]);

        $print = "<?php defined('BASEPATH') OR exit('No direct script access allowed');" . PHP_EOL . PHP_EOL;
        $print .= "Class Migration_Seeder_" . $name . " extends CI_Migration {" . PHP_EOL;
        $print .= '    /**' . PHP_EOL;
        $print .= '     * Private function db connection.' . PHP_EOL;
        $print .= '     * ' . PHP_EOL;
        $print .= '     * @param object' . PHP_EOL;
        $print .= '     */' . PHP_EOL;
        $print .= '    private $' . $this->getConn() . ';' . PHP_EOL . PHP_EOL;
        $print .= "    public function __construct() {" . PHP_EOL;
        $print .= "        parent::__construct();" . PHP_EOL;
        $print .= '        $this->' . $this->getConn() . ' = $this->load->database(\'' . $this->getConn() . '\', TRUE);' . PHP_EOL;
        $print .= "    }" . PHP_EOL . PHP_EOL; // end public function __construct()
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
        $print .= PHP_EOL . '        $this->' . $this->getConn() . '->insert_batch(\'' . $name . '\', $param);' . PHP_EOL;
        $print .= "    }" . PHP_EOL . PHP_EOL; // end public function up()
        $print .= "    public function down() {" . PHP_EOL;
        $print .= '        $this->db->truncate(\'' . $name . '\');' . PHP_EOL;
        $print .= "    }" . PHP_EOL; // end public function down()
        $print .= "}"; // end class

        // Get the latest migration file order.
        $count = $this->latest($this->getPath());

        // Create seeder file.
        $this->createFile($this->getPath(), $count . '_seeder_' . $name . '.php');

        // Write to newly created seeder file.
        fwrite($this->filePointer, $print . PHP_EOL);

        return (object) [
            'status' => true,
            'message' => 'SEEDER SUCCESS.'
        ];
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