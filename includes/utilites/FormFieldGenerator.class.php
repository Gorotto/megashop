<?
/**
 * генерирование полей для форм
 **/
class FormFieldGenerator {
    private $fields = array();

    private $nami_fields_types = array(
            'NamiTextDbField'       => 'textarea',
            'NamiCharDbField'       => 'text',
            'NamiIntegerDbField'    => 'text',
            'NamiFloatDbField'      => 'text',
            'NamiEnumDbField'       => 'text',
            'NamiBoolDbField'       => 'checkbox',
            'NamiDatetimeDbField'   => 'text',
            'NamiImageDbField'      => 'text',
            'NamiFileDbField'       => 'text',
        );


    function __construct($fields = array()) {}


    function add_field($type = NULL, array $attr, $label = '', $value = '') {
        if (is_null($type)) {
            throw new Exception('need $type argument');
        }
        if (!array_key_exists('name', $attr)) {
            throw new Exception('field need "name" attribute');
        }

        if ($this->fields) {
            if (! isset($this->fields[$attr['name']]) ) {
                $this->fields[$attr['name']] = array(
                        'attr' => $attr,
                        'type' => $type,
                        'label' => $label,
                        'value' => $value,
                    );
            }

        } else {
            $this->fields[$attr['name']] = array(
                    'attr' => $attr,
                    'type' => $type,
                    'label' => $label,
                    'value' => $value,
                );
        }

        return $this;
    }

    function add_class($field, $class_name) {
        if (!$field || !$class_name) {
            throw  new Exception('invalid arguments in function add_class');
        }

        if ($this->fields[$field]) {
            $added_classes = explode(' ', $class_name);

            if (isset($this->fields[$field]['attr']['class'])) {
                $exists_classes = explode(' ', $this->fields[$field]['attr']['class']);

                foreach ($exists_classes as $exist_class) {
                    foreach ($added_classes as $added_class) {
                        if ($added_class != $exist_class) {
                            $this->fields[$field]['attr']['class'] .= ' ' . $added_class;
                        }
                    }
                }

            } else {
                $this->fields[$field]['attr']['class'] = $class_name;
            }
        }

        return $this;
    }

    function remove_class($field, $class_name) {
        if (!$field || !$class_name) {
            throw  new Exception('invalid arguments in function remove_class');
        }

        if (isset($this->fields[$field]['attr']['class'])) {
            $classes = explode(' ', $this->fields[$field]['attr']['class']);
            $key_class = array_search($class_name, $classes);
            if ($key_class === 0 || $key_class) {
                unset($classes[$key_class]);
            }

            $this->fields[$field]['attr']['class'] = implode(' ', $classes);
        }

        return $this;
    }


    function set_label($field, $label = '') {
        if (!$field || !$label) {
            throw  new Exception('invalid arguments in function remove_class');
        }

        $this->fields[$field]['label'] = $label;
    }


    function render() {
        foreach ($this->fields as $field) {
            if (!isset($field['type'])) {
                continue;
            }

            echo new View('_forms/generator/' . $field['type'], array('type' => $field['type'], 'attr' => $field['attr'], 'label' => $field['label'], 'value' => $field['value']));
        }
    }
    
    function get_fields() {
        return $this->fields;
    }


    function add_fields() {}
    function html_after_field() {}
    function add_attr() {}
}