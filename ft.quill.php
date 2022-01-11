<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Quill Fieldtype
 */
class Quill_ft extends EE_Fieldtype
{
    public $info = array();

    public $has_array_data = true;

    private $default_settings = array(
        'theme' => 'snow',
        'placeholder' => '',
        'field_wide' => true
    );

    private $default_column = array(
        'type' => 'MEDIUMTEXT'
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        ee()->load->helper('form');

        $addon = ee('Addon')->get('quill');

        $this->info = array(
            'name' => $addon->getName(),
            'version' => $addon->getVersion()
        );
    }

    /**
     * Accepts Content Type
     *
     * {@inheritdoc}
     * @see EE_Fieldtype::accepts_content_type()
     */
    public function accepts_content_type($name)
    {
        // return ($name == 'channel' || $name == 'grid' || $name == 'fluid_field');
        return ($name == 'channel' || $name == 'grid');
    }

    public function display_field($data)
    {
        $value = array();
        
        if(! empty($data)) {
            $value['text'] = $data;
        }
        
        if( (! is_null($this->content_id) && ! isset($this->settings['grid_field_id'])) || (isset($this->settings['grid_row_id']) && isset($this->settings['grid_row_id']))) {

            $select = isset($this->settings['grid_field_id']) ? "col_ops_{$this->settings['col_id']}" : "field_ops_{$this->field_id}";
            $where = isset($this->settings['grid_field_id']) ? ['row_id' => $this->settings['grid_row_id']] : ['entry_id' => $this->content_id];
            $table = isset($this->settings['grid_field_id']) ? "channel_grid_field_{$this->settings['grid_field_id']}" : "channel_data_field_{$this->field_id}";

            $query = ee('db');
            $query->select($select);
            $query->where($where);
            $query->limit(1);
            $result = $query->get($table);
            
            if($result->num_rows() > 0) {
                $ops = $result->row()->{$select};
                if(! is_null($ops)) {
                    $value['ops'] = $ops;
                }
            }
        }

        $value = (! empty($value)) ? json_encode($value) : null;

        if(REQ == 'CP') {
            $this->_cp_resources();
        }

        return form_input(array(
            'name'  => $this->field_name,
            'value' => $value,
            'class' => 'quill-input'
        ));
    }

    /**
     * Save
     *
     * Preps the data for saving
     *
     * @param $data string
     *            Current field data, blank for new entries
     * @return string Data to save to the database
     *        
     * {@inheritdoc}
     * @see EE_Fieldtype::save()
     */
    public function save($data)
    {
        if(empty($data)) {
            return null;
        }
        
        $data = json_decode($data);
        
        if(empty($data)) {
            return null;
        }

        $cache_name = "quill_field_{$this->field_id}";
        if (isset($this->settings['grid_field_id'])) {
            $cache_name .= "_grid_" . $this->settings['grid_field_id'];
            $cache_name .= "_col_" . $this->settings['col_id'];
            $cache_name .= "_row_" . $this->settings['grid_row_name'];
        }
        
        ee()->session->set_cache(__CLASS__, $cache_name, $data);
        
        return (isset($data->text) && $data->text !== "\n") ? $data->text : null;
    }
    
