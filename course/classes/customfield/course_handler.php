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
 * @package   core_customfield
 * @copyright 2018 David Matamoros <davidmc@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_course\customfield;

defined('MOODLE_INTERNAL') || die;

/**
 * Course handler for custom fields
 *
 * @package core_course
 */
class course_handler extends \core_customfield\handler {

    /**
     * @var course_handler
     */
    static protected $singleton;

    /**
     * Returns a singleton
     *
     * @param int $itemid
     * @return \core_customfield\handler
     */
    public static function instance(int $itemid = 0) : \core_customfield\handler {
        if (static::$singleton === null) {
            self::$singleton = new static(0);
        }
        return self::$singleton;
    }

    /**
     * The current user can configure custom fields on this component.
     *
     * @return bool true if the current can configure custom fields, false otherwise
     */
    public function can_configure() : bool {
        // TODO separate capability.
        return has_capability('moodle/category:manage', \context_system::instance());
    }

    /**
     * The current user can edit custom fields on the given course.
     *
     * @param int $recordid id of the course to test edit permission
     * @return bool true if the current can edit custom fields, false otherwise
     */
    public function can_edit($recordid = null) : bool {
        if ($recordid) {
            return has_capability('moodle/course:changecustomfields', \context_course::instance($recordid));
        } else {
            // guess_if_creator_will_have_course_capability()
            return true; // TODO.
        }
    }

    /**
     * Adds custom fields to edit forms.
     *
     * @param int $courseid
     */
    public function display_fields($courseid) {
        $fields = $this->get_fields_with_data($courseid);
        $content = \html_writer::start_tag('div', ['class' => 'customfields-container', 'style' => 'clear: both;']);
        foreach ($fields as $data) {
            $visibility = $data->get_field()->get('visibility');
            $canview = false;
            if ($visibility == 0) {
                $canview = false;
            } else if ($visibility == 1) {
                $canview = has_capability('moodle/course:update', \context::instance_by_id($data->get('contextid')));
            } else {
                $canview = true;
            }
            if ($canview) {
                $content .= $data->display();
            }
        }
        $content .= \html_writer::end_tag('div');
        return $content;
    }

    /**
     * Context that should be used for new categories created by this handler
     *
     * @return \context the context for configuration
     */
    public function get_configuration_context(): \context {
        return \context_system::instance();
    }

    /**
     * URL for configuration of the fields on this handler.
     *
     * @return \moodle_url The URL to configure custom fields for this component
     */
    public function get_configuration_url(): \moodle_url {
        return new \moodle_url('/course/customfield.php');
    }

    /**
     * Returns the context for the data associated with the given recordid.
     *
     * @param int $recordid id of the record to get the context for
     * @return \context the context for the given record
     */
    public function get_data_context(int $recordid): \context {
        if ($recordid > 0) {
            return \context_course::instance($recordid);
        } else {
            return \context_system::instance();
        }
    }

    /**
     * Add fields for editing a text field.
     *
     * @param \MoodleQuickForm $mform
     * @throws \coding_exception
     */
    public static function add_to_field_config_form(\MoodleQuickForm $mform) {
        // If field is locked.
        $mform->addElement('selectyesno', 'configdata[locked]', get_string('isfieldlocked', 'core_customfield'));
        $mform->setType('configdata[locked]', PARAM_BOOL);

        // Field data visibility.
        $visibilityoptions = [get_string('notvisible', 'core_customfield'),
                              get_string('courseeditors', 'core_customfield'),
                              get_string('everyone', 'core_customfield')];
        $mform->addElement('select', 'configdata[visibility]', get_string('visibility', 'core_customfield'), $visibilityoptions);
        $mform->setType('configdata[visibility]', PARAM_INT);
    }
}