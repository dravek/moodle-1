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
 * The abstract custom fields handler
 *
 * @package   core_customfield
 * @copyright 2018 David Matamoros <davidmc@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_customfield;

use stdClass;

// TODO revise function names, they are difficult to understand now.
// This handler provides callbacks for field configuration form and also allows to add the fields to the entity editing form
// It should be clear from the functions names what they do.
// load_data() loads the multiple fields values from an entity, the function name and arguments are very confusing because we use the
// word 'data' for the data related to individual field and even have class with this name

defined('MOODLE_INTERNAL') || die;

/**
 * The abstract custom fields handler
 *
 * @package core_customfield
 */
abstract class handler {

    /**
     * The component this handler handles
     *
     * @var string $component
     */
    private $component;

    /**
     * The area within the component
     *
     * @var string $area
     */
    private $area;

    /**
     * The id of the item within the area and component

     * @var int $itemid
     */
    private $itemid;

    /**
     * Handler constructor.
     *
     * This constructor is protected. To initiate a class use an appropriate static method:
     * - instance
     * - get_handler
     * - get_handler_for_field
     * - get_handler_for_category
     *
     * @param int $itemid
     * @throws \coding_exception
     */
    protected final function __construct(int $itemid = 0) {
        if (!preg_match('|^(\w+_[\w_]+)\\\\customfield\\\\([\w_]+)_handler$|', static::class, $matches)) {
            throw new \coding_exception('Handler class name must have format: <PLUGIN>\\customfield\\<AREA>_handler');
        }
        $this->component = $matches[1];
        $this->area = $matches[2];
        $this->itemid = $itemid;
    }

    /**
     * Returns an instance of the handler
     *
     * Some areas may choose to use singleton/caching here
     *
     * @param int $itemid
     * @return handler
     */
    public static function instance(int $itemid = 0) : handler {
        return new static($itemid);
    }

    /**
     * Returns an instance of handler by it's class name
     *
     * @param string $component
     * @param string $area
     * @param int $itemid
     * @return handler
     * @throws \moodle_exception
     */
    public static function get_handler(string $component, string $area, int $itemid = 0) : handler {
        $classname = $component . '\\customfield\\' . $area . '_handler';
        if (class_exists($classname) && is_subclass_of($classname, self::class)) {
            return $classname::instance($itemid);
        }
        $a = ['component' => s($component), 'area' => s($area)];
        throw new \moodle_exception('unknownhandler', 'core_customfield', (object)$a);
    }

    /**
     * Return handler for a given field
     *
     * @param field $field
     * @return handler
     * @throws \moodle_exception
     */
    public static function get_handler_for_field(field $field) : handler {
        $category = new category($field->get('categoryid'));
        return self::get_handler_for_category($category);
    }

    /**
     * Return the handler for a given category
     *
     * @param category $category
     * @return handler
     * @throws \moodle_exception
     */
    public static function get_handler_for_category(category $category) : handler {
        return self::get_handler($category->get('component'), $category->get('area'), $category->get('itemid'));
    }

    /**
     * @return string
     */
    public function get_component() : string {
        return $this->component;
    }

    /**
     * @return string
     */
    public function get_area() : string {
        return $this->area;
    }

    /**
     * Context that should be used for new categories created by this handler
     *
     * @return \context
     */
    abstract public function get_configuration_context() : \context;

    /**
     * URL for configuration of the fields on this handler.
     *
     * @return \moodle_url
     */
    abstract public function get_configuration_url() : \moodle_url;

    /**
     * Context that should be used for data stored for the given record
     *
     * @param int $recordid
     * @return \context
     */
    abstract public function get_data_context(int $recordid) : \context;

    /**
     * @return int|null
     */
    public function get_itemid() : int {
        return $this->itemid;
    }

    /**
     * @return bool
     */
    public function uses_itemid(): bool {
        return false;
    }

    /**
     * @return bool
     */
    public function uses_categories(): bool {
        return true;
    }

    /**
     * The form to create or edit a field
     *
     * @param field $field
     * @return field_config_form
     * @throws \moodle_exception
     */
    public function get_field_config_form(field $field): field_config_form {
         $form = new field_config_form(null, ['handler' => $this, 'field' => $field]);
         $form->set_data($this->prepare_field_for_form($field));
         return $form;
    }

    /**
     * @param category $category
     * @param string $type
     * @return field
     * @throws \coding_exception
     */
    public function new_field(category $category, string $type) : field {
        $field = field::create_from_type($type);
        $field->set('categoryid', $category->get('id'));
        return $field;
    }

    /**
     * Generates a name for the new category
     */
    protected function generate_category_name($suffix = 0) : string {
        $basename = get_string('otherfields', 'core_customfield');
        return $basename . ($suffix ? (' ' . $suffix) : '');
    }

