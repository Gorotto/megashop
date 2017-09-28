<?php

class UriConfApplication {

    public function run(Page $page, $uri = '') {
        // Если у класса имеется свойство $uriconf - сделаем авто-ресолв, иначе - грузим view 'index'
        if (property_exists($this, 'uriconf')) {
            $resolved = $this->resolveUri(new UriConf($this->uriconf), $page, $uri);
            if ($resolved !== false) {
                return true;
            }
        }

        if (method_exists($this, 'default_action')) {
            return $this->default_action((object) array(), $page, 'default_action', $uri);
        }

        return false;
    }

    /**
     *   Автоматический ресолв UriConf'а и запуск соответствующего метода.
     *   $conf - экземпляр UriConf
     *   $uri  - адрес, которые требуется разресолвить
     *   Выполняет метод с именем $uriconf_result, если ресолв прошел.
     *   Выполняемому методу передается три аргумента - $uriconf->vars (объект матчей из адреса uriconf),
     *   $action (имя метода) и $uri.
     *   Возвращает false, если ничего не нашлось или результат работы метода.
     *   Если uriconf вернул метод, но он не найден в текущем объекте - выбрасывает исключение.
     */
    protected function resolveUri($uriconf, $page, $uri) {
        $action = $uriconf->resolve($uri);

        if ($action) {
            if (method_exists($this, $action)) {
                return $this->$action($uriconf->vars, $page, $action, $uri);
            } else {
                throw new Exception(sprintf("Action %s::%s not found", get_class($this), $action));
            }
        }

        return false;
    }

    public function default_action($vars, $page, $action, $uri) {
        return false;
    }

}
