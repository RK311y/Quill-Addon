<?php

$field_vars = [
    'name'  => $name,
    // 'id'    => $id,
    'type' => 'hidden',
    'value' => $value,
    'class' => $class,
    'data-quill-settings' => base64_encode(json_encode($quill_settings, true)),
];

if(isset($disabled) && $disabled) {
    $field_vars['disabled'] = 'disabled';
}

$field = form_input($field_vars);
?>
<div class="quill-field">
	<?=$field?>
</div>
