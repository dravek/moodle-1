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
 * @package   customfield_textarea
 * @copyright 2018 Daniel Neis Araujo <danielneis@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace customfield_textarea;

use core\persistent;

defined('MOODLE_INTERNAL') || die;

/**
 * Class data
 *
 * @package customfield_select
 */
class data extends \core_customfield\data {
    /**
     * Add fields for editing a textarea field.
     *
     * @param \moodleform $mform
     * @throws \coding_exception
     */
    public function edit_field_add(\moodleform $mform) {
        $mform->addElement('editor', $this->inputname(), format_string($this->get_field()->get('name')));
        $mform->setType($this->inputname(), PARAM_RAW);
    }

    /**
     * @return string
     * @throws \coding_exception
     */
    public function display() {
        return \html_writer::start_tag('div') .
               \html_writer::tag('span', format_string($this->get_field()->get('name')), ['class' => 'customfieldname']) .
               \html_writer::tag('span', format_text($this->get_formvalue()), ['class' => 'customfieldvalue']) .
               \html_writer::end_tag('div');
    }

    /**
     * @return string
     */
    public function datafield() :string {
        return 'value';
    }

    /**
     * Process incoming data for the field.
     *
     * @param \stdClass $data
     * @param \stdClass $datarecord
     * @return mixed
     */
    public function edit_save_data_preprocess(string $data, \stdClass $datarecord) {
        if (is_array($data)) {
            $datarecord->dataformat = $data['format'];
            $data                   = $data['text'];
        }
        return $data;
    }

    /**
     * Load data for this custom field, ready for editing.
     *
     * @param $data
     * @throws \coding_exception
     */
    public function edit_load_data(\stdClass $data) {
        if ($this->get('data') !== null) {
            $this->set('dataformat', 1);
            $this->set('data', clean_text($this->get('data'), $this->get('dataformat')));
            $data->{$this->inputname()} = array('text' => $this->get('data'), 'format' => $this->get('dataformat'));
        }
    }
}