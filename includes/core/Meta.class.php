<?php

/**
  Утилиты
 */
class Meta {

    static private $vars = array();
    static private $showExceptionsTrace = true;

    /**
     * Инициализация класса
     */
    static function init() {
        // Прочитаем массивы $_GET, $_POST и $_FILES в общий, сохраняя ссылки на оригинальные элементы оригинальных массивов
        foreach ($_GET as $k => $v) {
            self::$vars[$k] = & $_GET[$k];
        }
        foreach ($_POST as $k => $v) {
            self::$vars[$k] = & $_POST[$k];
        }

        foreach ($_FILES as $k => $v) {
            if (is_array($_FILES[$k]['error'])) {
                self::$vars[$k] = array();
                for ($i = 0; $i < count($_FILES[$k]['error']); $i++) {
                    $file = array();
                    foreach (array_keys($_FILES[$k]) as $key) {
                        $file[$key] = & $_FILES[$k][$key][$i];
                    }
                    self::$vars[$k][] = $file;
                }
            } else {
                self::$vars[$k] = & $_FILES[$k];
            }
        }

        // Удалим лишние кавычки
        if (get_magic_quotes_gpc()) {
            foreach (self::$vars as & $v) {
                if (!is_array($v))
                    $v = stripcslashes($v);
            }
        }
    }

    /**
     * Возвращает ссылку на массив входящих переменных.
     * Если передано имя — возвращает ссылку на конкретую переменную из него
     */
    static function & vars($name = null) {
        if ($name) {
            return self::$vars[$name];
        } else {
            return self::$vars;
        }
    }

    /**
      Разворачивает массив в ассоциативный массив по определенному ключу каждой записи.
      Запись может быть массивом или объектом.
     * @param array     $source исходный массив
     * @param string    $key поле по которому будет разворачитваться массив, может быть ссылкой на другой объект
     * @param bool      $group группировать элементы по данному полю, или просто развернуть каждый
     * @param string    $fk_field название поля связанного объекта для развертки
     *                  по-умолчанию  'id' (бесполезен, если тип $key != 'object')
     * #Examples
     * пример1 из пагинатора: $this->objects = Meta::getAssocArray($this->objects, $this->group_field, true, 'title');
     * -> значит развернуть по category->title
     * result = array('Новая категория' => array(
     *      array('id' => 123, 'title' => 'Новый товар1')),
     *      array('id' => 124, 'title' => 'Новый товар2'))
     * );
     * пример2: Meta::getAssocArray($entries, 'id', false);
     * -> для каждого элемента будет создан ключ по полю 'id'
     * result = array(
     *      '123' => array('id' => 123, 'title' => 'Товар1')
     *      '124' => array('id' => 124, 'title' => 'Товар2')
     * );
     */
    static function getAssocArray(array $source, $key = null, $group = false, $fk_field = 'id') {
        $result = array();

        foreach ($source as $i) {
            $assoc_key = $key ? ( is_object($i) ? (is_object($i->$key) ? $i->$key->{$fk_field} : $i->$key ) : $i[$key] ) : $i;
            if ($group) {
                $result[$assoc_key][] = $i;
            } else {
                $result[$assoc_key] = $i;
            }
        }
        return $result;
    }

    /**
      Делает из линейного массива таблицу с заданным количством колонок — массив массивов
      Возвращает результат
     */
    static function getGrid(array $array, $columnsCount = 3) {
        $rows = array();

        for ($offset = 0; $offset < count($array); $offset += $columnsCount) {
            $rows[] = array_slice($array, $offset, $columnsCount);
        }

        return $rows;
    }

