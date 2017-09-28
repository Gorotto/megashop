<?

class NamiFileDbFieldValue extends NamiDbFieldValue {

    protected $path;   // путь куда будут загружаться файлы (относительно DOCUMENT_ROOT)
    protected $source;   // источник нового файла, инициализируется при присваивании файлу нового значения
    protected $oldValues;  // Старое значение поля, чтобы удалить файлы при перезаписи
    public $title; // навзание файла в человекопонятном виде
    public $name; // имя файла
    public $uri; // uri файла, относительно $_SERVER['DOCUMENT_ROOT']
    public $size; // размер файла

    /**
     * 	Конструктор.
     * 	Принимает параметры от конструктора поля, заполняет всякие штуки
     */

    function __construct(array $params = array()) {
        if (!$params['path']) {
            throw new NamiException("Необходимо указать параметр 'path' — путь к каталогу для хранения файлов.");
        }

        $this->path = $params['path'];
        if ($this->path[0] != '/') {
            $this->path = "/{$this->path}";
        }

        $this->oldValues = array();
    }

    /**
     * 	Получение значения поля для сохранения в базу данных
     */
    function getForDatabase() {
        return $this->value ? json_encode($this->prepareDatabaseValue()) : null;
    }

    /**
     * 	Подготовка значения для базы данных. Возвращает объект, который должен быть сохранен в БД.
     */
    protected function prepareDatabaseValue() {
        return (object) array(
                'name' => $this->name,
                'title' => $this->title,
                'size' => $this->size,
        );
    }

    /**
     * 	Инициализация поля значением из базы данных
     * 	$value — значение колонки в БД, полученное из запроса
     */
    function setFromDatabase($value) {
        if ($value && $value = json_decode($value, false)) {
            $this->loadDatabaseValue($value);
        } else {
            $this->name = null;
            $this->title = null;
        }
        $this->checkValue();
    }

    /**
     * 	Загрузка значения, полученного из базы данных
     * 	$value — значение из БД, декодированное в объект
     */
    protected function loadDatabaseValue($value) {
        foreach (get_object_vars($value) as $name => $value) {
            $this->$name = $value;
        }
    }

    /**
     * 	Получение упрощенной версии объекта
     */
    function getSimplified($short = false) {
        return $this->value ? $this->prepareSimplifiedValue() : null;
    }

    /**
     * 	Подготовка значения для JSON. Возвращает объект.
     */
    protected function prepareSimplifiedValue() {
        return (object) array(
                'name' => $this->name,
                'title' => $this->title,
                'size' => $this->size,
                'uri' => $this->uri
        );
    }

    /**
     * 	Установка значения, пользовательские скрипты
     */
    function set($value) {
        $this->loadValue($value);
        $this->checkValue();
        return $this;
    }

    /**
     * 	Загрузка файла
     *  Может принимать занчения:
     *  1. массив $_FILES
     *  2. массив
     *      server_path_uri - путь до файла на сервере
     *      title - название файла (не уникально, может отсутствовать)
     *  3. строка - путь до файла на сервере
     */
    protected function loadValue($value) {
        if (is_array($value)) {
            if (array_key_exists('error', $value) && array_key_exists('name', $value) && array_key_exists('tmp_name', $value)) {
                $this->loadValueInput($value);
            } else if (array_key_exists('server_path_uri', $value)) {
                $this->loadValueServer($value);
            } else {
                throw new NamiValidationException("Недопустимое значение для загрузки файла");
            }
        } else if (is_string($value) && $value !== "") {
            $value_ = array(
                "server_path_uri" => $value,
            );
            $this->loadValueServer($value_);
        } else if (is_null($value) || $value === '') {
            if ($this->value) {
                $this->oldValues[] = $this->prepareSimplifiedValue();
            }
            $this->name = null;
            $this->title = null;
            $this->source = null;
        } else {
            throw new NamiValidationException("Недопустимое значение для загрузки файла");
        }
    }

    /**
     * Загрузка файла с сервера
     */
    private function loadValueServer($value) {
        // Проверим, не вышли ли мы за DOCUMENT_ROOT
        $fileRealPath = realpath($_SERVER['DOCUMENT_ROOT'] . $value['server_path_uri']);
        $rootRealPath = realpath($_SERVER['DOCUMENT_ROOT']);

        if (substr_compare($rootRealPath, $fileRealPath, 0, strlen($rootRealPath)) == 0) {
            // Файл годный
            $fileinfo = pathinfo($_SERVER['DOCUMENT_ROOT'] . $value['server_path_uri']);
            $filetitle = isset($value['title']) ? $value['title'] : $fileinfo['basename'];
            if (mb_strlen($filetitle) > 100) {
                $filetitle = mb_strcut($filetitle, 0, 100);
            }

            $this->source = array(
                'path' => $_SERVER['DOCUMENT_ROOT'] . $value['server_path_uri'],
                'name' => $fileinfo['basename'],
                'title' => $filetitle,
            );
        } else {
            // Отаке детектед!
            throw new NamiException("Загрузка файла {$value['server_path_uri']} запрещена из соображений безопасности.");
        }
    }

