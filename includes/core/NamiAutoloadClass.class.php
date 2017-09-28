<?php

// Обработчик автолоада
class NamiAutoloadClass {

    protected static function file_exists($filename) {
        foreach (explode(PATH_SEPARATOR, get_include_path()) as $path) {
            if ($path && $path[strlen($path) - 1] != '/') {
                $path .= '/';
            }
            if (file_exists("{$path}{$filename}")) {
                return true;
            }
        }
        return false;
    }

    public static function autoload($classname) {
        if (self::file_exists($classname . '.class.php')) {
            include_once( $classname . '.class.php' );
        }

        #  Удалось ли загрузить класс?
        if (class_exists($classname) || interface_exists($classname)) {
            # Если у класса есть метод init -  вызовем его для статической инициализации
            if (method_exists($classname, 'init')) {
                call_user_func(array($classname, 'init'));
            }

            # Если у класса есть метод destruct - запланируем его вызов при завершении скрипта
            if (method_exists($classname, 'destruct')) {
                register_shutdown_function(array($classname, 'destruct'));
            }

            # Все в порядке
            return;
        }
    }

}
