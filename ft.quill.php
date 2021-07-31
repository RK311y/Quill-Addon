<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use ExpressionEngine\Service\Validation\Validator;
use ExpressionEngine\Library\Data\Collection;

/**
 * Quill Field Class
 * 
 * TEXT – 64KB (65,535 characters)
 * MEDIUMTEXT – 16MB (16,777,215 characters)
 * LONGTEXT – 4GB (4,294,967,295 characters)
 * 
 */
class Quill_ft extends EE_Fieldtype
{

    var $info = array(
        'name'      => 'Quill',
        'version'   => '1.0.0'
    );

    private $default_settings = array(
        'theme'  => 'snow',
        'placeholder' => '',
        'field_wide' => true
    );
    
    private $default_column = array(
        'type' => 'MEDIUMTEXT'
    );
    
    /**
     * Constructor
     *
     */
    public function __construct()
    {
        parent::__construct();
        
        ee()->load->helper('form');
    }
    
    /**
     * Accepts Content Type
     *
     *
     * {@inheritDoc}
     * @see EE_Fieldtype::accepts_content_type()
     */
    public function accepts_content_type($name)
    {
        return ($name == 'channel' || $name == 'grid' || $name == 'fluid_field');
    }
    
    /**
     * Installation
     *
     * By returning an array from within install we can provide a default set of global settings.
     *
     * {@inheritDoc}
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
     * {@inheritDoc}
     * @see EE_Fieldtype::settings_modify_column()
     */
    public function settings_modify_column($data)
    {
        $id = $data['field_id'];
        
        return array(
            "field_id_{$id}" => $this->default_column,
            "field_ops_{$id}" => $this->default_column,
        );
    }
    
    /**
     * Grid Settings Modify Colum
     * 
     * {@inheritDoc}
     * @see EE_Fieldtype::grid_settings_modify_column()
     */
    public function grid_settings_modify_column($data)
    {
        $id = $data['col_id'];
        
        return array(
            "col_id_{$id}" => $this->default_column,
            "col_ops_{$id}" => $this->default_column,
        );
    }
    
