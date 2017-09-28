<?php
/**
*   Класс с фильтрами для обработки HTML.
*   Удобен для применения в методе beginFilter шаблонов.
*   Вызов может выглядеть так: $this->beginFilter('HtmlFilter::nice_filter', 'arg1', 'arg2');
*/
class HtmlFilter {
    /**
    *   Преобразование нескольких последних слов текста
    *   в ссылку с переданным адресом.
    *   Если текст заканчивается на слова в кавычках, типа «один», "два" или 'три',
    *   то текст в кавычках становится ссылкой целиком,
    *   иначе выбирается $words последних слов.
    */
    static function end_link($text, $uri, $words = 3) {
        $words--;
        if (! $words) {
            throw new Exception('minwords must be 1 or greater.');
        }
        
        $result = preg_replace("~(?<=^|\s)(«[^»]+»|\"[^\"]+\"|'[^']+'|\S+(?:\s+\S+){0,$words})$~ui", "<a href=\"$uri\">$1</a>", $text);

        return $result;
    }
    
    /**
    *   Удаляет пробелы, табуляции и переносы строк между тегами.
    *   Не трогает пробелы между тегами и текстом, между словами.
    */
    static function spaceless($text) {
        return preg_replace('/(?<=>)\s+(?=<)/', '', $text);
    }
    
    /**
    *   Выдает защищенную ссылку на email
    *   $text - текст ссылки
    *   $email - адрес email
    *   $attr - ассочиативный массив атрибутов ссылки
    */
    static function protected_email_link($text, $email, $attribute_string = '') {
        $char_codes = array();
        $href = "mailto:{$email}";
        $href_length = strlen($href);
    
        for ($i = 0; $i < $href_length; $i++) {
            $char_codes[] = ord($href[$i]);
        }
        
        return sprintf('<a %s href="mailto:email@protect.ed" onmouseover="this.href=String.fromCharCode(%s);">%s</a>',
            $attribute_string,
            join(',', $char_codes),
            $text
        );
    }
    
    static function end_span($text, $words = 3) {
        $words--;
        if (! $words) {
            throw new Exception('minwords must be 1 or greater.');
        }
        
        $result = preg_replace("~(?<=^|\s)(«[^»]+»|\"[^\"]+\"|'[^']+'|\S+(?:\s+\S+){0,$words})\s*$~ui", "<span>$1</span>", $text);

        return $result;
    }
    

    static function fill_href($text) {
        $server = $_SERVER['HTTP_HOST'];
        $result = preg_replace("~<a(.*?)href\s{0,}=\s{0,}['\"]([-\/\d_a-zA-Z]*?)['\"](.*?)?>~uUi", "<a$1href=\"http://{$server}$2\" $3>", $text);
        return $result;
    }
}