    /**
      Транслитерация строки
      Кириллические буквы меняются на латинские буквы, пробелы — на подчеркивания,
      цифры остаются без изменения, все остальное — удаляется.
     */
    static function getPathName($str) {
        if (is_array($str)) {
            foreach ($str as $s) {
                $s = self::getPathName($s);
                if (self::isPathName($s)) {
                    return $s;
                }
            }
            return null;
        }

        $trans = array(
            "а" => "a", "б" => "b", "в" => "v", "г" => "g", "д" => "d", "е" => "e",
            "ё" => "yo", "ж" => "j", "з" => "z", "и" => "i", "й" => "i", "к" => "k",
            "л" => "l", "м" => "m", "н" => "n", "о" => "o", "п" => "p", "р" => "r",
            "с" => "s", "т" => "t", "у" => "y", "ф" => "f", "х" => "h", "ц" => "c",
            "ч" => "ch", "ш" => "sh", "щ" => "sh", "ы" => "i", "э" => "e", "ю" => "u",
            "я" => "ya", "А" => "A", "Б" => "B", "В" => "V", "Г" => "G", "Д" => "D",
            "Е" => "E", "Ё" => "Yo", "Ж" => "J", "З" => "Z", "И" => "I", "Й" => "I",
            "К" => "K", "Л" => "L", "М" => "M", "Н" => "N", "О" => "O", "П" => "P",
            "Р" => "R", "С" => "S", "Т" => "T", "У" => "Y", "Ф" => "F", "Х" => "H",
            "Ц" => "C", "Ч" => "Ch", "Ш" => "Sh", "Щ" => "Sh", "Ы" => "I", "Э" => "E",
            "Ю" => "U", "Я" => "Ya", " " => "_");

        $str = str_replace(array_keys($trans), array_values($trans), trim($str));
        $str = preg_replace('/[^.a-zA-Z0-9_-]+/', '', $str);
        $str = preg_replace('/_{2,}/', '_', $str);

        return mb_strtolower($str, 'utf-8');
    }

    /**
      Проверка валидности имени пути
     */
    static function isPathName($str) {
        return preg_match('/^[.a-zA-Z0-9_-]+$/', $str);
    }

    /**
      Получение превью переданного текста не более указанной длины, не разрезая слова
     */
    static function getTextPreview($str, $maxlen) {
        if (mb_strlen($str, 'utf-8') > $maxlen) {
            // Получаем подстроку на один символ больше, чем нужно
            $str = mb_substr($str, 0, $maxlen + 1, 'utf-8');

            // Делаем замену с конца
            $str = preg_replace('/[\s?!.,:;]+[^\s?!.,:;]*$/', '', $str);
        }

        return $str;
    }

    /**
      Получение uri path текущей страницы, на основе $_SERVER[REQUEST_URI], или любой другой переданной переменной
      Возвращает путь в виде /levelone/leveltwo/levelthree, или /levelone/leveltwo/levelthree.html
      Последний слеш удаляется, кроме первого (вырожденный path = '/').
     */
    static function getUriPath($source = null) {
        if (is_null($source)) {
            $source = $_SERVER['REQUEST_URI'];
        }

        if (preg_match('/^(([^:\/?#]+):)?(\/\/([^\/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?/', $source, $m)) {
            return mb_strlen($m[5], 'utf-8') > 1 ? preg_replace('/\/$/', '', $m[5]) : $m[5];
        }

        return null;
    }

    /**
      Разбор uri path на части, возвращает массив элементов пути.
     */
    static function getUriPathNames($path = null) {
        if (is_null($path)) {
            $path = self::getUriPath();
        }

        $path = preg_replace('/\/+$|^\/+/', '', trim($path));

        if ($path == '')
            return array();

        return preg_split('/\//', $path);
    }

    /**
     *   Проверка нахождения переданного uri в переданном пути uri.
     */
    static function in_uripath($uri, $path = null, $strict = false) {
        if (is_null($path)) {
            $path = Meta::getUriPath() . "/";
        }

        if (mb_substr($uri, -1, 1) != "/") {
            $uri .= "/";
        }
        if (mb_substr($uri, 1, 1) != "/") {
            $uri = "/" . $uri;
        }
        if (mb_substr($path, -1, 1) != "/") {
            $path .= "/";
        }
        if (mb_substr($path, 1, 1) != "/") {
            $path = "/" . $path;
        }

        if ($strict) {
            if ($path == $uri) {
                return true;
            } else {
                return false;
            }
        } else {
            if (strpos($path, $uri) !== false) {
                return true;
            } else {
                return false;
            }
        }
    }