    /**
     * @return category
     */
    public function new_category() : category {
        $categorydata = new stdClass();
        $categorydata->component = $this->get_component();
        $categorydata->area = $this->get_area();
        $categorydata->itemid = $this->get_itemid();
        $categorydata->contextid = $this->get_configuration_context()->id;

        $category = new category(0, $categorydata);

        $suffix = 0;
        while (true) {
            try {
                $category->set('name', $this->generate_category_name($suffix));
                return $category;
            } catch (\moodle_exception $exception) {

            }
            $suffix++;
        }
    }

    /**
     * The current user can configure custom fields on this component.
     *
     * @return bool
     */
    abstract public function can_configure(): bool;

    /**
     * The current user can edit custom fields on the given record on this component.
     *
     * @param null $recordid
     * @return bool
     */
    abstract public function can_edit($recordid = null): bool;

    /**
     * The given field is supported on by this handler
     *
     * @param field $field
     * @return bool
     */
    public function is_field_supported(\core_customfield\field $field): bool {
        // TODO: Placeholder for now to allow in the future components to decide that they don't want to support some field types.
        return true;
    }

    /**
     * Returns array of categories, each of them contains a list of fields definitions.
     *
     * @return category[]
     */
    public function get_fields_definitions() : array {
        // TODO cache result here.
        $fields = api::get_fields_definitions(
                $this->get_component(),
                $this->get_area(),
                $this->get_itemid()
        );
        if (!$fields && !$this->uses_categories()) {
            $this->new_category()->save();
            $fields = api::get_fields_definitions($this->get_component(), $this->get_area(), $this->get_itemid());
        }
        return $fields;
    }

    /**
     * List of fields with their data
     *
     * @param int $recordid
     * @return data[]
     */
    public function get_fields_with_data(int $recordid) : array {
        // TODO call get_fields_definitions() first, filter by the fields visible to the current user
        // TODO only then request data only for these fields
        return api::get_fields_with_data($this->get_component(), $this->get_area(), $this->get_itemid(),
            $this->get_data_context($recordid), $recordid);
    }

    /**
     * List of fields with their data (only fields with data)
     *
     * @param int $recordid
     * @return data[] - TODO this is not correct
     */
    public function get_fields_with_data_for_backup(int $recordid) : array {
        // TODO call get_fields_definitions() first, get list of available fields
        // TODO then api::get_fields_with_data() and create the array in the desired format
        // TODO this function looks very similar to fields_array
        return api::get_fields_with_data_for_backup($this->get_component(), $this->get_area(), $this->get_itemid(),
            $this->get_data_context($recordid), $recordid);
    }

    /**
     * Custom fields definition after data was submitted on data form
     *
     * @param \MoodleQuickForm $mform
     * @param int $recordid
     * @throws \moodle_exception
     */
    public function definition_after_data(\MoodleQuickForm $mform, int $recordid) {
        $fields = $this->get_fields_with_data($recordid);

        foreach ($fields as $formfield) {
            $formfield->edit_after_data($mform);
        }
    }

    /**
     * Add the field to the $data received
     *
     * @param $data
     * @throws \moodle_exception
     */
    public function load_data($data) {
        if (!isset($data->id)) {
            $data->id = 0;
        }
        $fields = $this->get_fields_with_data($data->id);

        foreach ($fields as $formfield) {
            $formfield->edit_load_data($data);
        }
    }

    /**
     * Saves the given data for custom fields
     *
     * @param $data
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function save_customfield_data($data) {
        $fields = $this->get_fields_with_data($data->id);
        foreach ($fields as $formfield) {
            $formfield->edit_save_data($data);
        }
    }

    /**
     * Adds custom fields to edit forms.
     *
     * @param \MoodleQuickForm $mform
     * @param $record
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function add_custom_fields(\MoodleQuickForm $mform, $record) {

        if (isset($record->id)) {
            $recordid = $record->id;
        } else {
            $recordid = 0;
        }

        $fieldswithdata = $this->get_fields_with_data($recordid);
        $categories = [];
        foreach ($fieldswithdata as $data) {
            $categories[$data->get_field()->get('categoryid')][] = $data;
        }
        foreach ($categories as $categoryid => $fields) {
            // Check first if *any* fields will be displayed.
            $fieldstodisplay = [];

            foreach ($fields as $formfield) {
                if ($formfield->is_editable()) {
                    $fieldstodisplay[] = $formfield;
                }
            }

            if (empty($fieldstodisplay)) {
                continue;
            }

            // Display the header and the fields.
            $formfield = reset($fieldstodisplay);
            $mform->addElement('header', 'category_' . $categoryid, format_string($formfield->get_field()->get_category()->get('name')));
            foreach ($fieldstodisplay as $formfield) {
                $formfield->edit_field_add($mform);
                // TODO: looks like it is being done here and also on data class on callbacks?
                if ($formfield->get_field()->get('required')) {
                    $mform->addRule($formfield->inputname(), get_string('fieldrequired', 'core_customfield'), 'required', null, 'client');
                }
                if (!$this->can_edit()) {
                    $mform->hardFreeze($formfield->inputname());
                }
            }
        }
    }

    /**
     * @return array
     */
    public function field_types() :array {
        return api::field_types();
    }

