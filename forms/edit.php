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
 * Moodec pages dynamic Form
 *
 * @package     local
 * @subpackage  local_pages
 * @author      Kevin Dibble
 * @copyright   2017 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/../lib.php');

class pages_edit_product_form extends moodleform {
    public $_pagedata;
    public $callingpage;

    public function __construct($page) {
        if ($page) {
            $this->_pagedata = $page->pagedata;
            $this->callingpage = $page->id;
        }
        parent::__construct();
    }

    public function set_data($defaults) {
        $context = context_system::instance();
        $draftideditor = file_get_submitted_draft_itemid('pagecontent');
        $defaults->pagecontent['text'] = file_prepare_draft_area($draftideditor, $context->id,
            'local_pages', 'pagecontent', 0, array('subdirs' => true), $defaults->pagecontent['text']);
        $defaults->pagecontent['itemid'] = $draftideditor;
        $defaults->pagecontent['format'] = FORMAT_HTML;
        return parent::set_data($defaults);
    }

    // Add elements to form.
    public function definition() {
        global $DB, $PAGE;

        // GET A list of all pages.
        $pages = array(0 => 'None');
        $allpages = $DB->get_records('local_pages', array('deleted' => 0, 'pagetype' => 'page'));
        foreach ($allpages as $page) {
            if ($page->id != $this->callingpage) {
                $pages[$page->id] = $page->pagename;
            }
        }
        $hasstandard = false;
        $layouts = array("standard" => "standard");
        $layoutkeys = array_keys($PAGE->theme->layouts);
        foreach ($layoutkeys as $layoutname) {
            if (strtolower($layoutname) != "standard") {
                $layouts[$layoutname] = $layoutname;
            } else {
                $hasstandard = true;
            }
        }
        if (!$hasstandard) {
            unset($layouts['standard']);
        }

        $mform = $this->_form;

        $mform->addElement(
            'date_selector', 'pagedate',
            get_string(
                'page_date',
                'local_pages'
            ), get_string('to')
        );
        $mform->setType('pagedate', PARAM_TEXT);
        $mform->addHelpButton('pagedate', 'pagedate_description', 'local_pages');

        $mform->addElement('text', 'pagename', get_string('page_name', 'local_pages'));
        $mform->setType('pagename', PARAM_TEXT);

        $mform->addElement('text', 'menuname', get_string('menu_name', 'local_pages'));
        $mform->setType('menuname', PARAM_TEXT);

        $mform->addElement('text', 'emailto', get_string('emailto_name', 'local_pages'));
        $mform->setType('emailto', PARAM_TEXT);

        $mform->addElement('select', 'pagelayout', get_string('pagelayout_name', 'local_pages'), $layouts);

        $mform->getElement('pagelayout')->setSelected('standard');

        $mform->addElement('text', 'pageorder', get_string('page_order', 'local_pages'));
        $mform->setType('pageorder', PARAM_INT);

        $mform->addElement('select', 'pageparent', get_string('page_parent', 'local_pages'), $pages);

        $mform->addElement('select', 'onmenu', get_string('page_onmenu', 'local_pages'),
            array("1" => "Yes", "0" => "No"), 0);

        $mform->addElement('text', 'accesslevel', get_string('page_accesslevel', 'local_pages'));
        $mform->addHelpButton('accesslevel', 'accesslevel_description', 'local_pages');
        $mform->setType('accesslevel', PARAM_TEXT);

        $mform->addElement('select', 'pagetype', get_string('page_pagetype', 'local_pages'),
            array("page" => "Page", "form" => "Form"), 'page');

        $context = context_system::instance();
        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $context);

        $mform->addElement('editor', 'pagecontent', get_string('page_content', 'local_pages'),
            get_string('page_content_description', 'local_pages'), $editoroptions);

        $mform->addRule('pagecontent', null, 'required', null, 'client');
        $mform->setType('pagecontent', PARAM_RAW); // XSS is prevented when printing the block contents and serving files.

        $mform->addHelpButton('pagecontent', 'pagecontent_description', 'local_pages');

        $mform->addElement('html', $this->build_html_form());