    /*
     * Возвращает состояние узла (uri страницы, категории) для выделения в меню активного или текущего эдемента
     * @param $uri_path - путь по которому проверять (обычно uri или магический full_uri объекта)
     * @param $active_postfix - строка которая возвращается если текущий узел - активный
     * @param $current_postfix - строка которая возвращается если текущий узел - текущий :D
     * return array - массив с типом ('', 'active', 'current') узла и соответствующая строка для этого типа
     */

    static function get_node_type($uri_path, $active_postfix = '_active', $current_postfix = '_current') {
        if (!$uri_path)
            throw new NamiException('Не указан uri!');

        $uri_info = array(
            'type' => '',
            'postfix' => '',
        );

        // если путь в адресной строке совпадает с переданным $uri_path,
        // то текущая страница будет current
        // иначе, если есть $uri_path входит в множество не полностью
        // мы имеет активную страницу
        if (Meta::in_uripath($uri_path, null, true)) {
            $uri_info['type'] = 'current';
            $uri_info['postfix'] = $current_postfix;
        } elseif (Meta::in_uripath($uri_path)) {
            $uri_info['type'] = 'active';
            $uri_info['postfix'] = $active_postfix;
        }

        return $uri_info;
    }

    static function decline($num, $zero, $one, $two, $many) {
        $nmod10 = $num % 10;
        $nmod100 = $num % 100;

        if (!$num)
            return preg_replace("/%n/", $num, $zero);

        if (( $num == 1) || ( $nmod10 == 1 && $nmod100 != 11 ))
            return preg_replace("/%n/", $num, $one);

        if ($nmod10 > 1 && $nmod10 < 5 && $nmod100 != 12 && $nmod100 != 13 && $nmod100 != 14)
            return preg_replace("/%n/", $num, $two);

        return preg_replace("/%n/", $num, $many);
    }

