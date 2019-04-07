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
 * Local Pages Renderer
 *
 * @package     local_pages
 * @author      Kevin Dibble
 * @copyright   2017 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/pages/forms/edit.php');

/**
 *
 * Class local_pages_renderer
 *
 * @copyright   2017 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_pages_renderer extends plugin_renderer_base {

    /**
     * @var array
     */
    public $errorfields = array();

    /**
     *
     * Get the submenu item
     *
     * @param mixed $parent
     * @param string $name
     * @return string
     */
    public function get_submenuitem($parent, $name) {
        global $DB, $CFG, $USER;
        $html = '';
        $records = $DB->get_records_sql("SELECT * FROM {local_pages} WHERE deleted=0 AND " .
            "pageparent=? ORDER BY pageorder", array($parent));
        if ($records) {
            $html .= "<li class='custompages-list-element'>";
            $html .= '<div class="pages-action">' .
                '<a href="' . new moodle_url($CFG->wwwroot . '/local/pages/',
                    array('id' => $parent)) . '" class="custompages-edit">' .
                    get_string('view', 'local_pages') .'</a> | ' .
                '<a href="' . new moodle_url($CFG->wwwroot . '/local/pages/edit.php',
                    array('id' => $parent)) . '" class="custompages-edit">' .
                    get_string('edit', 'local_pages') . '</a> | ' .
                '<a href="' . new moodle_url($CFG->wwwroot . '/local/pages/pages.php',
                    array('pagedel' => $parent, 'sesskey' => $USER->sesskey)) . '" class="custompages-delete">' .
                    get_string('delete', 'local_pages') .' </a></div>';
            $html .= "<h4 class='custompages-title'>" . $name . "</h4>";
            $html .= "<ul class='custompages_submenu'>";
            foreach ($records as $page) {
                $html .= $this->get_submenuitem($page->id, $page->pagename);
            }
            $html .= "</ul>";
            $html .= "</li>";
        } else {
            $html .= "<li class='custompages-list-element'>";
            $html .= '<div class="pages-action">' .
                '<a href="' . new moodle_url($CFG->wwwroot . '/local/pages/',
                    array('id' => $parent)) . '" class="custompages-edit">' .
                    get_string('view', 'local_pages') .'</a> | ' .
                '<a href="' . new moodle_url($CFG->wwwroot . '/local/pages/edit.php',
                    array('id' => $parent)) . '" class="custompages-edit">' .
                    get_string('edit', 'local_pages') .'</a> | ' .
                '<a href="' . new moodle_url($CFG->wwwroot . '/local/pages/pages.php',
                    array('pagedel' => $parent, 'sesskey' => $USER->sesskey)) . '" class="custompages-delete">' .
                    get_string('delete', 'local_pages') .' </a></div>';
            $html .= "<h4 class='custompages-title'>" . $name . "</h4>";
            $html .= "</li>";
        }
        return $html;
    }

    /**
     *
     * List the pages for the user to view
     *
     * @return string
     */
    public function list_pages() {
        global $DB, $CFG;
        $html = '<ul class="custompages-list">';
        $records = $DB->get_records_sql("SELECT * FROM {local_pages} WHERE deleted=0 AND pageparent=0 ORDER BY pageorder");
        foreach ($records as $page) {
            $html .= $this->get_submenuitem($page->id, $page->pagename);
        }

        $html .= "<li class='custompages-list-element'>
                	<a href='" . new moodle_url($CFG->wwwroot . '/local/pages/edit.php') .
            "' class='custompages-add'>" . get_string("addpage", "local_pages") . "</a>
            	</li>";

        $html .= "<li class='custompages-list-element'>
					<a target='_blank' href='" . new moodle_url($CFG->wwwroot .
                '/local/pages/pages.pdf') . "' class='custompages-add'>" . get_string("pdfmanual", "local_pages") ."</a>
				</li>";

        $html .= "</ul>";
        return $html;
    }

    /**
     *
     * Show the page based on users rights
     *
     * @param mixed $page
     * @return mixed
     */
    public function showpage($page) {
        global $DB;
        $context = context_system::instance();
        $canaccess = true;
        if (trim($page->accesslevel) != '') {
            $canaccess = false;        // Page Has level Requirements - check rights.
            $levels = explode(",", $page->accesslevel);
            foreach ($levels as $key => $level) {
                if ($canaccess != true) {
                    if (stripos($level, "!") !== false) {
                        $level = str_replace("!", "", $level);
                        $canaccess = has_capability(trim($level), $context) ? false : true;
                    } else {
                        $canaccess = has_capability(trim($level), $context) ? true : false;
                    }
                }
            }
        }

        if ($canaccess && ($page->pagedate <= date('U') || is_siteadmin())) {
            $today = date('U');
            $records = $DB->get_records_sql("SELECT * FROM {local_pages} WHERE deleted=0 AND pagetype <> 'page' " .
                "AND pageparent=? AND pagedate <=? ORDER BY pageorder", array($page->id, $today));
            $form = '';
            foreach ($records as $key => $value) {
                switch (strtolower($value->pagetype)) {
                    case 'form':
                        $form = $this->createform($value);
                        break;
                }
            }

            // Format the input for XSS.
            $page->pagecontent = format_text($this->adduserdata($page->pagecontent), FORMAT_HTML,
                ['trusted' => true, 'noclean' => true]);

            return str_replace(array("#form#", "{form}"), array($form, $form), $page->pagecontent);
        } else {
            return get_string('noaccess', 'local_pages');
        }
    }

    /**
     *
     * Add user data to the form to display on the page
     *
     * @param mixed $data
     * @return mixed
     */
    public function adduserdata($data) {
        global $USER, $DB;
        if (isloggedin()) {
            $usr = $USER;
        } else {
            $usr = $DB->get_record_sql('SELECT * FROM {user} WHERE id=1');
        }
        foreach ((array)$usr as $key => $details) {
            if (!is_array($details) && !is_object($details)) {
                $data = str_ireplace('{' . $key . '}', $details, $data);
            }
        }
        return $data;
    }

    /**
     *
     * Create the form for the page
     *
     * @param mixed $data
     * @return string
     */
    public function createform($data) {
        global $USER;

        // Setup required parameters.
        $honeypot = optional_param('hp', '', PARAM_RAW);
        $formsubmit = optional_param('formsubmit', 0, PARAM_RAW);

        if (isloggedin()) {
            $USER->fullname = $USER->firstname . " " . $USER->lastname;
        }
        $records = json_decode($data->pagedata);
        $valuesout = array();
        $valuesin = array();

        if ($formsubmit == 1 && $honeypot == '') {
            if ($this->valid($records)) {
                $cache = cache::make('local_pages', 'sent');
                if (!$cache->get($data->pagename)) {
                    $this->processform($data);
                    if (get_config('local_pages', 'enable_limit') != 0) {
                        $cache->set($data->pagename, 'sent');
                    }
                    foreach ((array)$records as $key => $value) {
                        $valuesout[] = "{" . $value->name . "}";

                        // Get all data sent from the form.
                        $tmpparam = str_replace(" ", "_", $value->name);
                        $tmpparam = optional_param($tmpparam, '', PARAM_RAW);
                        $valuesin[] = $tmpparam;
                    }
                    return str_replace($valuesout, $valuesin, $data->pagecontent);
                } else {
                    return get_string('cannnot_send', 'local_pages');
                }
            }
        }

        $str = '<form method="post" action="" class="mform">';
        foreach ((array)$records as $key => $value) {
            $errorclass = isset($this->error_fields[$value->name]) ? 'has-error' : '';
            $record = $value->readsfrom;
            $tmpparam = str_replace(' ', '', $value->name);
            $tmpparam = optional_param($tmpparam, '', PARAM_RAW);
            if ($value->type == "Text Area") {
                $str .= '<div class="form-group fitem ' . $errorclass . '">';
                $str .= '<div class="fitemtitle"><label for="' .
                    str_replace(" ", "", $value->name) . '">' . $value->name . '</label></div>';
                $str .= '<div class="felement"><textarea class="form-control" name="' .
                    str_replace(" ", "_", $value->name) . '" id="' .
                    str_replace(" ", "", $value->name) . '" ' . ($value->required == "Yes" ? "Required" : '') .
                    ' placeholder="' . $value->defaultvalue . '">' .
                    ($tmpparam != '' ? $tmpparam : (isset($USER->$record) ? $USER->$record : ''))
                    . '</textarea></div></div>';
            } else if (strtolower($value->type) == "checkbox") {
                $str .= '<div class="checkbox ' . $errorclass . '">';
                $str .= '<label for="' . str_replace(" ", "", $value->name) . '">';
                $str .= '<input name="' . str_replace(" ", "_", $value->name) . '" type="hidden" value="0"  id="' .
                    str_replace(" ", "", $value->name) . '" />';
                $str .= '<input name="' . str_replace(" ", "_", $value->name) . '" type="' .
                    strtolower($value->type) . '" value="' . $tmpparam . '" id="' .
                    str_replace(" ", "", $value->name) . '" ' . ($value->required == "Yes" ? "Required" : '') .
                    ' placeholder="' . $value->defaultvalue . '" />';
                $str .= $value->name . '</label></div>';
            } else {
                if ($value->type == "HTML") {
                    $str .= '<div class="form-break">' . $value->name ."</div>";
                } else if ($value->type == "Select") {
                    $str .= '<div class="form-group fitem fitem_fselect' . $errorclass . '">';
                    $str .= '<div class="fitemtitle"><label for="' . str_replace(" ", "", $value->name) . '">' .
                        $value->name . '</label></div>';
                    $str .= '<div class="felement fselect">'.
                        '<select class="form-control" ' . ($value->required == "Yes" ? "Required" : '') .
                        ' name="' . str_replace(" ", "_", $value->name) . '" id="' .
                        str_replace(" ", "", $value->name) . '">';
                    $selectlist = explode("\r\n", $value->defaultvalue);
                    foreach ($selectlist as $option) {
                        $options = explode("|", $option);
                        if (trim($options[0]) == '' && !isset($options[1])) {
                            $options[1] = get_string("pleaseselect", "local_pages");
                        }
                        $str .= '<option value="' . $options[0] . '" ' .
                            ($tmpparam == $options[0] ? 'selected="selected"' : '') . ' >' .
                            (isset($options[1]) ? $options[1] : $options[0]) . '</option>';
                    }
                    $str .= '</select></div></div>';
                } else {
                    $str .= '<div class="form-group fitem fitem_ftext ' . $errorclass . '">';
                    $str .= '<div class="fitemtitle"><label for="' . str_replace(" ", "", $value->name) . '">' .
                        $value->name . '</label></div>';
                    $str .= '<div class="felement ftext"><input name="' . str_replace(" ", "_", $value->name) . '" type="' .
                        strtolower($value->type) . '" value="' .
                        ($tmpparam != '' ? $tmpparam : (isset($USER->$record) ? $USER->$record : ''))
                        . '" placeholder="' . $value->defaultvalue .
                        '" class="form-control" id="' . str_replace(" ", "", $value->name) . '" ' .
                        ($value->required == "Yes" ? "Required" : '') . ' /></div></div>';
                }
            }
        }

        if (isset($this->error_fields[$value->name])) {
            $str .= '<span class="help-block">' . $this->error_fields[$value->name] . '</span>';
        }

        $str .= '<div class="fitem fitem_actionbuttons fitem_fgroup"><div class="felement fgroup">' .
            '<input type="text" name="hp" value="" style="position:absolute;left:-99999px" /> ' .
            '<button type="submit" name="formsubmit" value="1" class="btn btn-primary">' .
            get_string("submit", "local_pages") .'</button>' .
            '</div></div></form>';
        return $str;
    }

    /**
     *
     * Check if the form is valid
     *
     * @param  mixed $records
     * @return bool
     */
    public function valid($records) {
        $valid = true;
        foreach ((array)$records as $key => $value) {
            $tmpparam = str_replace(" ", "_", $value->name);
            $tmpparam = optional_param($tmpparam, '', PARAM_RAW);

            if ($value->required == "Yes" && $value->type != "HTML") {
                if ($value->type == "Email" && (stripos($tmpparam, "@") === false ||
                        stripos($tmpparam, ".") === false)
                ) {
                    $this->error_fields[$value->name] = "Please Supply a valid email address for " . $value->name;
                    $valid = false;
                }

                if ($value->type != 'Email' && $tmpparam == '') {
                    $this->error_fields[$value->name] = "Please fill in " . $value->name;
                    $valid = false;
                }

                if ($value->type == 'Numeric' && !is_numeric($tmpparam)) {
                    $this->error_fields[$value->name] = "Please provide a number for " . $value->name;
                    $valid = false;
                }
            }
        }
        return $valid;
    }

    /**
     *
     * Process the submitted form up update page data
     *
     * @param mixed $page
     */
    public function processform($page) {
        global $DB;
        $touser = get_admin();
        $fromuser = clone $touser;

        $touser->email = isset($page->emailto) && trim($page->emailto) ? $page->emailto : $touser->email;
        $touser->emailstop = 0;

        $messagetext = '';
        $fields = array();
        $records = json_decode($page->pagedata);
        $outarray = array();
        foreach ((array)$records as $key => $value) {
            if ($value->type != "HTML") {
                $outarray[] = "{" . $value->name . "}";

                $tmpparam = str_replace(" ", "_", $value->name);
                $tmpparam = optional_param($tmpparam, '', PARAM_RAW);

                $fields[$value->name] = $tmpparam;
                $messagetext .= ucfirst($value->name) . ": " . $tmpparam . "\r\n";
                $field = strtolower(str_replace(" ", "", $value->name));
                $fromuser->$field = $tmpparam;
            }
        }

        $messagehtml = nl2br($messagetext);
        $subject = $page->pagename;

        $data = new stdClass();
        $data->formdate = date('U');
        $data->formcontent = json_encode($fields);
        $data->formname = $page->id;
        $DB->insert_record('local_pageslogging', $data);

        email_to_user($touser, $fromuser, $subject, $messagetext, $messagehtml, '', '', true);

        $outarray[] = "{table}";
        $fields[] = $messagetext;

        $messageforuser = str_replace($outarray, $fields, get_config('local_pages', 'message_copy'));
        $messagetext = strip_tags(str_replace(array("</p>", "<br>", "&nbsp;"), array("</p>\r\n", "<br>\r\n", ""),
            $messageforuser));

        // Emails a copy to the user.
        if (get_config('local_pages', 'user_copy') == 1) {
            email_to_user($fromuser, $touser, $subject, $messagetext, $messageforuser, '', '', true);
        }
    }

    /**
     *
     * Save the page to the database and redirect the user
     *
     * @param bool $page
     */
    public function save_page($page = false) {
        global $CFG;
        $mform = new pages_edit_product_form($page);
        if ($mform->is_cancelled()) {
            redirect(new moodle_url($CFG->wwwroot . '/local/pages/pages.php'));
        } else if ($data = $mform->get_data()) {
            require_once($CFG->libdir . '/formslib.php');

            $context = context_system::instance();
            $data->pagecontent['text'] = file_save_draft_area_files($data->pagecontent['itemid'], $context->id,
                'local_pages', 'pagecontent',
                0, array('subdirs' => true), $data->pagecontent['text']);

            $data->pagedata = '';
            if (strtolower($data->pagetype) == "form") {
                $pagedata = array();
                $fieldnames = required_param_array('fieldname', PARAM_RAW);
                $fieldtype = required_param_array('fieldtype', PARAM_RAW);
                $fieldrequired = required_param_array('fieldrequired', PARAM_RAW);
                $fielddefault = required_param_array('defaultvalue', PARAM_RAW);
                $fieldreadsfrom = required_param_array('readsfrom', PARAM_RAW);

                foreach ($fieldnames as $key => $value) {
                    // Get all data sent from the form.
                    // Stop empty fields being created.
                    if (trim($value) != '') {
                        $pagedata[] = array("name" => $value,
                            "type" => $fieldtype[$key],
                            "required" => $fieldrequired[$key],
                            "defaultvalue" => $fielddefault[$key],
                            "readsfrom" => $fieldreadsfrom[$key]);
                    }
                }
                $data->pagedata = json_encode($pagedata);
            }
            $recordpage = new stdClass();
            $recordpage->id = $data->id;
            $recordpage->pagedate = $data->pagedate;
            $recordpage->pagename = $data->pagename;
            $recordpage->pageorder = intval($data->pageorder);
            $recordpage->menuname = strtolower(str_replace(array(" ", "/", "\\", "'", '"', ";", "~",
                "?", "&", "@", "#", "$", "%", "^", "*", "(", ")", "+", "="), "", trim($data->menuname)));
            $recordpage->onmenu = $data->onmenu;
            $recordpage->accesslevel = $data->accesslevel;
            $recordpage->pagedata = $data->pagedata;
            $recordpage->pagetype = $data->pagetype;
            $recordpage->emailto = isset($data->emailto) ? $data->emailto : '';
            $recordpage->pagelayout = $data->pagelayout;
            $recordpage->pageparent = intval($data->pageparent);
            $recordpage->pagecontent = $data->pagecontent['text'];
            $result = $page->update($recordpage);
            if ($result && $result > 0) {
                redirect(new moodle_url($CFG->wwwroot . '/local/pages/edit.php', array('id' => $result)));
            }
        }
    }

    /**
     *
     * Show the page information to edit
     *
     * @param bool $page
     */
    public function edit_page($page = false) {
        $mform = new pages_edit_product_form($page);
        $forform = new stdClass();
        $forform->pagecontent['text'] = $page->pagecontent;
        $forform->pagename = $page->pagename;
        $forform->onmenu = $page->onmenu;
        $forform->accesslevel = $page->accesslevel;
        $forform->pageparent = $page->pageparent;
        $forform->menuname = $page->menuname;
        $forform->id = $page->id;
        $forform->emailto = $page->emailto;
        $forform->pagedate = $page->pagedate;
        $forform->pagelayout = $page->pagelayout;
        $forform->pageorder = $page->pageorder;
        $forform->pagetype = $page->pagetype;
        $mform->set_data($forform);
        $mform->display();
    }

    /**
     *
     * Gets all the menu items
     *
     * @param mixed $parent
     * @param string $name
     * @param string $url
     * @return string
     */
    public function get_menuitem($parent, $name, $url) {
        global $DB, $CFG;
        $context = context_system::instance();
        $html = '';
        $urllocation = new moodle_url($CFG->wwwroot . '/local/pages/', array('id' => $parent));
        if (get_config('local_pages', 'cleanurl_enabled')) {
            $urllocation = new moodle_url($CFG->wwwroot . '/local/pages/' . $url);
        }
        $today = date('U');
        $records = $DB->get_records_sql("SELECT * FROM {local_pages} WHERE deleted=0 AND onmenu=1 " .
            "AND pagetype='page' AND pageparent=? AND pagedate <=? " .
            "ORDER BY pageorder", array($parent, $today));
        if ($records) {
            $html .= "<li class='custompages_item'><a href='" . $urllocation . "'>" . $name . "</a>";
            $html .= "<ul class='custompages_submenu'>";
            $canaccess = true;
            foreach ($records as $page) {
                if (isset($page->accesslevel) && stripos($page->accesslevel, ":") !== false) {
                    $canaccess = false;        // Page Has level Requirements - check rights.
                    $levels = explode(",", $page->accesslevel);
                    foreach ($levels as $level) {
                        if ($canaccess != true) {
                            if (stripos($level, "!") !== false) {
                                $level = str_replace("!", "", $level);
                                $canaccess = has_capability(trim($level), $context) ? false : true;
                            } else {
                                $canaccess = has_capability(trim($level), $context) ? true : false;
                            }
                        }
                    }
                }
                if ($canaccess) {
                    $html .= $this->get_menuitem($page->id, $page->pagename, $page->menuname);
                }
            }
            $html .= "</ul>";
            $html .= "</li>";
        } else {
            $html .= "<li class='custompages_item'><a href='" . $urllocation . "'>" . $name . "</a></li>";
        }
        return $html;
    }

    /**
     *
     * Builds the menu for the page
     *
     * @return string
     */
    public function build_menu() {
        global $DB;
        $context = context_system::instance();
        $dbman = $DB->get_manager();
        $html = '';
        if ($dbman->table_exists('local_pages')) {
            $html = '<ul class="custompages_nav">';
            $today = date('U');
            $records = $DB->get_records_sql("SELECT * FROM {local_pages} WHERE deleted=0 AND onmenu=1 " .
                "AND pagetype='page' AND pageparent=0 AND pagedate <= ? ORDER BY pageorder", array($today));
            $canaccess = true;
            foreach ($records as $page) {
                if (isset($page->accesslevel) && stripos($page->accesslevel, ":") !== false) {
                    $canaccess = false;
                    $levels = explode(",", $page->accesslevel);
                    foreach ($levels as $key => $level) {
                        if ($canaccess != true) {
                            if (stripos($level, "!") !== false) {
                                $level = str_replace("!", "", $level);
                                $canaccess = has_capability(trim($level), $context) ? false : true;
                            } else {
                                $canaccess = has_capability(trim($level), $context) ? true : false;
                            }
                        }
                    }
                }
                if ($canaccess) {
                    $html .= $this->get_menuitem($page->id, $page->pagename, $page->menuname);
                }
            }
            $html .= "</ul>";
        }
        return $html;
    }
}