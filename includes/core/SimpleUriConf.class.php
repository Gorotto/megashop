<?php

/**
*   Упрощенный uriconf. В отличие от родителя использует упрощенный синтаксис описания действия:
*   array('/news/:year/:month/:day/', 'arhive_day')
*   Определено действие archive_day с тремя переменными - year, month и day.
*   Значения переменных могут содержать любые символы кроме /, никакие другие проверки не выполняются.
*   Имя переменной может содержать буквы a-z в нижнем регистре, цифры и знаки подчеркивания.
*   Можно выделять переменную двумя двоеточиями, например /news/:id:doc/ совпадет с /news/15doc/
*/
class SimpleUriConf extends UriConf {

    /**
    *   В отличие от UriConf принимает только массив описаний действий.
    */
    function __construct(array $actions) {
        // Проверим, что нам передали массив массивов
        $actions_are_arrays = count($actions) && is_array($actions[0]);
        if (! $actions_are_arrays) {
            throw new Exception("Incorrect actions definition");
        }
        
        // Пройдем по действиям и превратим упрощенные адреса в регулярные выражения
        foreach ($actions as $i => $action) {
            $uri = $action[0];

            if ($uri === '/') {
                $uri = '/?';
            } else {
                $uri = preg_replace('~:([a-z0-9_]+):?~', '(?P<$1>[^/]+)', $uri);
                if ($uri[mb_strlen($uri) - 1] != '/') {
                    $uri .= '/?';    
                }
            }

            $actions[$i][0] = "~^{$uri}\$~";
        }
        
        return parent::__construct($actions);
    }
}