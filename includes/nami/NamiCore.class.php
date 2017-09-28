<?php

/**
  Штормоядро!
  Штуки, нужные для слаженной работы всех механизмов — связи между моделями, настройки соединения с базой и прочего.
  Singleton, экземпляр можно получить вызовом NamiCore::getInstance();
 */
class NamiCore {

    private static $instance = null;    // Для реализации singleton-а
    private $related;  // массив связанных полей моделей
    private $metadata = array(); // массив метаданных моделей
    private $models; // массив зарегистрированных моделей
    private $querysets; // массив названий querysetов для моделей, ключ - имя модели
    private $backend;
    private $mapper;
    private $language; // текущий язык, включенный в ядре. Влияет на выборку и сохранение данных.
    private $languages; // массив доступных языков

    /*     * **********
      Общедоступные штуки.
     * ******************* */

    /**
      Синхронизация базы данных
     */
    static public function sync() {
        return self::getInstance()->syncdb();
    }

    /**
      Доступ к объекту-синглтону
     */
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new NamiCore();
        }

        return self::$instance;
    }

    /**
      Утилита — получение текущего бэкенда БД
     */
    public static function getBackend() {
        return self::getInstance()->backend;
    }

    /**
      Получение текущего маппера БД
     */
    public static function getMapper() {
        return self::getInstance()->mapper;
    }

    /**
     * 	Установка текущего языка Nami.
     * 	Возвращает true или выбрасывает исключение.
     */
    public static function setLanguage($language) {
        if ($language instanceof NamiLanguage) {
            $language = $language->name;
        }
        if (array_key_exists($language, self::getInstance()->languages)) {
            self::getInstance()->language = self::getInstance()->languages[$language];
            return true;
        }
        throw new NamiException("Неизвестный язык '{$language}'");
    }

    /**
     * 	Получение текущего языка Nami.
     * 	Возвращает NamiLanguage.
     */
    public static function getLanguage($name = null) {
        if (!$name) {
            return self::getInstance()->language;
        }
        if (array_key_exists($name, self::getInstance()->languages)) {
            return self::getInstance()->languages[$name];
        }
        throw new NamiException("Неизвестный язык '{$name}'");
    }

    /**
     * 	Получение списка доступных языков Nami.
     * 	Возвращает массив NamiLanguage.
     */
    public static function getAvailableLanguages() {
        return self::getInstance()->languages;
    }

    /**
      Функция старта класса
     */
    public static function init() {
        // Зарегистрируем все известные из конфига модели
        foreach (NamiConfig::$models as $def) {
            if (is_array($def)) {
                $classname = $def[0];
                $querysetname = $def[1];
            } else {
                $classname = $def;
                $querysetname = "{$def}s";
            }

            // Запомним имя модели и querysetа
            self::getInstance()->models[] = $classname;
            self::getInstance()->querysets[$classname] = $querysetname;
        }

        foreach (self::getInstance()->models as $classname) {
            // Получим связи типа один-ко-многим, и сложим их в наше поле related.
            // Ключи в этом поле — модель, содержащая записи-ключи (one)
            foreach (self::getInstance()->getNamiOneToManyRelations($classname) as $relation) {
                self::getInstance()->related[$relation->key_model][] = $relation;
            }
        }
    }

    /*     * *****************************
      Системные методы — обеспечивают работу Nami как единого целого
     * ****************************** */

    /**
      Приватный конструктор — извне невозможно сконструировать экземпляр объекта.
     */
    private function __construct() {
        // Сделаем пустой массив related
        $this->related = array();

        // Починим список локалей, если он не указан
        if (!is_array(NamiConfig::$locales)) {
            NamiConfig::$locales = array('ru_RU.UTF-8');
        }
        // Заполним список языков, выберем первый в качестве текущего
        $this->languages = array();
        foreach (NamiConfig::$locales as $locale) {
            $language = new NamiLanguage($locale);
            $this->languages[$language->name] = $language;
            if (!$this->language) {
                $this->language = $language;
            }
        }

        // Получим backend
        $this->backend = new NamiConfig::$db_backend(array
            (
            'host' => NamiConfig::$db_host,
            'port' => NamiConfig::$db_port,
            'name' => NamiConfig::$db_name,
            'user' => NamiConfig::$db_user,
            'password' => NamiConfig::$db_password,
            'charset' => NamiConfig::$db_charset,
        ));

        // Получим mapper
        $this->mapper = new NamiConfig::$db_mapper();
    }

    /**
      Приватная функция клонирования — клонирование недоступно извне
     */
    private function __clone() {
        
    }

    /**
      Проверка наличия модели в списках штормоядра
     */
    function checkModel($classname) {
        if (!in_array($classname, $this->models)) {
            throw new NamiException("'{$classname}' model is not known by NamiCore.");
        }
    }

    /**
      Получение метаданных модели
      Возвращает массив метаданных так, как он выглядит в свежесозданном экземпляре модели
     */
    public function getNamiModelMetadata($classname) {
        // Проверим, нет ли у нас готовой копии метаданных для этой модели
        if (!array_key_exists($classname, $this->metadata) || !$this->metadata[$classname]) {
            //Данных нет, их нужно получить
            $instance = new $classname();

            $this->metadata[$classname] = $instance->meta;
        }

        return $this->metadata[$classname];
    }

    /**
      Получение списка связей типа один-ко-многим, определенных заданной моделью
      Возвращает массив объектов NamiOneToManyRelation
     */
    private function getNamiOneToManyRelations($classname) {
        // Получим поля модели
        $definition = call_user_func(array($classname, 'definition'));

        $relations = array();

        // Идем по полям циклом
        foreach ($definition as $fieldname => $fieldobject) {
            // Поле — ForeignKey?
            if ($fieldobject instanceof NamiFkDbField) {
                $relations[] = new NamiOneToManyRelation($fieldobject->model, $fieldname, $classname, $fieldobject->related);
            }
        }

        return $relations;
    }

    /**
      Получение списка внешних связей модели.
      Возвращает массив, каждый элемент содержит ключи model, field и related.
      Возвращает пустой массив, если связей нет.
     */
    function getRelatedModels($class) {
        if (array_key_exists($class, $this->related) && is_array($this->related[$class]) && count($this->related[$class]) > 0) {
            return $this->related[$class];
        }

        return array();
    }

    /**
      Получение параметров для внешних вызовов
     */
    public function __get($name) {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
    }

    /**
      Синхронизация базы данных
      Принимает массив имен моделей, которые следует синхронизировать
      Выполняется со следующими ограничениями:
      1. Отсутствующие модели создаются.
      2. Отсутствующие поля существующих моделей создаются.
      3. Тип полей не проверяется вообще, нужно пересоздать поле - удаляй его из БД и синхронизируй еще раз.
      4. Индексы создаются автоматически, но не удаляются.
     */
    private function syncdb() {
        // Прогоним событие «перед синхронизацией модели»
        $this->triggerFieldHandler('beforeSync');

        // Получим список имеющихся в БД таблиц
        $tables = $this->mapper->getTableList($this->backend->cursor);

        // Пройдемся по списку моделей и создадим их
        foreach ($this->models as $model) {
            $table = $this->mapper->getModelTable($model);

            // Проверим наличие таблицы
            if (array_search($table, $tables) !== false) {
                // Получим колонки базы данных (которые уже есть) и колонки модели (которые должны быть)
                $dbColumns = $this->mapper->getColumnList($this->backend->cursor, $model);
                $modelColumns = $this->mapper->getModelColumnList($model);

                // Сверим колонки и создадим отсутствующие
                foreach ($modelColumns as $column => $definitionSql) {
                    if (!in_array($column, $dbColumns)) {
                        $this->backend->cursor->execute($definitionSql);
                    }
                }
            } else {
                // Таблицы нет - создадим её
                $this->backend->cursor->execute($this->mapper->getTableCreationSql($model));
            }
        }

        // Пройдемся по списку моделей и создадим индексы
        foreach ($this->models as $model) {
            // Получим желаемые и имеющиеся индексы
            $desired = $this->mapper->getModelIndexes($model);
            $existing = $this->mapper->getIndexList($this->backend->cursor, $model);

            // Сверяем все циклом
            foreach ($desired as $idx) {
                // Несуществующее создаем
                if (!array_key_exists($idx->getName(), $existing)) {
                    $this->backend->cursor->execute($this->mapper->getIndexCreationSql($idx));
                }
            }
        }

        // Пройдемся по моделям и запустим статические методы onSync, если таковые имеются
        foreach ($this->models as $model) {
            if (method_exists($model, '_onSync')) {
                // Аргумент вызова — имя класса, ибо из статического метода его не достать толком
                call_user_func_array(array($model, '_onSync'), array($model));
            }
        }
    }

    function triggerFieldHandler($handler) {
        foreach ($this->models as $model) {
            foreach ($this->getNamiModelMetadata($model)->getFields() as $field) {
                $field->beforeSync();
            }
        }
    }

    function getQuerySet($model) {
        if (property_exists($model, 'queryset_class')) {
            $property = new ReflectionProperty($model, 'queryset_class');
            return $property->getValue();
        }

        if (is_subclass_of($model, 'NamiNestedSetModel')) {
            return 'NamiNestedSetQuerySet';
        }
        if (is_subclass_of($model, 'NamiSortableModel')) {
            return 'NamiSortableQuerySet';
        }
        if (is_subclass_of($model, 'CatalogEntryModel')) {
            return 'CatalogEntryQuerySet';
        }
        return 'NamiQuerySet';
    }

    /**
      Создание функций для быстрого доступа к моделям, NamiQueryCheck-ам и прочим прелестям.
      В этом методе все является читерством и извращением в той или иной степени, но что делать? Унылый PHP уныл.
     */
    function registerUtilities() {

        // Быстрое создание NamiQC
        function Q($params, $arg = null) {
            switch (func_num_args()) {
                case 2: return new NamiQC($params, $arg);
            }
            return new NamiQC($params);
        }

        function QOR($left, $right) {
            return new NamiQCOR($left, $right);
        }

        function QAND($left, $right) {
            return new NamiQCAND($left, $right);
        }

        function QNOT($op) {
            return new NamiQCNOT($op);
        }

        function NamiDebug($debug = true) {
            NamiConfig::$db_debug = $debug ? true : false;
        }

        // Быстрое создание NamiQuerySet
        function NamiQuerySet($model) {
            $classname = NamiCore::getInstance()->getQuerySet($model);
            return new $classname($model);
        }

        $filenameFunctions = dirname(__FILE__) . '/NamiCoreFunctions.php';        
        if (true) { // FIX ME
            // Утилиты для получения моделей и их NamiQuerySet-ов
            $code = '<?php';
            foreach ($this->querysets as $model => $set) {
                // Определим класс NamiQuerySet-а для этой модели
                $queryset = $this->getQuerySet($model);
               
                $code .=
                        "
                function {$model}( \$params = null )
                {
                    return \$params ? new {$model}( \$params ) : new {$model}();
                }

                function {$set}( \$params = null )
                {
                    \$qs = NamiQuerySet( '{$model}' );
                    return \$params ? \$qs->filter( \$params ) :  \$qs;
                }
                ";
            }

            // eval($code);
            file_put_contents($filenameFunctions, $code);
        }
        require_once $filenameFunctions;        
        // eval($code); // :D
    }

}
