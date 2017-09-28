<?php
/**
*   Класс интерфейса сайта - отвечает за блоки и цвета.
*/
class SiteInterface {
    protected static $colors = array(
        'gray'   => array('name' => 'gray',   'hex' => '#666666', 'style' => 'gray.css',   'title' => 'Серый'),
        'cyan'   => array('name' => 'cyan',   'hex' => '#3674a2', 'style' => 'cyan.css',   'title' => 'Синий'),
        'orange' => array('name' => 'orange', 'hex' => '#ff8c00', 'style' => 'orange.css', 'title' => 'Оранжевый'),
        'choco'  => array('name' => 'choco',  'hex' => '#523618', 'style' => 'choco.css',  'title' => 'Шоколадный'),
        'pink'   => array('name' => 'pink',   'hex' => '#cc394d', 'style' => 'pink.css',   'title' => 'Розовый'),
    );

    protected static $blocks;
    
    /**
    *   Получение списка цветов интерфейса
    */
    static function get_colors() {
        return self::$colors;
    }
    
    /**
    *   Получение пользовательского цвета
    */
    static function get_user_color() {
        if (array_key_exists('user_color', $_COOKIE)
        &&  array_key_exists($_COOKIE['user_color'], self::$colors)) {
            $key = $_COOKIE['user_color'];
        } else if (Config::get('interface.default_color')
        &&  array_key_exists(Config::get('interface.default_color'), self::$colors)) {
            $key = Config::get('interface.default_color');
        } else {
            $key = self::$colors[0]['name'];
        }
        return $key;
    } 
    
    /**
    *   Получение стиля выбранного цвета
    */
    static function get_user_color_style() {
        $key = self::get_user_color(); 
        return self::$colors[$key]['style'];
    }
    
    /**
    *   Получение hex'a выбранного цвета
    */
    static function get_user_color_hex() {
        $key = self::get_user_color(); 
        return self::$colors[$key]['hex'];
    }

    /**
    *   Получение списка доступных блоков сайта
    */
    static function get_blocks() {
        if (!self::$blocks) {
            self::$blocks = Meta::getAssocArray(IndexPageBlocks()
            ->filter(array('enabled' => true))
            ->order('column')
            ->order('position')
            ->values(array('id', 'title', 'column', 'name', 'params')), 'id');
        }
        
        return self::$blocks;
    }
    
    /**
    *   Получение списка блоков сайта, выбранных пользователем
    *   Возвращает ассоциативный массив ассоциативных массивов,
    *   ключи - номер колонки и идентификатор блока
    */
    static function get_user_blocks() {
        $session = SiteSession::getInstance();
        if (array_key_exists('user_blocks', $_COOKIE)) {
            return json_decode($_COOKIE['user_blocks'], true);
        } else {
            $blocks = array(array(), array(), array());
            foreach (self::get_blocks() as $id => $i) {
                $blocks[$i['column']][] = $id;
            }
            return $blocks;
        }
    }
}