    /**
     * загрузка из $_FILES
     */
    private function loadValueInput($value) {
        if ($value['error'] == UPLOAD_ERR_OK) {
            // Все в порядке, файл успешно загружен
            $this->source = array(
                'path' => $value['tmp_name'],
                'name' => $value['name'],
            );
        } else if ($value['error'] == UPLOAD_ERR_NO_FILE) {
            // Пользователь не указал файл и он не был загружен вообще, представим, что это NULL
            $this->loadValue(null);
        } else {
            // Что-то не в порядке или файл не был указан
            switch ($value['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    throw new NamiException("Файл слишком велик. Максимальный размер файла — " . ini_get('upload_max_filesize') . ".");
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    throw new NamiException("Файл слишком велик.");
                    break;
                case UPLOAD_ERR_PARTIAL:
                    throw new NamiException("Файл не был полностью загружен. Повторите загрузку.");
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new NamiException("Отсутствует временный каталог для записи файла. Обратитесь в службу технической поддержки.");
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    throw new NamiException("Не удалось записать файл на диск. Обратитесь в службу технической поддержки.");
                    break;
                case UPLOAD_ERR_EXTENSION:
                    throw new NamiException("Недопустимый тип файла. Используйте другой файл.");
                    break;
                default:
                    throw new NamiException("При загрузке файла произошла неизвестная ошибка. Попробуйте загрузить файл заново или используйте другой файл. Обратитесь в службу технической поддержки, если ошибка не исчезает.");
                    break;
            }
        }
    }

    /**
     * 	Автоматически вызывается перед сохранением поля в БД
     */
    function beforeSave() {
        // Если у нас есть новый источник данных — самое время обработать его
        if ($this->source) {
            $this->oldValues[] = $this->getSimplified();
            $this->loadSource($this->source);
            $this->checkValue();
            $this->source = null;
        }

        // Если есть старые значение — удалим их
        if ($this->oldValues) {
            foreach ($this->oldValues as $old) {
                $this->removeFiles($old);
            }
            $this->oldValues = array();
        }
    }

    /**
     * 	Перед удалением из БД надлежит почистить файлы
     */
    function beforeDelete() {
        $this->removeFiles($this->getSimplified());
    }

    /**
     * 	Загрузка файла и значения из указанного источника (скорее всего это $this->source)
     */
    function loadSource($source) {
        // Проверим существование всех каталогов и доступность их для записи
        $path = $_SERVER['DOCUMENT_ROOT'] . $this->path;

        if (!@file_exists($path)) {
            if (!@mkdir($path, 0755, true)) {
                throw new NamiException("Невозможно создать каталог '{$path}' для сохранения файла.");
            }
        } else if (!is_writeable($path)) {
            throw new NamiException("Невозможно сохранить файл, запись в каталог '{$path}' запрещена.");
        }

        // Каталоги существуют и доступны для записи. Самое время попробовать прочитать данные.
        $data = file_get_contents($source['path']);
        if ($data === false) {
            throw new NamiException("Невозможно прочитать файл '{$source['path']}'.");
        }

        if (isset($source['title'])) {
            $this->title = $source['title'];
        } else {
            $this->title = mb_substr($source['name'], 0, mb_strpos($source['name'], ".") ? mb_strpos($source['name'], ".") : mb_strlen($source['name']));
        }

        // Сгенерируем имя нового файла
        $filename = $this->getFilename($source['name'], $path);

        // Запишем новый файл
        $written = file_put_contents("{$path}/{$filename}", $data);
        if ($written === false || $written != strlen($data)) {
            throw new NamiException("Произошла ошибка при записи файла '{$path}/{$filename}'.");
        }

        // Если источник был загруженным файлом — удалим его
        if (is_uploaded_file($source['path'])) {
            unlink($source['path']);
        }

        $this->name = $filename;
    }

    /**
     * 	Удаление файлов переданного упрощенного вида значения
     */
    protected function removeFiles($simplified) {
        if (is_object($simplified) && $simplified->name) {
            $file = "{$_SERVER['DOCUMENT_ROOT']}/{$this->path}/{$simplified->name}";
            if (file_exists($file) && is_writeable($file)) {
                unlink($file);
            }
        }
    }

    /**
     * 	Проверка и приведение к правильному виду значения.
     *  Вызывается после работы с частями значения.
     */
    protected function checkValue() {
        if ($this->name) {
            $this->uri = "{$this->path}/{$this->name}";
            $this->size = @filesize("{$_SERVER['DOCUMENT_ROOT']}/{$this->uri}");
            $this->value = $this;
        } else {
            $this->name = null;
            $this->uri = null;
            $this->size = null;
            $this->value = null;
        }
    }

    /**
     * Получение правильного уникального имени файла
     */
    protected function getFilename($name, $path) {
        // Сначала выполним транслитерацию
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

        $name = str_replace(array_keys($trans), array_values($trans), trim($name));
        $name = preg_replace('/[^.a-zA-Z0-9_\-]+/', '', $name);
        $name = preg_replace('/_{2,}/', '_', $name);

        if (preg_match("~\.~", $name)) {
            $file_name = mb_substr($name, 0, mb_strpos($name, "."));
            $file_ext = mb_substr($name, mb_strrpos($name, "."));
        } else {
            $file_name = $name;
            $file_ext = null;
        }

        if (!$file_name) {
            $file_name = rand(0, 1000) . time();
        }

        $new_name = $file_name . $file_ext;

        $rand_try = 0;
        while (file_exists($path . "/" . $new_name)) {
            $file_name = rand(0, 1000) . time();
            $new_name = $file_name . $file_ext;

            $rand_try++;
            if ($rand_try > 10) {
                throw new NamiException("Загружаемый файл имеет некорректное имя. Пожалуйся переименуйте и повторите попытку.");
            }
        }

        return $new_name;
    }

    function beforeSync() {
        $path = "{$_SERVER['DOCUMENT_ROOT']}{$this->path}";
        if (!@file_exists($path)) {
            if (!@mkdir($path, 0755, true)) {
                throw new NamiException("Не удалось создать каталог хранения файлов '{$path}'. Недостаточно привилегий доступа.");
            }
        } else if (!is_writeable($path) && !( @chmod($path, 0755) && is_writeable($path) )) {
            throw new NamiException("Запись в каталог '{$path}' запрещена.");
        }
    }

}
