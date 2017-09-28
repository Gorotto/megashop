<?
/**
*   Интерфейс к значению поля NamiArrayDbFieldValue, который показывается внешнему миру.
*   Реализует интерфейсы
*       ArrayAccess     http://www.php.net/manual/en/class.arrayaccess.php
*       Iterator        http://www.php.net/manual/en/class.iterator.php
*/
class NamiArrayDbFieldFrontend implements ArrayAccess, Iterator {
    protected $value;       // ссылка на значение поля, NamiDbFieldValue
    protected $position;    // текущая позиция итератора
    
    /**
    *   Конструктор
    */
    function __construct($value) {
        $this->value = $value;
        $this->position = 0;
    }
    
    /**
    *   Получение отсутствующих свойств, типа геттер.
    */
    function __get($name) {
        switch ($name) {
        case 'length':
            return $this->value->getLength();
        }
        throw new NamiException("Undefined property '$name'");
    }
    
    /**
    *   ArrayAccess::offsetSet
    */
    function offsetSet($offset, $value) {
        return $this->value->offsetSet($offset, $value);
    }

    /**
    *   ArrayAccess::offsetExists
    */
    function offsetExists($offset) {
        return $this->value->offsetExists($offset);
    }

    /**
    *   ArrayAccess::offsetUnset
    */
    function offsetUnset($offset) {
        return $this->value->offsetUnset($offset);
    }    

    /**
    *   ArrayAccess::offsetGet
    */
    function offsetGet($offset) {
        return $this->value->offsetGet($offset);
    }

    /**
    *   Iterator::rewind
    */
    function rewind() {
        $this->position = 0;
    }

    /**
    *   Iterator::current
    */
    function current() {
        return $this->value->offsetGet($this->position);
    }

    /**
    *   Iterator::key
    */
    function key() {
        return $this->position;
    }

    /**
    *   Iterator::next
    */
    function next() {
        $this->position++;
    }

    /**
    *   Iterator::valid
    */
    function valid() {
        return $this->value->offsetExists($this->position);
    }
}