        // FORM BUTTONS.
        $this->add_action_buttons();

        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);
    }

    // Custom validation should be added here.
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

    private function build_html_form() {
        global $DB;
        $usertable = $DB->get_record_sql("select * FROM {user} LIMIT 1");
        $records = json_decode($this->_pagedata);
        $limit = count($records);

        $i = 0;
        $html = '<div class="form-builder" id="form-builder">' .
            '<h3><a href="#" id="showform-builder">Form Builder ' .
            '<span id="showEdit">Show</span> <span id="hideEdit">Hide</span></a></h3><div class="formbuilderform">';
        do {
            $html .= '<div class="formrow"><div class="col-sm-12 col-md-2 span2"><label>Name</label>' .
                '<textarea class="form-control field-name" name="fieldname[]" ' .
                'placeholder="Field Name" style="height:25px;resize:none;overflow:hidden">' .
                (isset($records[$i]) ? $records[$i]->name : '') .
                '</textarea></div>';
            $html .= '<div class="col-sm-12 col-md-2 span2"><label>Placeholder</label>' .
                '<textarea type="text" class="form-control default-name" ' .
                'name="defaultvalue[]" style="height:25px;resize:none;overflow:hidden" placeholder="Placeholder text">' .
                (isset($records[$i]) ? $records[$i]->defaultvalue : '') .
                '</textarea></div>';

            $html .= '<div class="col-sm-12 col-md-2 span2"><label>Relates to</label>' .
                '<select class="form-control field-readsfrom" name="readsfrom[]">' .
                '<option value="">Nothing</option>';
            $keys = array_keys((array)$usertable);
            foreach ($keys as $key) {
                $html .= '<option ' . ((isset($records[$i]) &&
                        isset($records[$i]->readsfrom) &&
                        $records[$i]->readsfrom == $key) ? 'selected="selected"' : '') . '>' . $key . '</option>';
            }
            $html .= '<option ' . ((isset($records[$i]) &&
                    isset($records[$i]->readsfrom) &&
                    $records[$i]->readsfrom == "fullname") ? 'selected="selected"' : '') . '>fullname</option>';
            $html .= '</select></div>';

            $html .= '<div class="col-sm-12 col-md-2 span2"><label>Required</label>' .
                '<select class="form-control field-required" name="fieldrequired[]">' .
                '<option ' . (isset($records[$i]) &&
                $records[$i]->required == 'Yes' ? 'selected="selected"' : '') . '>Yes</option>' .
                '<option ' . (isset($records[$i]) &&
                $records[$i]->required == 'No' ? 'selected="selected"' : '') . '>No</option>' .
                '</select></div>';

            $html .= '<div class="col-sm-12 col-md-2 span2"><label>Type</label>' .
                '<select class="form-control field-type" name="fieldtype[]">' .
                '<option ' . (isset($records[$i]) &&
                $records[$i]->type == 'Text' ? 'selected="selected"' : '') . ' >Text</option>' .
                '<option ' . (isset($records[$i]) &&
                $records[$i]->type == 'Email' ? 'selected="selected"' : '') . ' >Email</option>' .
                '<option ' . (isset($records[$i]) &&
                $records[$i]->type == 'Number' ? 'selected="selected"' : '') . '  >Number</option>' .
                '<option ' . (isset($records[$i]) &&
                $records[$i]->type == 'Checkbox' ? 'selected="selected"' : '') . ' >Checkbox</option>' .
                '<option ' . (isset($records[$i]) &&
                $records[$i]->type == 'Text Area' ? 'selected="selected"' : '') . ' >Text Area</option>' .
                '<option ' . (isset($records[$i]) &&
                $records[$i]->type == 'Select' ? 'selected="selected"' : '') . ' >Select</option>' .
                '<option ' . (isset($records[$i]) &&
                $records[$i]->type == 'HTML' ? 'selected="selected"' : '') . ' >HTML</option>' .
                '</select></div>';

            $html .= '<div class="col-sm-12 col-md-2 span2"><label style="width:100%"> &nbsp;</label>' .
                '<input type="button" value="add" ' .
                'class="form-submit form-addrow" name="submitbutton" type="button" />' .
                '<input type="button" value="remove" ' .
                'class="form-submit form-removerow" name="cancel" type="button" />' .
                '</div>' .
                '</div>';
            $i++;
        } while ($i < $limit);

        $html .= '</div></div>';
        return $html;
    }
}