    /**
     *    Замена функции file_exists, использующая include_path (по умолчанию — не использующая ;)
     */
    static function file_exists($filename, $use_include_path = false) {
        if (!$use_include_path) {
            return file_exists($filename);
        }
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

    /**
     *    Форматированние трейса, полученного из Exception->getTrace() или debug_backtrace().
     *    Возвращает трейс в виде HTML-таблицы.
     */
    static function formatDebugTrace(array $rawTrace) {
        $traces = array();
        $num = count($rawTrace);

        if (!$rawTrace) {
            return '';
        }

        $dump = '<style>.trace {border:none;border-collapse:collapse;margin:0 0 1em 0;} .trace td, .trace th {border:1px solid #000;padding:5px 7px;}</style>';

        foreach ($rawTrace as $trace) {
            $call = '';

            if (array_key_exists('object', $trace) && is_object($trace['object']) && method_exists($trace['object'], 'toTraceString')) {
                $call .= $trace['object']->toTraceString() . "{$trace['type']}";
            } else if (array_key_exists('class', $trace)) {
                $call .= "{$trace['class']}{$trace['type']}";
            }

            $call .= $trace['function'];

            if (array_key_exists('args', $trace)) {
                $args = array();
                foreach ($trace['args'] as $arg) {
                    if (is_null($arg)) {
                        $args[] = 'NULL';
                    } elseif (is_object($arg)) {
                        if (method_exists($arg, 'toTraceString')) {
                            $args[] = $arg->toTraceString();
                        } else {
                            $args[] = get_class($arg);
                        }
                    } elseif (is_array($arg)) {
                        $args[] = print_r($arg, true);
                    } else {
                        $args[] = "'{$arg}'";
                    }
                }
                $call .= '(' . ( $args ? ' ' . join(', ', $args) . ' ' : '' ) . ')';
            }

            if (array_key_exists('file', $trace)) {
                $trace['file'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $trace['file']);
            } else {
                $trace['file'] = '';
            }

            if (!array_key_exists('line', $trace)) {
                $trace['line'] = '';
            }

            $traces[] = "<tr><td>{$num}</td><td>{$call}</td><td>{$trace['file']}</td><td>{$trace['line']}</td></tr>";
            $num--;
        }

        $dump .= '<table class="trace"><tr><th>#</th><th>Call</th><th>Source file</th><th>Line</th></tr>' . join($traces) . "</table></body></html>";

        return $dump;
    }

    static function str_replace_once($needle, $replace, $haystack) {
        // Looks for the first occurence of $needle in $haystack
        // and replaces it with $replace.
        $pos = strpos($haystack, $needle);
        if ($pos === false) {
            // Nothing found
            return $haystack;
        }
        return substr_replace($haystack, $replace, $pos, strlen($needle));
    }

    /**
     * Получение символьного имени ошибки php. Используется в обработчике ошибок.
     */
    static function getPhpErrorName($errno) {
        $errors = array(
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        );
        return array_key_exists($errno, $errors) ? $errors[$errno] : $errno;
    }

    /**
      Транслитерация заголовка согласно ГОСТ 16876-71
      обрезается по словам
     */
    static $trans_table = array(
        "Є" => "EH", "І" => "I", "і" => "i", "№" => "#", "є" => "eh",
        "А" => "A", "Б" => "B", "В" => "V", "Г" => "G", "Д" => "D",
        "Е" => "E", "Ё" => "JO", "Ж" => "ZH",
        "З" => "Z", "И" => "I", "Й" => "JJ", "К" => "K", "Л" => "L",
        "М" => "M", "Н" => "N", "О" => "O", "П" => "P", "Р" => "R",
        "С" => "S", "Т" => "T", "У" => "U", "Ф" => "F", "Х" => "KH",
        "Ц" => "C", "Ч" => "CH", "Ш" => "SH", "Щ" => "SHH", "Ъ" => "'",
        "Ы" => "Y", "Ь" => "", "Э" => "EH", "Ю" => "YU", "Я" => "YA",
        "а" => "a", "б" => "b", "в" => "v", "г" => "g", "д" => "d",
        "е" => "e", "ё" => "jo", "ж" => "zh",
        "з" => "z", "и" => "i", "й" => "jj", "к" => "k", "л" => "l",
        "м" => "m", "н" => "n", "о" => "o", "п" => "p", "р" => "r",
        "с" => "s", "т" => "t", "у" => "u", "ф" => "f", "х" => "kh",
        "ц" => "c", "ч" => "ch", "ш" => "sh", "щ" => "shh", "ъ" => "",
        "ы" => "y", "ь" => "", "э" => "eh", "ю" => "yu", "я" => "ya", "«" => "", "»" => "", "—" => "", " " => "_");

    static function getTranslit($str, $length) {
        if (strlen($str) > $length) {
            // если строка длинее $length, обрезаем строку до $length
            // и записываем положение последнего оставшегося пробела
            $l = strripos(substr($str, 0, $length), ' ');
            // и обрезаем эту строку по этот пробел
            $str = substr($str, 0, $l);
        }

        $str = str_replace(array_keys(self::$trans_table), array_value(self::$trans_table), trim($str));
        $str = preg_replace('/[^a-zA-Z0-9_]+/', '', $str);
        $str = preg_replace('/_{2,}/', '_', $str);
        return mb_strtolower($str, 'utf-8');
    }

    /**
     *   Определение кодировки текста выбором из переданного набора кодировок
     *   $string - строка, которую нужно проанализировать
     *   $encodings - строка, набор кодировок, разделенных пробелом
     *   Возвращает определенную кодировку, или false, если определение не удалось
     */
    function detect_encoding($string, $encodings = 'utf-8 windows-1251 koi8-r cp886') {
        $list = explode(' ', $encodings);
        foreach ($list as $item) {
            $sample = @iconv($item, $item, $string);
            if (md5($sample) == md5($string)) {
                return $item;
            }
        }
        return false;
    }

    static function beauty_price($price) {
        if (($price * 100) % 100) {
            return number_format($price, 2, '.', ' ');
        } else {
            return number_format($price, 0, '.', ' ');
        }
    }

    //-------------------------- шаблоны преобразований чисел -----------

    static function isAjaxRequest() {
        return (array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }

    static function cut_text($text, $lenght_to_cut) {
        $text = preg_replace("~\s+~u", " ", trim(strip_tags($text)));

        if (mb_strlen($text) > $lenght_to_cut) {
            $text = mb_substr($text, 0, $lenght_to_cut);

            preg_match("~^(.*[\w]{4,})(?=[\s\.!\?,-]{1,})~u", $text, $result);

            if ($result) {
                $text = $result[1];
            }
            $text .= "&hellip;";
        }

        return $text;
    }

    static function get_ru_day_of_week($day_number, $short = false) {
        $day_number = (int) $day_number;

        $days = array(
            1 => 'Понедельник',
            2 => 'Вторник',
            3 => 'Среда',
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота',
            0 => 'Воскресенье',
        );

        if ($short) {
            $days = array(
                1 => 'Пн',
                2 => 'Вт',
                3 => 'Ср',
                4 => 'Чт',
                5 => 'Пт',
                6 => 'Сб',
                0 => 'Вс',
            );
        }

        if ($day_number < 7 && $day_number >= 0) {
            return $days[$day_number];
        } else {
            return false;
        }
    }

    static function get_month_by_number($number, $singular = false, $en = false) {
        $number = (int) $number;
        $months_ru_singular = array(
            1 => 'Январь',
            2 => 'Февраль',
            3 => 'Март',
            4 => 'Апрель',
            5 => 'Май',
            6 => 'Июнь',
            7 => 'Июль',
            8 => 'Август',
            9 => 'Сентябрь',
            10 => 'Октябрь',
            11 => 'Ноябрь',
            12 => 'Декабрь',
        );

        $months_ru = array(
            1 => 'января',
            2 => 'февраля',
            3 => 'марта',
            4 => 'апреля',
            5 => 'мая',
            6 => 'июня',
            7 => 'июля',
            8 => 'августа',
            9 => 'сентября',
            10 => 'октября',
            11 => 'ноября',
            12 => 'декабря',
        );

        $months_en = array(
            1 => 'january',
            2 => 'february',
            3 => 'march',
            4 => 'april',
            5 => 'may',
            6 => 'june',
            7 => 'july',
            8 => 'august',
            9 => 'september',
            10 => 'october',
            11 => 'november',
            12 => 'december',
        );

        if ($number > 0 && $number < 13) {
            if ($singular) {
                return $months_ru_singular[$number];
            } else if ($en) {
                return $months_en[$number];
            } else {
                return $months_ru[$number];
            }
        } else {
            return false;
        }
    }

    static function get_normal_date($timestamp) {
        $string = "";

        $string .= date("d", $timestamp);
        $string .= " ";
        $string .= self::get_month_by_number(date("m", $timestamp));
        $string .= " ";
        $string .= date("Y", $timestamp);

        return $string;
    }

    static function get_human_type_date($timestamp, $show_time = true) {
        $string = "";

        if ((strtotime("00:00:00") < $timestamp) && (strtotime("23:59:59") > $timestamp)) {
            //сегодня
            $string .= "Сегодня";
        } else if ((strtotime("00:00:00 -1day") < $timestamp) && (strtotime("23:59:59 -1day") > $timestamp)) {
            //вчера
            $string .= "Вчера";
        } else {
            //хуй знает когда
            $string .= date("d", $timestamp) * 1;
            $string .= " ";
            $string .= self::get_month_by_number(date("m", $timestamp));
            $string .= " ";
            $string .= date("Y", $timestamp);
        }

        if ($show_time) {
            $string .= ", ";
            $string .= date("H:i", $timestamp);
        }

        return $string;
    }

    static $rus_en_table = array(
        "А" => "A", "В" => "B", "Е" => "E", "К" => "K", "М" => "M",
        "Н" => "H", "О" => "O", "Р" => "P", "С" => "C", "Т" => "T",
        "У" => "Y", "Х" => "X");

    static function rus_to_en($str) {
        $str = str_replace(array_keys(self::$rus_en_table), array_values(self::$rus_en_table), trim($str));
        return mb_strtolower($str, 'utf-8');
    }

    static $rus_search_letters = array("А", "Б", "В", "Г", "Д", "Е", "Ж", "З", "И", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Ч", "Ш", "Щ", "Ы", "Э", "Ю", "Я");

    /**
     * Возвращалка красивого размера файлов
     * @param type $filesize размер файла некрасивый
     * @return type размер красивый
     */
    static function beauty_file_size($filesize, $unit = array('б', 'Кб', 'Мб', 'Гб', 'Тб', 'Пб', 'Эб', 'Зб', 'Йб')) {
        if ($filesize > 0) {
            return round($filesize / pow(1024, ($m = floor(log($filesize, 1024))))) . "&nbsp;" . $unit[$m];
        } else {
            return $filesize . "&nbsp;" . $unit[0];
        }
    }

    static function vd($args) {
        echo "<div style='color: #333; border: 1px solid #bbb; font-size: 14px; background: #eee; margin: 1px; padding: 5px; display: inline-block; vertical-align: top'><pre>";
        var_dump($args);
        echo "</pre></div>";
    }

    /**
      функция для преобразования в нормальный вид массивов, которые сгенерированы через ORM и выдаают список каких-то элементов через метод value
      @array $array - массив, который надо разобрпать
      @string $rows - список ключей (перечисленные через запятую), данные из которых нужно извлечь
     */
    static function normal_array($array, $keys) {
        $result = false;

        if (!count($keys)) {
            throw new Exception('Need string argument $row');
        }

        if (is_array($array)) {
            $result = array();

            foreach ($array as $id => $value) {

                foreach ($keys as $key) {
                    $result[] = $value[$key];
                }
            }
        } else {
            throw new Exception('method "normal_array" need array argument');
        }

        return $result;
    }

    /**
     * htmlentities настроенная на русский
     * @param  string $string строка для обработки
     * @return string преобразованная строка
     */
    static function he($string = NULL) {
        return (!is_null($string) && $string) ? htmlentities($string, ENT_COMPAT || ENT_HTML5, 'UTF-8') : '';
    }

    /**
      Форматирование текста для textarea без richtext
     */
    static function nl2p($string) {
        if (!is_string($string)) {
            return false;
        }

        return "<p>" . preg_replace("~\<br( {0,})?(\/)?\>~", "</p><p>", nl2br($string)) . "</p>";
    }

    static function set_uri_param($param_name, $param_value, $uri = null) {
        if (!$uri) {
            $uri = $_SERVER['REQUEST_URI'];
        }

        if (preg_match("/[\?\&]" . preg_quote($param_name) . "(?:(?:[\=]+[\d\w-_]*)|$)/i", $uri)) {
            $uri = preg_replace("/([\?\&])" . preg_quote($param_name) . "(?:(?:[\=]+[\d\w-_]*)|$)/i", "$1" . $param_name . "=" . $param_value, $uri);
        } else {
            if (mb_strpos($uri, "?") === false) {
                $uri .= "?" . $param_name . "=" . $param_value;
            } else {
                $uri .= "&" . $param_name . "=" . $param_value;
            }
        }

        return $uri;
    }

    /**
     * расширение файла
     */
    static function get_file_ext($filename) {
        return mb_substr($filename, mb_strrpos($filename, ".") + 1);
    }

    /*
     * Формирует двух уровневое дерево из родительских и дочерних элементов
     * @param array of objects $parents - родительские элементы
     * @param array of objects $children - дочерние элементы (должны быть связаны с $parents через поле category)
     * все объекты должны иметь title, full_uri
     * return array 2lvl tree
     */

    static function getMenuTree($parents, $children) {
        $small_tree = array();
        foreach ($parents as $parent) {
            $small_tree[$parent->id] = array(
                'title' => $parent->title,
                'uri' => $parent->full_uri,
                'children' => array(),
            );
            foreach ($children as $child) {
                if ($parent->id == $child->category->id) {
                    $small_tree[$parent->id]['children'][] = array(
                        'title' => $child->title,
                        'uri' => $child->full_uri,
                        'children' => array(),
                    );
                }
            }
        }
        return $small_tree;
    }

    static function getUserIpAddress() {
        $ip_keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    // trim for safety measures
                    $ip = trim($ip);
                    // attempt to validate IP
                    if (self::validateIp($ip)) {
                        return $ip;
                    }
                }
            }
        }

        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
    }

