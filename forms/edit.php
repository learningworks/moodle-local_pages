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
 * @package     local_pages
 * @author      Kevin Dibble
 * @copyright   2017 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/../lib.php');

/**
 * Class pages_edit_product_form
 *
 * @copyright   2017 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pages_edit_product_form extends moodleform {
    /**
     * @var $_pagedata
     */
    public $_pagedata;

    /**
     * @var $callingpage
     */
    public $callingpage;

    /**
     * pages_edit_product_form constructor.
     * @param mixed $page
     */
    public function __construct($page) {
        if ($page) {
            $this->_pagedata = $page->pagedata;
            $this->callingpage = $page->id;
        }
        parent::__construct();
    }

    /**
     *
     * Set the page data.
     *
     * @param mixed $defaults
     * @return mixed
     */
    public function set_data($defaults) {
        $context = context_system::instance();
        $draftideditor = file_get_submitted_draft_itemid('pagecontent');
        $defaults->pagecontent['text'] = file_prepare_draft_area($draftideditor, $context->id,
            'local_pages', 'pagecontent', 0, array('subdirs' => true), $defaults->pagecontent['text']);
        $defaults->pagecontent['itemid'] = $draftideditor;
        $defaults->pagecontent['format'] = FORMAT_HTML;
        return parent::set_data($defaults);
    }

    /**
     * Get a list of all pages
     */
    public function definition() {
        global $DB, $PAGE;

        // Get a list of all pages.
        $none = get_string("none", "local_pages");
        $pages = array(0 => $none);
        $allpages = $DB->get_records('local_pages', array('deleted' => 0));
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
            array("1" => get_string("yes", "local_pages"), "0" => get_string("no", "local_pages")), 0);

        $mform->addElement('text', 'accesslevel', get_string('page_accesslevel', 'local_pages'));
        $mform->addHelpButton('accesslevel', 'accesslevel_description', 'local_pages');
        $mform->setType('accesslevel', PARAM_TEXT);

        $mform->addElement('select', 'pagetype', get_string('page_pagetype', 'local_pages'),
            array("page" => get_string("page", "local_pages"),
                "form" => get_string("form", "local_pages")), 'page');

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

        if (method_exists($mform, "hideif")) {
            $mform->hideIf('emailto', 'pagetype', 'neq', 'form');
        }

        $mform->setType('id', PARAM_INT);
    }

    /**
     *
     * Validate the form
     *
     * @param mixed $data
     * @param mixed $files
     * @return mixed
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

    /**
     *
     * Build the HTML form elements
     *
     * @return string
     */
    private function build_html_form() {
        global $DB;
        $usertable = $DB->get_record_sql("select * FROM {user} LIMIT 1");
        $records = json_decode($this->_pagedata);

        // PHP 7.2 now gives an error if the item cannot be counted - pre 7.2 it returned 0.
        $limit = intval(@count($records));

        $i = 0;
        $html = '<div class="form-builder row" id="form-builder">' .
            '<h3 style="width:100%"><a href="#" id="showform-builder">'. get_string('formbuilder', 'local_pages') .'  ' .
            '<span id="showEdit">' . get_string('show', 'local_pages') .
            '</span> <span id="hideEdit">' . get_string('hide', 'local_pages') .
            '</span></a></h3><div class="formbuilderform">';
        do {
            $html .= '<div class="formrow row"><div class="col-sm-12 col-md-2 span2"><label>' .
                get_string('label_name', 'local_pages') .' </label>' .
                '<textarea class="form-control field-name" name="fieldname[]" ' .
                'placeholder="' . get_string('placeholder_fieldname', 'local_pages') .
                '" style="height:25px;resize:none;overflow:hidden">' .
                (isset($records[$i]) ? $records[$i]->name : '') .
                '</textarea></div>';
            $html .= '<div class="col-sm-12 col-md-2 span2"><label>'.
                get_string('label_placeholder', 'local_pages') . '</label>' .
                '<textarea type="text" class="form-control default-name" ' .
                'name="defaultvalue[]" style="height:25px;resize:none;overflow:hidden" placeholder="' .
                get_string('placeholder_text', 'local_pages') . '">' .
                (isset($records[$i]) ? $records[$i]->defaultvalue : '') .
                '</textarea></div>';

            $html .= '<div class="col-sm-12 col-md-2 span2"><label>' .
                get_string('label_relatesto', 'local_pages') .' </label>' .
                '<select class="form-control field-readsfrom" name="readsfrom[]">' .
                '<option value="">'. get_string('select_nothing', 'local_pages')  .' </option>';
            $keys = array_keys((array)$usertable);
            foreach ($keys as $key) {
                $html .= '<option ' . ((isset($records[$i]) &&
                        isset($records[$i]->readsfrom) &&
                        $records[$i]->readsfrom == $key) ? 'selected="selected"' : '') . '>' . $key . '</option>';
            }
            $html .= '<option value="fullname" ' . ((isset($records[$i]) &&
                    isset($records[$i]->readsfrom) &&
                    $records[$i]->readsfrom == "fullname") ? 'selected="selected"' : '') . '>' .
                get_string('select_fullname', 'local_pages') . '</option>';
            $html .= '</select></div>';

            $html .= '<div class="col-sm-12 col-md-2 span2"><label>' .
                get_string('label_required', 'local_pages') .'</label>' .
                '<select class="form-control field-required" name="fieldrequired[]">' .
                '<option value="Yes" ' . (isset($records[$i]) &&
                $records[$i]->required == 'Yes' ? 'selected="selected"' : '') . '>' .
                get_string('select_yes', 'local_pages') .'</option>' .
                '<option value="No" ' . (isset($records[$i]) &&
                $records[$i]->required == 'No' ? 'selected="selected"' : '') . '>' .
                get_string('select_no', 'local_pages').'</option>' .
                '</select></div>';

            $html .= '<div class="col-sm-12 col-md-2 span2"><label>' .
                get_string('type', 'local_pages') . '</label>' .
                '<select class="form-control field-type" name="fieldtype[]">' .
                '<option value="Text" ' . (isset($records[$i]) &&
                $records[$i]->type == 'Text' ? 'selected="selected"' : '') . ' >' .
                get_string('select_text', 'local_pages') . '</option>' .
                '<option value="Email" ' . (isset($records[$i]) &&
                $records[$i]->type == 'Email' ? 'selected="selected"' : '') . ' >' .
                get_string('select_email', 'local_pages') . '</option>' .
                '<option value="Number" ' . (isset($records[$i]) &&
                $records[$i]->type == 'Number' ? 'selected="selected"' : '') . '  >' .
                get_string('select_number', 'local_pages')  . '</option>' .
                '<option value="Checkbox" ' . (isset($records[$i]) &&
                $records[$i]->type == 'Checkbox' ? 'selected="selected"' : '') . ' >' .
                get_string('select_checkbox', 'local_pages') . '</option>' .
                '<option value="Text Area"' . (isset($records[$i]) &&
                $records[$i]->type == 'Text Area' ? 'selected="selected"' : '') . ' >' .
                get_string('select_text_area', 'local_pages') . '</option>' .
                '<option value="Select" ' . (isset($records[$i]) &&
                $records[$i]->type == 'Select' ? 'selected="selected"' : '') . ' >' .
                get_string('select_select', 'local_pages') . '</option>' .
                '<option value="HTML" ' . (isset($records[$i]) &&
                $records[$i]->type == 'HTML' ? 'selected="selected"' : '') . ' >' .
                get_string('select_html', 'local_pages') . '</option>' .
                '</select></div>';

            $html .= '<div class="col-sm-12 col-md-2 span2"><label style="width:100%"> &nbsp;</label>' .
                '<input type="button" value="' . get_string('label_add', 'local_pages') . '" ' .
                'class="form-submit form-addrow btn btn-primary" name="submitbutton" type="button" />' .
                '<input type="button" value="' . get_string('label_remove', 'local_pages') .'" ' .
                'class="form-submit form-removerow btn btn-danger" name="cancel" type="button" />' .
                '</div>' .
                '</div>';
            $i++;
        } while ($i < $limit);

        $html .= '</div></div>';
        return $html;
    }
}