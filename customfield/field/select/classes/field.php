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
 * @package   customfield_select
 * @copyright 2018 David Matamoros <davidmc@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace customfield_select;

defined('MOODLE_INTERNAL') || die;

/**
 * Class field
 *
 * @package customfield_select
 */
class field extends \core_customfield\field {

    /**
     * Add fields for editing a text field.
     * @param moodleform $mform
     */
    public function add_field_to_config_form( \MoodleQuickForm $mform) {
        $mform->addElement('textarea', 'configdata[options]', 'Menu options (one per line)');

        $mform->addElement('text', 'configdata[defaultvalue]', get_string('defaultvalue', 'core_customfield'), 'size="50"');
        $mform->setType('configdata[defaultvalue]', PARAM_TEXT);
    }
}