    /**
     * Счетчик обратного отсчета
     */
    static function downcounter($timestamp, $hide_sec = false, $hide_min = false) {
        $check_time = $timestamp - time();
        if ($check_time <= 0) {
            return false;
        }

        $days = floor($check_time / 86400);
        $hours = floor(($check_time % 86400) / 3600);
        $minutes = floor(($check_time % 3600) / 60);
        $seconds = $check_time % 60;

        $str = '';
        if ($days > 0) {
            $str .= Meta::declension($days, array('день', 'дня', 'дней')) . ' ';
        }

        if ($hours > 0) {
            $str .= Meta::declension($hours, array('час', 'часа', 'часов')) . ' ';
        }

        if ($minutes > 0 && !$hide_min) {
            $str .= Meta::declension($minutes, array('минуту', 'минуты', 'минут')) . ' ';
        }

        if (($minutes == 0) && !$hide_min && $hide_sec) {
            $str .= '1 минуту ';
        }

        if ($seconds > 0 && !$hide_sec) {
            $str .= Meta::declension($seconds, array('секунда', 'секунды', 'секунд'));
        }

        return $str;
    }

    /**
     * Функция склонения слов
     */
    static function declension($digit, $expr, $onlyword = false) {
        if (!is_array($expr))
            $expr = array_filter(explode(' ', $expr));
        if (empty($expr[2]))
            $expr[2] = $expr[1];
        $i = preg_replace('/[^0-9]+/s', '', $digit) % 100;
        if ($onlyword)
            $digit = '';
        if ($i >= 5 && $i <= 20)
            $res = $digit . ' ' . $expr[2];
        else {
            $i%=10;
            if ($i == 1)
                $res = $digit . ' ' . $expr[0];
            elseif ($i >= 2 && $i <= 4)
                $res = $digit . ' ' . $expr[1];
            else
                $res = $digit . ' ' . $expr[2];
        }
        return trim($res);
    }