    /**
     * Post Save
     *
     * Handles any custom logic after an entry is saved.
     * Called after an entry is added or updated. Available data is identical to save. This is a good method to implement if you need the content ID of the fieldtype’s newly-saved parent content type.
     *
     * @param $data string
     *            Current field data, blank for new entries
     * @return void
     *
     * {@inheritdoc}
     * @see EE_Fieldtype::post_save()
     */
    public function post_save($data)
    {
        // Prevent saving if save() was never called, happens in Channel Form
        // if the field is missing from the form
        $cache_name = "quill_field_{$this->field_id}";
        if (isset($this->settings['grid_field_id'])) {
            $cache_name .= "_grid_" . $this->settings['grid_field_id'];
            $cache_name .= "_col_" . $this->settings['col_id'];
            $cache_name .= "_row_" . $this->settings['grid_row_name'];
        }

        if (($data = ee()->session->cache(__CLASS__, $cache_name, null)) === null) {
            return;
        }
        
        $col_name = (isset($this->settings['grid_field_id'])) ? "col_ops_{$this->settings['col_id']}" : "field_ops_{$this->field_id}";
        $where = (isset($this->settings['grid_field_id'])) ? ['row_id' => $this->settings['grid_row_id']] : ['entry_id' => $this->content_id];
        $table = (isset($this->settings['grid_field_id'])) ? "channel_grid_field_{$this->settings['grid_field_id']}" : "channel_data_field_{$this->field_id}";

        $ops = (isset($data->ops)) ? $data->ops : null;
        $ops = (gettype($ops) == 'array') ? json_encode($ops) : $ops;

        $values = array(
            $col_name => $ops
        );

        $result = ee()->db->get_where($table, $where, 1);
        if ($result->num_rows() < 1) {
            ee()->db->insert($table, array_merge($where, $values));
            return;
        }

        $query = ee('db');
        $query->set($values);
        $query->where($where);
        $query->update($table);
    }

    /**
     * Validate
     *
     * Validates the field input
     *
     * @param $data array|string Current field data, blank for new entries
     * @return boolean|string TRUE if the field validates, an error message otherwise
     *        
     * {@inheritdoc}
     * @see EE_Fieldtype::validate()
     */
    public function validate($data)
    {
        return true;
    }

    public function replace_tag($data, $params = array(), $tagdata = false)
    {
        $text = $data;
        $ops = null;

        $settings = array_merge($this->default_settings, $this->settings);

        if( (! is_null($this->content_id) && ! isset($this->settings['grid_field_id'])) || (isset($this->settings['grid_row_id']) && isset($this->settings['grid_row_id']))) {
            $select = isset($this->settings['grid_field_id']) ? "col_ops_{$this->settings['col_id']}" : "field_ops_{$this->field_id}";
            $where = isset($this->settings['grid_field_id']) ? ['row_id' => $this->settings['grid_row_id']] : ['entry_id' => $this->content_id];
            $table = isset($this->settings['grid_field_id']) ? "channel_grid_field_{$this->settings['grid_field_id']}" : "channel_data_field_{$this->field_id}";
            $query = ee('db');
            $query->select($select);
            $query->where($where);
            $query->limit(1);
            $result = $query->get($table);
            if($result->num_rows() > 0) {
                $ops = $result->row()->{$select};
            }
        }

        $params = array_merge([
            'data' => 'text', // 'text' or 'ops',
            'encode_ops' => 'none', // 'none' or 'base64'
        ], $params);

        if ($params['encode_ops'] == 'base64') {
            $ops = base64_encode($ops);
        }

        if ($tagdata !== false) {

            $vars = [
                'text' => $text,
                'ops' => $ops,
                'ops:base64' => base64_encode($ops),
                'theme' => $settings['theme'],
                'placeholder' => $settings['placeholder'],
            ];

            return ee()->TMPL->parse_variables($tagdata, [$vars]);
        }

        $output = $text;

        if ($params['data'] == 'ops') {
            $output = $ops;
        }

        // $output = ee('Format')->make('Text', $output)->attributeSafe();
        return $output;
    }
    
    /**
     * Installation
     *
     * By returning an array from within install we can provide a default set of global settings.
     *
     * {@inheritdoc}
     * @see EE_Fieldtype::install()
     */
    public function install()
    {
        return $this->default_settings;
    }

    /**
     * Settings Modify Colum
     *
     * Allows the specification of an array of fields to be added, modified or dropped when fields are created, edited or deleted.
     *
     * {@inheritdoc}
     * @see EE_Fieldtype::settings_modify_column()
     */
    public function settings_modify_column($data)
    {
        $id = $data['field_id'];

        return array(
            "field_id_{$id}" => $this->default_column,
            "field_ops_{$id}" => $this->default_column
        );
    }

