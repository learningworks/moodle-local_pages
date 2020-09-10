<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * local pages
 *
 * @package     local_pages
 * @author      Kevin Dibble
 * @copyright   2017 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_pages;

defined('MOODLE_INTERNAL') || die;

/**
 * Class custompage
 * @author      Kevin Dibble
 * @copyright   2017 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custompage {

    /**
     * @var $_data
     */
    private $_data;

    /**
     * custompage constructor.
     * @param mixed $data
     */
    public function __construct($data) {
        $this->_data = $data;
    }

    /**
     *
     * This is to create a new page in the database
     *
     * @param mixed $data
     * @return Object
     */
    public function createpage($data) {
        global $DB;
        return $DB->insert_record('local_pages', $data);
    }

    /**
     *
     * This is to update the page based on the data object
     *
     * @param mixed $data
     * @return mixed
     */
    public function updatepage($data) {
        global $DB;
        return $DB->update_record('local_pages', $data);
    }

    /**
     *
     * This is to update or create a page if it does not exist
     *
     * @param mixed $data
     * @return mixed
     */
    public function update($data) {
        if (isset($data->id) && $data->id > 0) {
            $result = $this->updatepage($data);
            if ($result) {
                $result = $data->id;
            }
        } else {
            $result = $this->createpage($data);
        }
        return $result;
    }

    /**
     *
     * A getter to get items form the page object
     *
     * @param string $item
     * @return mixed
     */
    public function __get($item) {
        if (isset($this->_data->$item)) {
            return $this->_data->$item;
        }
    }

    /**
     *
     * This is to load the page based on the page id
     *
     * @param integer $id
     * @param bool $editor
     * @return object
     */
    public static function load($id, $editor = false) {
        global $DB, $CFG;
        require_once($CFG->libdir . '/formslib.php');
        require_once(dirname(__FILE__) . '/../lib.php');

        $data = new \stdClass();
        if (intval($id) > 0) {
            $data = $DB->get_record_sql("SELECT * FROM {local_pages} WHERE id=? LIMIT 1", array(intval($id)));
        } else {

            // Check url for page name.
            $main = explode('?', trim($_SERVER['REQUEST_URI']));
            $parts = explode("/", trim($main[0]));
            $url = '%' . end($parts) . '%';
            $page = $DB->get_record_sql("SELECT * FROM {local_pages} WHERE menuname LIKE ? limit 1", array(trim($url)));

            if ($page) {
                $data = $page;
            }
        }

        $str = get_string('noaccess', 'local_pages');
        $data->pagecontent = isset($data->pagecontent) ? $data->pagecontent : ($editor ? '' : $str);

        $context = \context_system::instance();
        if (!$editor) {
            $data->pagecontent = file_rewrite_pluginfile_urls($data->pagecontent, 'pluginfile.php',
                $context->id, 'local_pages', 'pagecontent', null);
        }
        return new custompage($data);
    }
}