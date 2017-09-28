<?php
/**
*   Класс для упрощения работы с именованными параметрами.
*   - поддержка значений по умолчанию
*   - возврат null для отсутствующих значений
*   - поддержка обязательных параметров
*/
class NamedArguments {
    protected $defaults  = array();
    protected $arguments = array();

    function __construct($arguments, $required = '', $defaults = array()) {
        $this->arguments = $arguments;
        $this->defaults = $defaults;
        foreach (preg_split('/\s*,\s*/', $required) as $names) {
            foreach (preg_split('/\s*\|\s*/', $names) as $name) {
                $value = $this->__get($name);
                if (!is_null($value)) {
                    continue 2;
                }
            }
            throw new Exception("Required argument: $names");
        }
    }
    
    function __get($name) {
        if (array_key_exists($name, $this->arguments)) {
            return $this->arguments[$name];
        }

        if (array_key_exists($name, $this->defaults)) {
            return $this->defaults[$name];
        }
    
        return null;
    }
    
    function __set($name, $value) {
        $this->arguments[$name] = $value;
    }
}