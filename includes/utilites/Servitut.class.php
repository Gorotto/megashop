<?
/*
    $a = new Servitut();
    $a->fill(array('Publication', 100));
    $a->fill(array('CatalogCategory', 'CatalogEntry', 10));

    В будущем: $a->fill_deep();
*/
class Servitut {

    static private $source_path = "/static/servitut";
    static private $class_content_types = array(
        'NamiFkDbField'         => 'fk',
        'NamiTextDbField'       => 'text',
        'NamiCharDbField'       => 'string',
        'NamiIntegerDbField'    => 'int',
        'NamiFloatDbField'      => 'float',
        'NamiPriceDbField'      => 'float',
        'NamiEnumDbField'       => 'enum',
        'NamiBoolDbField'       => 'bool',
        'NamiDatetimeDbField'   => 'datetime',
        'NamiImageDbField'      => 'image',
        'NamiFileDbField'       => 'file',
        'NamiArrayDbField'      => 'array',
    );

    static private $cache;

    public function __construct() {
        $this->source = "{$_SERVER['DOCUMENT_ROOT']}".self::$source_path;
    }

    /**
    *   Заполняем модель значениями (нахуячиваем)
    */
    function fill($models_names, $entries_count = 10) {
        if (!is_array($models_names)) {
            $models_names = array($models_names,);
        }
        foreach ($models_names as $model_name) {
            $model = new $model_name;

            for ($i = 0; $i < ($entries_count + 1); $i++) {
                $content = array();
                foreach ($model->definition() as $field_name => $instance) {
                    if ($field_name == 'enabled') {
                        $value = 1;
                    } else {
                        $classname = get_class($instance);
                        $fk_model = (property_exists($instance, 'model')) ? $instance->model : null;
                        $type = @$instance->valueOptions['type'] ? $instance->valueOptions['type'] : null;

                        $value = call_user_func(array($this, "get_".self::$class_content_types[$classname]), compact('fk_model', 'type'));
                    }
                    $content[$field_name] = $value;
                }
                //print_r($content);
                NamiQuerySet($model_name)->create($content);
            }
        }
    }

    // Взять случайное значение связанной модели
    function get_fk($params) {
        $ids = NamiQuerySet($params['fk_model'])->values('id');
        if (!$ids) {
            return null;
        }
        return $ids[mt_rand(0, count($ids) - 1)];
    }

    // Взять случайный текст
    function get_text() {
        if (!@self::$cache['text']) {
            self::$cache['text'] = file("{$this->source}/text.txt");
        }
        $paragraph_count = mt_rand(2, 3);
        $text = '';
        for ($i = 0; $i < $paragraph_count; $i++) {
            $text .= "<p>";
            $text .= self::$cache['text'][mt_rand(0, count(self::$cache['text']) - 1)];
            $text .= "</p>";
        }
        return $text;
    }

    // Взять случайную строку из файла
    function get_string() {
        if (!@self::$cache['strings']) {
            self::$cache['strings'] = file("{$this->source}/string.txt");
        }
        return self::$cache['strings'][mt_rand(0, count(self::$cache['strings']) - 1)];
    }

    // Взять случайное целое число в диапозоне от 1 до 100000
    function get_int() {
        return mt_rand(1, 100000);
    }

    // Взять случайное число с плавающей точкой в диапозоне от 1.00 до 100000.00
    function get_float () {
        return mt_rand(100, 10000000) / 100;
    }

    // Взять случайную правду
    function get_bool() {
        return mt_rand(0, 1);
    }

    // Взять случайную дату в диапозоне от сейчас до двух лет назад
    function get_datetime() {
        return date('Y-m-d', strtotime('-'.mt_rand(0, 730).' days'));
    }

    // Взять случайную картинку
    function get_image() {
        if (!@self::$cache['images']) {
            function is_image($image) {
                return preg_match('~.jpg$~', $image);
            }
            self::$cache['images'] = array_values(array_filter($this->list_dir('images'), 'is_image'));
        }
        return self::$cache['images'][mt_rand(0, count(self::$cache['images']) - 1)];
    }

    // Взять случайный файл
    function get_file() {
        if (!isset(self::$cache['files'])) {
            self::$cache['files'] = array_values($this->list_dir('files'));
        }
        return self::$cache['files'][mt_rand(0, count(self::$cache['files']) - 1)];
    }

    // Взять случайный набор элементов
    function get_array($params) {
        $arr = array();
        $count = mt_rand(2, 8);
        for ($i = 0; $i < $count; $i++) {
            $arr[] = call_user_func(array($this, "get_".self::$class_content_types[$params['type']]));
        }
        return $arr;
    }

    // Список файлов директории в виде массива
    function list_dir($dir) {
        $files = array();
        foreach (scandir("{$this->source}/{$dir}") as $file) {
            if ($file === '.' || $file === '..' || is_dir("{$this->source}/{$dir}/{$file}")) {
                continue;
            }
            $files[] = self::$source_path."/{$dir}/{$file}";
        }
        return $files;
    }
}