    /**
     * Options for processing embedded files in the field description.
     *
     * Handlers may want to extend it to disable files support and/or specify 'noclean'=>true
     * Context is not necessary here
     *
     * @return array
     */
    public function get_description_text_options() : array {
        return [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
        ];
    }

    /**
     * Save the field configuration with the data from the form
     *
     * @param field $field
     * @param stdClass $data data from the form
     * @throws \moodle_exception
     */
    public function save_field(field $field, stdClass $data) {
        try {
            api::save_field($field, $data, $this->get_description_text_options());
            \core\notification::success(get_string('fieldsaved', 'core_customfield'));
        } catch (\moodle_exception $exception) {
            \core\notification::error(get_string('fieldsavefailed', 'core_customfield'));
        }
    }

    /**
     * Prepare the field data to set in the configuration form
     *
     * @param field $field
     * @return stdClass
     * @throws \moodle_exception
     */
    protected function prepare_field_for_form(field $field) : stdClass {
        $data = $field->to_record();
        $context = $this->get_configuration_context();
        $textoptions = ['context' => $context] + $this->get_description_text_options();
        // TODO use $field->get_field_configdata()
        $data->configdata = json_decode($data->configdata, true);
        if ($data->id) {
            file_prepare_standard_editor($data, 'description', $textoptions, $context, 'core_customfield',
                'description', $data->id);
        }

        return $data;
    }

    /**
     * Prepare category data to set in the configuration form
     *
     * @param category $category
     * @return stdClass
     */
    protected function prepare_category_for_form(category $category) : stdClass {
        return $category->to_record();
    }

    /**
     * Save the category configuration using the data from the form
     *
     * @param category $category
     * @param stdClass $data data from the form
     * @throws \moodle_exception
     */
    public function save_category(category $category, stdClass $data) {
        try {
            api::save_category($category, $data);
            \core\notification::success(get_string('categorysaved', 'core_customfield'));
        } catch (\moodle_exception $exception) {
            \core\notification::error(get_string('categorysavefailed', 'core_customfield'));
        }
    }

    /**
     * Returns get_fields_with_data as an array for webservices use.
     *
     * @param int $recordid id of the record to get fields for
     * @return array custom fields with it's values for the specified recordid
     */
    public function fields_array($recordid) : array {
        $datafields = $this->get_fields_with_data($recordid);
        $fieldsarray = array();
        foreach ($datafields as $data) {
            $field = $data->get_field();
            $fieldsarray[] = ['type' => $field->get('type'), 'value' => $data->get_formvalue(),
                              'name' => $field->get('name'), 'shortname' => $field->get('shortname')];
        }
        return $fieldsarray;
    }

    /**
     * Creates or updates custom field data for a recordid from backup data.
     *
     * @param int $recordid
     * @param array $data
     */
    public function restore_field_data_from_backup(int $recordid, array $data) {
        global $DB;
        // TODO use field::create_from_type() and data::load_data()
        if ($field = $DB->get_record('customfield_field', ['shortname' => $data['shortname']])) {
            $customfieldtype = "\\customfield_{$field->type}\\field";
            $customdatatype = "\\customfield_{$field->type}\\data";
            $field = new $customfieldtype($field->id, $field);

            $datarecord = $DB->get_record('customfield_data', array('recordid' => $recordid, 'fieldid' => $field->get('id')));
            if ($datarecord) {
                $dataobject = new $customdatatype($datarecord->id, $datarecord);
            } else {
                $dataobject = new $customdatatype(0);
            }
            $dataobject->set('recordid', $recordid);
            $dataobject->set('fieldid', $field->get('id'));
            $dataobject->set('contextid', $this->get_data_context($recordid)->id);
            $dataobject->set_rawvalue($data['value']);
            $dataobject->save();
        }
    }

    /**
     * Add fields for editing a text field.
     *
     * @param \MoodleQuickForm $mform
     * @throws \coding_exception
     */
    abstract static public function add_to_field_config_form(\MoodleQuickForm $mform);
}