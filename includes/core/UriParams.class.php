<?php
/**
    Объект параметров строки Uri
    
    UriParams::getInstance()->prices = array('0-1000', '1000-5000');
    UriParams::getInstance()->companies = array('Google', 'Yandex', 'Bing');
    
    После того, как объект наполнен пришедшими параментрами, мы можем вывести получившуюся строку uri
    (ее можно смело присовывать paginator'у):
    
    print UriParams::getInstance();
    // ?prices=0-1000;1000-5000&companies=Google;Yandex;Bing
    
    Или можем представить как бы выглядел uri, если бы в нем не было одного из параметров:
    
    print UriParams::getInstance()->imagineRemove('companies', 'Yandex');
    // ?prices=0-1000;1000-5000&companies=Google;Bing
    
    Или наборот, если бы новый параметр появился:
    
    print UriParams::getInstance()->imagineAppend('companies', 'Baidu');
    // ?prices=0-1000;1000-5000&companies=Google;Yandex;Bing;Baidu
*/
class UriParams {
    private static $instance = null;
    
    public static function getInstance() {
        if( ! self::$instance ) {
            self::$instance = new UriParams();
        }
        return self::$instance;
    }
    

    private $params = array();

    /**
        Установка значения параметра
        Можно добавлять как одиночные значения, так и массивы данных
    */
    function __set($name, $value) {
        $this->params[$name] = $value;
        return true;
    }
    
    /**
        Получение значения параметра
    */
    function __get($name) {
        if (array_key_exists($name, $this->params)) {
            return $this->params[$name];
        }
        return null;
    }
    
    /**
        Проверка на существание параметра (если указан только $name)
        или значения параметра (если указаны и $name, и $val)
        Если params->name - массив, то ищем $val среди элементов этого массива. 
    */
    public function exists($name, $val = null) {
        if(array_key_exists($name, $this->params) && $this->params[$name]) {
            if(! $val) {
                return true;
            } 
            if(is_array($this->params[$name])) {
                foreach($this->params[$name] as $i) {
                    if($i == $val) {
                        return true;
                    }
                }
            } else {
                if($this->params[$name] == $val) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
        Проверка на пустоту объекта
    */
    public function is_empty() {
        if($this->params) {
            return false;
        }
        return true;
    }
    
    /**
        Показывает, как бы выглядел uri, если в него добавить (или изменить) одно значение любого параметра.
        Этот метод не изменяют сам объект, он делают копию параметров, заменяет в ней какую-либо часть 
        и выводит получившийся массив в виде отформатированной строки. Им хорошо делать ссылки вида «Добавить фильтр».
    */
    public function imagineAppend($name, $value) {
        $params = $this->params;
        if(array_key_exists($name, $params)) {
            if(is_array($params[$name])) {
                if(! in_array($value, $params[$name])) {
                    // вот здесь лучше не в конец добавлять а все таки сортировать
                    $params[$name][] = $value;
                 }    
            } else {
                $params[$name] = $value;
            }
        } else {
            $params[$name] = $value;
        }
        return $this->makeString($params);
    }
    
    /**
        Показывает, как бы выглядел uri, если из него убрать одно значение любого параметра.
        Работает подобно imagineAppend.
    */
    public function imagineRemove($name, $value) {
        $params = $this->params;
        if(array_key_exists($name, $params)) {
            if(is_array($params[$name])) {
                foreach($params[$name] as $n => $i) {
                    if($i == $value) {
                        unset($params[$name][$n]);
                        if($params[$name]) {
                            $params[$name] = array_values($params[$name]);
                        } else {
                            unset($params[$name]);
                        }
                    }
                }   
            } else {
                unset($params[$name]);
            }
        } else {
            return false;
        }
        return $this->makeString($params);
    }
    
    /**
        Из переданных параметров собирает строку uri.
        Из такого: 
        
            $params = array(
                'prices' => array('0-1000', '1000-5000'), 
                'companies' => array('Google', 'Yandex', 'Bing'),
                'filter' => 1
            ));
            
        Делает такое:
            
            ?prices=0-1000;1000-5000&companies=Google;Yandex;Bing&filter=1
    */
    private function makeString($params) {
        $string = '';
        if(count($params) > 0) {            
            $string = '?';
            foreach($params as $key => $val) {
                if($string != '?') {
                    $string .= "&";
                }
                if(is_array($val)) {
                    $val_count = count($val);
                    $substring = '';
                    foreach($val as $n => $i) {
                        $substring .= "{$i}";
                        if($n != $val_count-1) {
                            $substring .= ";";
                        }
                    }
                    $string .= "{$key}={$substring}";
                } else {
                    $string .= "{$key}={$val}";
                }
            } 
        }
        return $string;   
    }
    
    /**
        Представление объекта в виде строки
        Используется makeString с текущим состоянием объекта.
    */
    public function __toString() {
        return $this->makeString($this->params);
    }
}
?>