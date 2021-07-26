<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Quill Field Class
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
        'type' => 'TEXT'
    );
    
    /**
     * Constructor
     *
     */
    public function __construct()
    {
        parent::__construct();
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
            "field_len_{$id}" => array(
                'type' => 'INT',
                'constriant' => 10,
                'default' => 0
            ),
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
            "col_len_{$id}" => array(
                'type' => 'INT',
                'constriant' => 10,
                'default' => 0
            ),
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
    
    
    /**
     * Display Field
     * 
     * 
     * {@inheritDoc}
     * @see EE_Fieldtype::display_field()
     */
    public function display_field($data)
    {
        return form_input(array(
            'name'  => $this->field_name,
            'id'    => $this->field_id,
            'value' => $data
        ));
    }

}
// END Quill_ft class

/* End of file ft.quill.php */
/* Location: ./system/user/addons/quill/ft.quill.php */