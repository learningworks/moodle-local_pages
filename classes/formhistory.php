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
 * Pages Form history
 *
 * @package     local_pages
 * @author      Kevin Dibble
 * @copyright   2017 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_pages;

defined('MOODLE_INTERNAL') || die;

// Load Tablelib lib.
require_once($CFG->dirroot . '/lib/tablelib.php');

/**
 * Class formhistory
 * @copyright   2017 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * Create and show form submission history
 */
class formhistory extends \table_sql {

    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);
        // Define the list of columns to show.
        $columns = array(
            'id',
            'formdate',
            'formcontent'
        );
        $this->define_columns($columns);

        // Define the titles of columns to show in header.
        $headers = array(
            get_string('form_field_id', 'local_pages'),
            get_string('form_field_date', 'local_pages'),
            get_string('form_field_content', 'local_pages'),
        );
        $this->define_headers($headers);
        $this->sortable(true, 'formdate', SORT_DESC);
        $this->no_sorting("id");
        $this->no_sorting('formcontent');
    }

    /**
     * This function is called to return the id of the object
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return id of record
     */
    public function col_id($values) {
        return $values->id;
    }

    /**
     * This function is called for each data row to allow processing of the
     * date value.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return purchase_date formatted like 01/12/1991
     */
    public function col_formdate($values) {
        return date('j M Y', $values->formdate);
    }

    /**
     * This function is called to format row data
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return html string if not downloading
     */
    public function col_formcontent($values) {
        $data = json_decode($values->formcontent);
        $html = '';
        foreach ($data as $key => $value) {
            $html .= ucfirst($key) . " = " . $value;
            if (!$this->is_downloading()) {
                $html .= "<br />";
            } else {
                $html .= " | ";
            }
        }
        return $html;
    }
}