    static function getYoutubeIdentify($url) {
        $identify = null;

        $url = trim($url);

        $pattern = '/^(?:https?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=))(?P<code>[\w-]{10,12})$/';
        preg_match($pattern, $url, $matches);

        if (isset($matches["code"])) {
            $identify = $matches["code"];
        } else {
            throw new Exception("Ссылка на видео неверна");
        }

        return $identify;
    }

    static function getYoutubePreview($identify) {
        $file_uri = null;

        //список урлов для получения превьюхи в порядки уменьшения качества
        $sources_list = array(
            array(
                "uri" => "http://img.youtube.com/vi/%identify%/maxresdefault.jpg",
                "plug_md5" => "e2ddfee11ae7edcae257da47f3a78a70",
            ),
            array(
                "uri" => "http://img.youtube.com/vi/%identify%/sddefault.jpg",
                "plug_md5" => "e2ddfee11ae7edcae257da47f3a78a70",
            ),
            array(
                "uri" => "http://img.youtube.com/vi/%identify%/hqdefault.jpg",
                "plug_md5" => "e2ddfee11ae7edcae257da47f3a78a70",
            ),
        );


        //----------получаем превьюшку с ютуба---------------
        foreach ($sources_list as $source_data) {
            $source_uri = str_replace("%identify%", $identify, $source_data['uri']);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_URL, $source_uri);
            curl_setopt($ch, CURLOPT_REFERER, $source_uri);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $raw = curl_exec($ch);
            curl_close($ch);

            $tmp_img_url = $_SERVER['DOCUMENT_ROOT'] . "/static/uploaded/youtube.jpg";
            if (file_exists($tmp_img_url)) {
                unlink($tmp_img_url);
            }
            $fp = fopen($tmp_img_url, 'x');
            fwrite($fp, $raw);
            fclose($fp);

            if (md5_file($_SERVER['DOCUMENT_ROOT'] . "/static/uploaded/youtube.jpg") == $source_data['plug_md5']) {
                //если ютуб вернул по какаим-то причинам свою заглушку
                //нам этого нахуй не надо
                continue;
            } else {
                $file_uri = "/static/uploaded/youtube.jpg";

                //отрезаем пару пикселей, т.к. на ютуб заливают видос с рамочкой какой-то
                $file_uri_ = $_SERVER['DOCUMENT_ROOT'] . $file_uri;
                $thumb = new Imagick($file_uri_);
                $thumb->cropImage($thumb->getImageWidth() - 10, $thumb->getImageHeight() - 10, 5, 5);
                $thumb->writeImage($file_uri_);

                return $file_uri;
            }
        }

        return $file_uri;
    }

    static function isHttps() {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) || isset($_SERVER['HTTPS'])) {
            return true;
        } else {
            return false;
        }
    }

}