    /**
     * Display (Individual) Settings
     * 
     * {@inheritDoc}
     * @see EE_Fieldtype::display_settings()
     */
    public function display_settings($data)
    {
        $values = array(
            'theme' => isset($data['theme']) ? $data['theme'] : $this->default_settings['theme'],
            'placeholder' => isset($data['placeholder']) ? $data['placeholder'] : $this->default_settings['placeholder'],
        );
        
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
                        'type' => 'textarea',
                        'value' => $values['placeholder'],
                        'placeholder' => 'Compose an epic...'
                    )
                )
            ),
        );
        
        
        if ($this->content_type() == 'grid') {
            return array('field_options' => $settings);
        }
        
        return array('field_options_quill' => array(
            'label' => 'field_options',
            'group' => 'quill',
            'settings' => $settings
        ));
    }
    
    /**
     * Save (Individual) Settings
     * 
     * {@inheritDoc}
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
    
    private function colName($name)
    {
        if(isset($this->settings['grid_field_id']))  {
            return "col_" . $name . "_" . $this->settings['col_id'];
        }
        
        return "field_" . $name . "_" . $this->field_id;
    }
    
    private function tableName()
    {
        $table_name = "channel_data_field_{$this->field_id}";
        
        if(isset($this->settings['grid_field_id'])) {
            $table_name = "channel_grid_field_{$this->settings['grid_field_id']}";
        }
        
        return $table_name;
    }
    
    private function tableWhere()
    {
        $table_where = $table_where = array(
            'entry_id' => $this->content_id
        );
        
        // grid field
        if(isset($this->settings['grid_field_id'])) {
            if(isset($this->settings['grid_row_id'])) {
                $table_where = array(
                    'row_id' => $this->settings['grid_row_id']
                );
            } else {
                return null;
            }
            // fluid field
        } else if (isset($this->settings['fluid_field_data_id']) && $this->settings['fluid_field_data_id'] != 0 && !isset($this->settings['grid_field_id'])) {
            
            $query = ee()->db->get_where('fluid_field_data', array(
                'id' => $this->settings['fluid_field_data_id']
            ), 1);
            
            if($query->num_rows() > 0) {
                
                $query_result = $query->result_array();
                
                $table_where = array(
                    'id' => $query_result[0]['field_data_id'],
                    'entry_id' => 0
                );
            } else {
                return null;
            }
            // channel field
        } /* else if (!isset($this->settings['fluid_field_data_id']) && !isset($this->settings['grid_field_id']) && isset($this->content_id) && is_int($this->content_id)) {
            $table_where = array(
                'entry_id' => $this->content_id
            );
        } */
        
        return $table_where;
    }
    
    private function getData()
    {
        $table = $this->tableName();
        $where = $this->tableWhere();
        
        if(is_null($where)) {
            return null;
        }
        
        $query = ee()->db
        ->where($where)
        ->limit(1)
        ->get($table);
        
        if($query->num_rows() == 0) {
            return null;
        }
        
        $result = $query->result_array()[0];
        
        $data = [
            'text' => $result[$this->colName('id')],
            'ops' => $result[$this->colName('ops')]
        ];
        
        if(!is_null($data['ops']) && !empty($data['ops'])) {
            $data['ops'] = json_decode($data['ops'], true);
        }
        
        return $data;
    }
    
    
    /**
     * Display Field
     * 
     * 
     * {@inheritDoc}
     * @see EE_Fieldtype::display_field()
     */
    public function display_field($data)
    {
        $value = $this->getData();
        $disabled = (is_null($value) && (isset($this->settings['grid_field_id']) || isset($this->settings['fluid_field_data_id']))) ? true : false;
        
        if(is_null($value)) {
            $value = [
                'ops' => [
                    'insert' => '\n'
                ]
            ];
        }
        
        $value = base64_encode(json_encode($value));
        
        $vars = [
            'name'  => $this->field_name,
            'id'    => $this->field_id,
            'disabled' => $disabled,
            //'data' => $data,
            'value' => $value,
            'class' => 'quill-field-input',
            'quill_settings' => [
                'theme' => isset($this->settings['theme']) ? $this->settings['theme'] : $this->default_settings['theme'],
                'placeholder' => isset($this->settings['placeholder']) ? $this->settings['placeholder'] : $this->default_settings['placeholder'],
            ],
        ];
        
        $this->_resources();
        
        return ee('View')->make('quill:publish')->render($vars);
    }
    
    
    /**
     * The save_cache_name is used for storing data between calling save() and post_save().
     * This method provides a unique name for each piece of data.
     *
     * @return string
     */
    private function save_cache_name()
    {
        $name = "field_id_" . $this->field_id;
        
        if(isset($this->settings['fluid_field_data_id']) && !isset($this->settings['grid_field_id'])) {
            $name .= '_fl_ft_' . $this->settings['fluid_field_data_id'];
        }
        
        if(isset($this->settings['grid_field_id'])) {
            $name .= "_grid_" . $this->settings['grid_field_id'] . "_row_" . $this->settings['grid_row_name'];
        }
        
        return $name;
    }
    
    
    
    public function save($data)
    {
        if(empty($data)) {
            return null;
        }
        
        $data = json_decode(base64_decode($data), true);
        
        if(empty($data)) {
            return null;
        }
        
        $cache_name = $this->save_cache_name();
        
        ee()->session->set_cache(__CLASS__, $cache_name, $data);
        
        return isset($data['text']) ? $data['text'] : null;
        
        /* $fields = ee()->db->field_data($this->tableName());
        $fields_collection = new Collection($fields);
        $fields_indexed = $fields_collection->indexBy('name');
        
        $modify_fields = [];
        
        // text
        $col_name = $this->colName('id');
        $text_field = $fields_indexed[$col_name];
        $max_len = $text_field->max_length;
        $text_len = strlen($data['text']);
        
        if($text_len > $max_len) {
            $modify_fields[$col_name] = [
                'name' => $col_name,
                'type' => 'MEDIUMTEXT'
            ];
        }
        
        */
        
        /* ee()->load->dbforge();
        
        $fields = array(
            $this->colName('id') => array(
                'name' => $this->colName('id'),
                'type' => 'MEDIUMTEXT',
            ),
            $this->colName('ops') => array(
                'name' => $this->colName('ops'),
                'type' => 'MEDIUMTEXT',
            ),
        );
        
        ee()->dbforge->modify_column($this->tableName(), $fields);
        
        
        
        $fields = ee()->db->field_data($this->tableName());
        foreach ($fields as $field)
        {
            echo json_encode($field).BR;
        }
        die(); */
        
        
        
        /* 
        $out_data = ee('View')->make('quill:data')->render([
            'name'  => $this->field_name,
            'id'    => $this->field_id,
            'data' => json_encode($data),
            'cache_name' => $cache_name,
            // 'table_data' => json_encode($this->getData()),
            'content_id' => $this->content_id,
            'content_type' => $this->content_type,
            'is_new' => ($this->isNew() == true) ? 'y' : 'n',
            'is_grid_field' => (isset($this->settings['grid_field_id'])) ? 'y' : 'n',
            'is_fluid_field' => (isset($this->settings['fluid_field_data_id'])) ? 'y' : 'n',
            'settings' => $this->settings,
            'table' => $this->tableName(),
            'where' => $this->tableWhere(),
            'fluid_field_data_id' => (isset($this->settings['fluid_field_data_id'])) ? $this->settings['fluid_field_data_id'] : '-',
            'grid_field_id' => (isset($this->settings['grid_field_id'])) ? $this->settings['grid_field_id'] : '-',
            'col_id' => (isset($this->settings['col_id'])) ? $this->settings['col_id'] : '-',
            'col_name' => (isset($this->settings['col_name'])) ? $this->settings['col_name'] : '-',
            'col_required' => (isset($this->settings['col_required'])) ? $this->settings['col_required'] : '-',
            'grid_row_name' => (isset($this->settings['grid_row_name'])) ? $this->settings['grid_row_name'] : '-',
            'grid_row_id' => (isset($this->settings['grid_row_id'])) ? $this->settings['grid_row_id'] : '-',
        ]); 
        
        */
        
        
        
        
    }
    
    
    public function post_save($data)
    {
        
        $cache_name = $this->save_cache_name();
        
        $data = ee()->session->cache(__CLASS__, $cache_name, []);
        
        if(empty($data)) {
            return null;
        }
        
        $table = $this->tableName();
        $where = $this->tableWhere();
        
        if(is_null($where)) {
            show_error("NULL where");
        }
        
        //echo json_encode($where); die();
        
        $db_data = array(
            $this->colName('id') => $data['text'],
            $this->colName('ops') => json_encode($data['ops'], true),
        );
        
        $query = ee()->db->get_where($table, $where, 1);
        
        if($query->num_rows() === 0) {
            $db_data = array_merge($db_data, $where);
            ee()->db->insert($table, $db_data);
        } else {
            ee()->db->update($table, $db_data, $where);
        }
        
    }
    
    private function _resources()
    {
        ee()->cp->load_package_css('quill.bubble');
        ee()->cp->load_package_css('quill.snow');
        ee()->cp->load_package_css('cp');
        
        ee()->cp->load_package_js('quill.min');
        ee()->cp->load_package_js('main');
        
        ee()->javascript->set_global([
            'Quill.default_input_value_update_timeout' => 1500,
            'Quill.default_settings' => [
                'theme' => 'snow'
            ],
        ]);
    }
    
    
    
    public function validate($data)
    {
        $data = json_decode(base64_decode($data), true);
        
        if(empty($data)) {
            return "invalid data";
        }
        
        $rules = array(
            'text' => 'maxLength[500000]',
            //'ops' => 'required',
            //'ops_str' => 'maxLength[5000000]'
        );
        
        $result = ee('Validation')->make($rules)->validate($data);
        
        if ($result->isValid())
        {
            return true;
        }
        
        $errors = $result->getAllErrors();
        
        foreach($errors as $error_name => $error) {
            return $result->renderError($error_name);
        }
        
        return json_encode($result->getAllErrors());
    }
    
}
// END Quill_ft class

/* End of file ft.quill.php */
/* Location: ./system/user/addons/quill/ft.quill.php */