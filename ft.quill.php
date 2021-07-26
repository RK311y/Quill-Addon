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