    /**
     * Grid Settings Modify Colum
     *
     * {@inheritdoc}
     * @see EE_Fieldtype::grid_settings_modify_column()
     */
    public function grid_settings_modify_column($data)
    {
        $id = $data['col_id'];

        return array(
            "col_id_{$id}" => $this->default_column,
            "col_ops_{$id}" => $this->default_column
        );
    }

    function validate_settings($data)
    {
        $validator = ee('Validation')->make(array(
            'theme' => 'required|enum[snow,bubble]',
            'placeholder' => 'maxLength[50]|noHtml|xss'
        ));

        return $validator->validate($data);
    }

    /**
     * Save (Individual) Settings
     *
     * {@inheritdoc}
     * @see EE_Fieldtype::save_settings()
     */
    public function save_settings($data)
    {
        return array(
            'theme' => isset($data['theme']) ? $data['theme'] : $this->default_settings['theme'],
            'placeholder' => isset($data['placeholder']) ? $data['placeholder'] : $this->default_settings['placeholder'],
            'field_wide' => true
        );
    }

    /**
     * Display (Individual) Settings
     *
     * {@inheritdoc}
     * @see EE_Fieldtype::display_settings()
     */
    public function display_settings($data)
    {
        $values = array_merge($this->default_settings, $data);

        $settings = array(
            array(
                'title' => 'theme',
                'desc' => 'theme_desc',
                'fields' => array(
                    'theme' => array(
                        'type' => 'dropdown',
                        'value' => $values['theme'],
                        'required' => true,
                        'choices' => array(
                            'snow' => 'Snow',
                            'bubble' => 'Bubble'
                        )
                    )
                )
            ),
            array(
                'title' => 'placeholder',
                'desc' => 'placeholder_desc',
                'fields' => array(
                    'placeholder' => array(
                        'type' => 'text',
                        'value' => $values['placeholder'],
                        'placeholder' => 'Compose an epic...'
                    )
                )
            )
        );

        if ($this->content_type() == 'grid') {
            return array(
                'field_options' => $settings
            );
        }

        return array(
            'field_options_quill' => array(
                'label' => 'field_options',
                'group' => 'quill',
                'settings' => $settings
            )
        );
    }

    private function _cp_resources()
    {
        if (! ee()->session->cache(__CLASS__, 'cp_resources_js', false)) {
            ee()->cp->load_package_js('quill.min');
            ee()->cp->load_package_js('main');
            ee()->session->set_cache(__CLASS__, 'cp_resources_js', true);
        }

        if (isset($this->settings['grid_field_id']) && ! ee()->session->cache(__CLASS__, 'cp_resources_js_grid', false)) {
            ee()->cp->load_package_js('grid');
            ee()->session->set_cache(__CLASS__, 'cp_resources_js_grid', true);
        }
        
        if (! ee()->session->cache(__CLASS__, 'cp_resources_css_main', false)) {
            ee()->cp->load_package_css('main');
            ee()->session->set_cache(__CLASS__, 'cp_resources_css_main', true);
        }
        
        if (isset($this->settings['theme']) && $this->settings['theme'] == 'bubble' && !ee()->session->cache(__CLASS__, 'cp_resources_css_bubble', false)) {
            ee()->cp->load_package_css('quill.bubble');
            ee()->session->set_cache(__CLASS__, 'cp_resources_css_bubble', true);
        }
        
        if (isset($this->settings['theme']) && $this->settings['theme'] == 'snow' && !ee()->session->cache(__CLASS__, 'cp_resources_css_snow', false)) {
            ee()->cp->load_package_css('quill.snow');
            ee()->session->set_cache(__CLASS__, 'cp_resources_css_snow', true);
        }
    }

}
// END Quill_ft class

/* End of file ft.quill.php */
/* Location: ./system/user/addons/quill/ft.quill.php */