<?

/**
  Абстрактная модель базы данных.
 */
abstract class NamiModel {

    private static $defaultValueCache = array();
    public $meta;           // Мета-информация модели
    private $constructed = false;   // Признак сконструированности модели
    private $language; // Язык, в которой создан экземпляр модели
    protected $proxies; // Языковые прокси-объекты
    protected static $fulltextProcessor = null;
    protected $related = array(); // Фабрики связанных queryset-ов

    /**
      Инициализация класса.
      Вызывается автолоадером как только заданный класс где-то упонимается в коде.
      Этот метод играет важную роль в построении связей между зависимыми моделями — регистрирует все модели в ядре
     */

    public static function init($classname = '') {
        // Регистрируем только реальные классы моделей, абстрактный ни к чему
        if ($classname && $classname != 'NamiModel' && $classname != 'NamiNestedSetModel' && $classname != 'NamiSortableModel') {
            NamiCore::getInstance()->checkModel($classname);
        }
    }

    /**
      Конструктор.
      Вызывается при создании, должен рассовать метаинформацию для модели.
      Не предназначен для переопределения, используй метод construct() для имитации конструктора класса в потомках.
     */
    function __construct($params = null) {
        // Запомним текущий язык, будем считать его нашим
        $this->language = NamiCore::getLanguage();

        // Генерируем метаинформацию модели
        $this->meta = new NamiModelMetadata(get_class($this), $this->getDefinition());

        // Спросим у ядра какие модели ссылаются на нас и создадим связанные наборы данных
        foreach (NamiCore::getInstance()->getRelatedModels($this->meta->name) as $r) {
            // Создадим связанный источник данных
            $this->related[$r->related_queryset_name] = new NamiRelatedQuerySetFabric($r->set_model, $r->key_field_name);
        }

        // Модель сконструирована
        $this->constructed = true;

        // Вызовем пользовательский конструктор, если он есть
        if (method_exists($this, 'construct')) {
            $this->construct($params);
        }

        // Установим значения полей по умолчанию и очистим флаги грязных данных
        foreach ($this->meta->getFields() as $name => $field) {
            $languages = $field->localized ? NamiCore::getAvailableLanguages() : array($this->language);
            foreach ($languages as $language) {
                if (!@array_key_exists($language->name, self::$defaultValueCache[get_class($this)][$name])) {
                    self::$defaultValueCache[get_class($this)][$name][$language->name] = $field->getDefaultValue($language);
                }
                if (!@is_null(self::$defaultValueCache[get_class($this)][$name][$language->name])) {
                    $field->setValue(self::$defaultValueCache[get_class($this)][$name][$language->name], $language);
                    $field->markClean();
                }
            }
        }

        // Посмотрим, что за параметры пришли к нам и выполним установку значений полей
        if (!is_array($params)) {
            if ($params)
                $this->meta->setPkValue($params);
        }
        else {
            $this->copyFrom($params, true);
        }

        // Заполним массив проксей, для начала языками
        $this->proxies = array();
        foreach (NamiCore::getAvailableLanguages() as $name => $language) {
            $this->proxies[mb_strtoupper($name)] = $language;
        }
    }

    protected function getLanguageProxy($name) {
        if (!array_key_exists($name, $this->proxies)) {
            throw new NamiException("В модели '{$this->meta->name}' отсутствует языковой прокси '{$name}'.");
        }
        if (!$this->proxies[$name] instanceof NamiModelLanguageProxy) {
            $this->proxies[$name] = new NamiModelLanguageProxy($this, $this->proxies[$name]);
        }
        return $this->proxies[$name];
    }

    /**
      Проверка загруженности данных модели.
      Возвращает true или false.
     */
    function loaded() {
        return $this->meta->getPkValue() > 0;
    }

    /**
      Установка значений полей объекта
     */
    function __set($name, $value) {
        // Объект еще не сконструирован
        if (!$this->constructed) {
            if ($value instanceof NamiQuerySet) {
                $this->{ $name } = $value;
            } else {
                throw new NamiException("Can't set model property '{$name}' in construction phase");
            }
        }
        // Объект сконструирован - выполняем установку значений полей
        else {
            // Если поле с указанным именем у нас есть - устанавливаем его значение
            if ($this->meta->fieldExists($name)) {
                $this->meta->getField($name)->setValue($value);
            }
            // Поля нет - exception
            else {
                throw new NamiException("There is no '${name}' property in {$this->meta->name} model");
            }
        }
    }

    /**
      Получение значения поля
     */
    function & __get($name) {
        // Разрешаем получение имеющихся в объекте полей
        if (property_exists($this, $name)) {
            return $this->{ $name };
        }

        // Выборка полей модели
        if ($this->meta->fieldExists($name)) {
            $return = $this->meta->getField($name)->getValue();
            return $return;
        }

        // Выборка связанных запросов
        if (array_key_exists($name, $this->related)) {
            $queryset = $this->related[$name]->create($this);
            return $queryset;
        }

        // Получение языковых копий
        if (strlen($name) == '2') {
            try {
                $result = & $this->getLanguageProxy($name);
                return $result;
            } catch (Exception $e) {

            }
        }

        // Кастомный обработчик модели
        if (method_exists($this, "__{$name}")) {
            $method = "__{$name}";
            $result = $this->$method();
            return $result;
        }

        // Поля нет - exception
        throw new NamiException("There is no field '$name' in {$this->meta->name} model");
    }

    /**
      Представление объекта в виде строки - generic-овая версия.
      Отображает объект в виде Имя_модели(имя_первичного_ключа: значение_первичного_ключа)
     */
    function __toString() {
        return "{$this->meta->name} ({$this->meta->pkname}: " . ( is_null($this->{ $this->meta->pkname }) ? 'NULL' : $this->{ $this->meta->pkname } ) . ')';
    }

    /**
      Получение объявления полей модели.
      Учитывает поля, объявленные пользователям и служебные поля.
      Возвращает массив объявленных полей.
     */
    protected function getDefinition() {
        $fields = $this->definition();

        if (method_exists($this, '_definition')) {
            $fields = array_merge($fields, $this->_definition());
        }

        if (isset($this->description)) {
            foreach ($this->description as $field_name => $field_data) {
                if (isset($fields[$field_name])) {
                    $fields[$field_name]->title = $field_data['title'];
                }
            }
        }

        return $fields;
    }

    /**
     * Копирование объекта
     * можно переопределять в самой модели описав метод createCopy
     * Модифицирует поле title если оно есть, добавляя слово "__копия"
     * @return array данные о модели. возвращает поля модели(не все, только char)
     */
    public function createCopy() {
        $params = $this->getDataToCopy();
        $new_copy = NamiQuerySet($this->meta->name)->create($params);

        return $new_copy->asArray();
    }

    /**
      Копирование данных модели из переданного массива
      $source — массив или объект с полями, названными так же, как поля модели
      $copy_pk — флаг, копировать или нет поле primary key
     */
    public function copyFrom(array $source, $copy_pk = false) {
        if (!is_array($source))
            throw new NamiException("Cannot read data of {$this->meta->name} model from {$source}");

        foreach ($this->meta->getFields() as $name => $field) {
            // Пропустим ключевое поле
            if (!$copy_pk && $name == $this->meta->pkname)
                continue;
            if (array_key_exists($name, $source)) {
                $field->setValue($source[$name]);
            }
        }

        return $this;
    }

    /**
     * 	Инициализация значений полей данными, выбранными из БД.
     * 	В первую очередь метод предназначен для использования в NamiQuerySet::limit.
     */
    public function setValuesFromDatabase(array $source) {
        foreach ($this->meta->getFields() as $name => $field) {
            if (array_key_exists($name, $source)) {
                if ($field->localized) {
                    foreach (NamiCore::getAvailableLanguages() as $language_name => $language) {
                        $field->setValueFromDatabase($source[$name][$language_name], $language);
                    }
                } else {
                    $field->setValueFromDatabase($source[$name]);
                }
            }
        }
        return $this;
    }

    /**
      Тихое сохранение объекта в БД — не вызываются пользовательские обработчики before и after Save
     */
    public final function hiddenSave() {
        return $this->save(false);
    }

    /**
      Очистка флагов грязных данных
     */
    protected function cleanDirtyData() {
        foreach ($this->meta->getFields() as $field) {
            $field->markClean();
        }
    }

    /**
      Проверка загрязненности данных.
      Можно передать несколько имен полей, метод вернет true, если хотя бы одно поле грязное.
     */
    protected function isDirty($name) {
        foreach (func_get_args() as $name) {
            if ($this->meta->getField($name)->isDirty()) {
                return true;
            }
        }
        return false;
    }

    /**
     *   Пометка переданных полей, как грязных
     *   Принимает одно и более имя поля
     */
    protected function markDirty($name) {
        foreach (func_get_args() as $name) {
            $this->meta->getField($name)->markDirty();
        }
    }

    /**
      Получение данных для сохранения поля в БД
      Возвращает массив.

     */
    protected function getFieldSaveData($field) {
        if (!is_object($field)) {
            $field = $this->meta->getField($field);
        }

        $data = array();

        $field->beforeSave();

        $languages = $field->localized ? NamiCore::getAvailableLanguages() : array($this->language);

        foreach ($languages as $language) {
            $value = $field->getValueForDatabase($language);

            // Поместим экранированное значение в массив данных, имя поля тоже экранируем
            $data[NamiCore::getBackend()->escapeName(NamiCore::getMapper()->getFieldColumnName($field, $language))] = is_null($value) ? 'NULL' : "'" . NamiCore::getBackend()->escape($value) . "'";

            // Если поле полнотекстовое — обновляем его данные
            if ($field->fulltext) {
                if (!is_null($value)) {
                    if (!isset(self::$fulltextProcessor)) {
                        self::$fulltextProcessor = new NamiFulltextProcessor();
                    }
                    $words = self::$fulltextProcessor->get_base_forms($value);
                    $words = NamiCore::getMapper()->fulltext_prepare_words($words);
                    $fts_value = join(' ', $words);
                } else {
                    $fts_value = null;
                }

                $data[NamiCore::getBackend()->escapeName(NamiCore::getMapper()->getFieldFulltextColumnName($field, $language))] = is_null($fts_value) ? 'NULL' : "'" . NamiCore::getBackend()->escape($fts_value) . "'";
            }
        }
        return $data;
    }

    /**
      Сохранение объекта в базе данных
     */
    public function save($callBeforeAfterHandlers = true) {
        // Вызовем обработчик
        if (method_exists($this, '_beforeSave'))
            $this->_beforeSave();
        if ($callBeforeAfterHandlers) {
            if (method_exists($this, 'beforeSave'))
                $this->beforeSave();
        }

        // Проверим, чтобы все NOT NULL поля кроме NamiAutoDbField были заполнены
        foreach ($this->meta->getFields() as $name => $field) {
            if (!$field instanceof NamiAutoDbField && !$field->null && is_null($field->getValue())) {

                //Если поле описано в description модели выводим это
                if (property_exists($this, "description") && isset($this->description[$name])) {
                    throw new NamiValidationException("Поле '{$this->description[$name]['title']}' не может быть пустым", $field);
                } else {
                    throw new NamiValidationException("Поле '{$name}' не может быть пустым", $field);
                }
            }
        }

        // Получим имя таблицы
        $table = NamiCore::getBackend()->escapeName(NamiCore::getMapper()->getModelTable($this->meta->name));

        // Получим имя и значение первичного ключа
        $pkname = $this->meta->pkname;
        $pkvalue = $this->meta->getPkValue();

        // флаг вставки новой записи
        $update_done = false;

        // Массив данных модели
        $data = array();

        // Значение ключевого поля задано - обновляем БД. Есть вероятность, что ключ задали от балды и обновление ничего не даст
        if (!is_null($pkvalue)) {
            // Сохраняем только грязные данные
            foreach ($this->meta->getFields() as $field) {
                if ($field->isDirty()) {
                    $data = array_merge($this->getFieldSaveData($field), $data);
                }
            }

            // Теперь интересный момент — если нет грязных данных, то прийдется проверить существование записи
            if ($data) {
                // Сконструируем запрос
                $expressions = array();
                foreach ($data as $k => $v) {
                    $expressions[] = "$k=$v";
                }
                $query = "UPDATE {$table} SET " .
                        join(', ', $expressions) .
                        " WHERE " .
                        NamiCore::getBackend()->escapeName($pkname) . "='" . NamiCore::getBackend()->escape($pkvalue) . "'";

                // Affected rows устанавливается только если имеет место _именно_ изменение данных, то есть заданы новые значения. Поэтому 0 не означает, что такой записи нет.
                // Единственное, что можно достоверно сказать — если вернули 1, то обновление точно произошло
                if (NamiCore::getBackend()->cursor->execute($query)) {
                    $update_done = true;
                }
            }

            if (!$update_done) {
                // Не прошел запрос, или данные не менялись. Так или иначе проверим наличие записи в таблице.
                NamiCore::getBackend()->cursor->execute("SELECT " . NamiCore::getBackend()->escapeName($pkname) . " FROM {$table} WHERE " . NamiCore::getBackend()->escapeName($pkname) . "='" . NamiCore::getBackend()->escape($pkvalue) . "'");
                // Если выбрана запись — данные просто не были изменены, все в порядке
                if (count(NamiCore::getBackend()->cursor->fetchAll()) > 0) {
                    $update_done = true;
                }
            }
        }

        // Если обновление прошло — вызываем обработчик и заканчиваем работу
        if ($update_done) {
            // Вызовем обработчик
            if (method_exists($this, '_afterSave'))
                $this->_afterSave(false);
            if ($callBeforeAfterHandlers) {
                if (method_exists($this, 'afterSave'))
                    $this->afterSave(false);
            }

            // Почистим флаги грязных данных, теперь все данные чисты
            $this->cleanDirtyData();

            return $this;
        }

        // Если мы попали сюда - апдейт либо не планировался, либо не прошел
        // В любом случае, нужно выполнить insert, а значение primary key нужно установить после этого
        if (!is_null($pkvalue)) {
            // Добавим pk в массив данных
            $data = array_merge(array(NamiCore::getBackend()->escapeName($pkname) => "'" . NamiCore::getBackend()->escape($pkvalue) . "'"), $data);
        }

        // Дозаполним данные, пропуская уже заполненные (из грязных)
        foreach ($this->meta->getFields() as $name => $field) {
            // Пропустим ключевое поле
            if ($name == $this->meta->pkname)
                continue;

            // Пропустим грязное поле, если была попытка сохранять
            if (!is_null($pkvalue) && $field->isDirty()) {
                continue;
            }

            // Добавляем данные
            $data = array_merge($this->getFieldSaveData($field), $data);
        }

        // Сконструируем INSERT-запрос
        $query = "INSERT INTO $table (" .
                join(", ", array_keys($data)) . ") VALUES (" .
                join(", ", $data) . ")";

        // выполним запрос
        NamiCore::getBackend()->cursor->execute($query);

        // Если значение PK не было установлено - прочитаем его из last insert id БД
        if (is_null($pkvalue)) {
            $this->meta->setPkValue(NamiCore::getBackend()->getLastInsertId(NamiCore::getBackend()->cursor, NamiCore::getMapper()->getModelTable($this->meta->name), $pkname));
        }

        // Вызовем обработчик
        if (method_exists($this, '_afterSave'))
            $this->_afterSave(true);
        if (method_exists($this, 'afterSave'))
            $this->afterSave(true);

        // Данные чисты
        $this->cleanDirtyData();

        return $this;
    }

    /**
      Удаление записи из БД
     */
    public function delete() {
        // Вызовем обработчик
        if (method_exists($this, '_beforeDelete'))
            $this->_beforeDelete();
        if (method_exists($this, 'beforeDelete'))
            $this->beforeDelete();

        // Получим имя и значение первичного ключа
        $pkname = NamiCore::getBackend()->escapeName($this->meta->pkname);
        $pkvalue = $this->meta->getPkValue();

        // Проверим, чтобы ключевое поле было заполнено
        if (is_null($pkvalue))
            throw new NamiException("Primary key is not set, cannot delete a record");

        // Пройдемся по моделям, которые имеют нас в качестве foreign key и выполним очистку
        // Сначала - проверим все связи на not null
        foreach (NamiCore::getInstance()->getRelatedModels($this->meta->name) as $r) {
            // Посмотрим, может ли наше поле быть NULL
            $meta = NamiCore::getInstance()->getNamiModelMetadata($r->set_model);
            if (!$meta->getField($r->key_field_name)->null) {
                // Поле NOT NULL, посмотрим, есть ли записи
                if ($this->{ $r->related_queryset_name }->count() > 0) {
                    throw new NamiException("Cannot delete instance of {$this->meta->name} because it is referenced by {$meta->name} instance with NOT NULL constraint");
                }
            }
        }

        // Связи проверены, можно смело все удалить и обнулить
        foreach (NamiCore::getInstance()->getRelatedModels($this->meta->name) as $r) {
            $this->{ $r->related_queryset_name }->clear();
        }

        // Получим имя таблицы
        $table = NamiCore::getBackend()->escapeName(NamiCore::getMapper()->getModelTable($this->meta->name));

        // Выполним запрос удаления нашей записи
        NamiCore::getBackend()->cursor->execute("DELETE FROM $table WHERE {$pkname}=%{pk}", array('pk' => $pkvalue));

        // Удаление не прошло - exception
        if (!NamiCore::getBackend()->cursor->rowcount > 0) {
            throw new NamiException("There is no {$this->meta->name} with {$pkname} = {$pkvalue} in the database");
        }

        // Выполним действия полей по удалению записи
        foreach ($this->meta->getFields() as $key => $field) {
            $field->beforeDelete();
        }

        // Вызовем обработчик
        if (method_exists($this, '_afterDelete'))
            $this->_afterDelete();
        if (method_exists($this, 'afterDelete'))
            $this->afterDelete();

        // Данные чисты
        $this->cleanDirtyData();

        return $this;
    }

    /**
      Получение QuerySet-а объектов этой модели
     */
    function getQuerySet() {
        return new NamiQuerySet($this->meta->name);
    }

    /**
      Получение данных объекта в виде массива.
      Может быть использовано для ajax-ответов и т.п., поэтому по умолчанию не включает большие по объему поля БД.
      Возвращает массив с ключами — именами полей, значения — строки или null.
      Ссылки на связанные модели заменяются идентификаторами.
     */
    function asArray($full = false) {
        $result = array();

        foreach ($this->meta->getFields() as $name => $field) {
            if ($full || !$field instanceof NamiTextDbField) {
                if ($field->localized) {
                    foreach (NamiCore::getAvailableLanguages() as $language) {
                        $key = mb_strtoupper($language->name);
                        if (!array_key_exists($key, $result)) {
                            $result[$key] = array();
                        }
                        $value = $field->asArrayElement($full, $language);
                        if ($value instanceof NamiModel)
                            $value = $value->meta->getPkValue();
                        $result[$key][$name] = $value;
                    }
                }
                $value = $field->asArrayElement($full);
                if ($value instanceof NamiModel)
                    $value = $value->meta->getPkValue();
                $result[$name] = $value;
            }
        }

        return $result;
    }

    /**
     * Получение данных для копирования.
     * метод asArray для копирования не подходит, т.к. некоторые поля
     * при создании требуют особых подход.
     */
    function getDataToCopy() {
        $result = array();

        foreach ($this->meta->getFields() as $name => $field) {

            //поле файл или картинка
            if ($field instanceof NamiFileDbField) {
                $img_values = $field->value->get();
                if ($img_values) {
                    $result[$name] = $img_values->uri;
                }

                continue;
            }

            //копирование поля с типом массив
            //TODO проверка на тип поля не совсем корректная.
            //нужно исправить на более точный метод
            if ($field instanceof NamiArrayDbField) {

                $array_values = $field->asArrayElement();
                $new_values = array();

                if (count($array_values)) {
                    foreach ($array_values as $value) {


                        //img value
                        if (property_exists($value, "original")) {
                            $new_values[] = $value->original->uri;
                        }

                        //files value
                        if (property_exists($value, "uri") && property_exists($value, "size")) {
                            $new_values[] = $value->uri;
                        }
                    }
                }

                $result[$name] = $new_values;
                continue;
            }

            if ($field->localized) {
                foreach (NamiCore::getAvailableLanguages() as $language) {
                    $key = mb_strtoupper($language->name);
                    if (!array_key_exists($key, $result)) {
                        $result[$key] = array();
                    }

                    $value = $field->asArrayElement(true, $language);
                    if ($value instanceof NamiModel)
                        $value = $value->meta->getPkValue();
                    $result[$key][$name] = $value;
                }
            }
            $value = $field->asArrayElement(true);

            $result[$name] = $value;
        }

        unset($result['id']);
        if (isset($result['title'])) {
            $result['title'] = $result['title'] . "__копия";
        }

        return $result;
    }

    function toTraceString() {
        return get_class($this) . "[ " . $this->meta->getPkValue() . " ]";
    }

    /**
     *   Загрузка NamiQueryEntity в качестве собственного поля.
     *   $entity - экземпляр NamiQueryEntity.
     *   $data - массив данных, заботливо загруженный NamiQuerySet'ом.
     */
    function loadQueryEntity(NamiQueryEntity $entity, $data) {
        $steps = $entity->path;
        $fieldname = array_pop($steps);

        // Циклом идем по пути в $steps, записывая в $instance очередную модель в пути
        $instance = $this;
        foreach ($steps as $next) {
            $field = $instance->meta->getField($next);
            $instance = & $field->getValue();
            if (!$instance) {
                // Поле заполненно NULL'ом, дальше идти некуда и заполнять нечего
                return;
            }
        }

        if ($instance) {
            // Остался последний шаг - заполнить поле текущего $instance
            $field = $instance->meta->getField($fieldname);
            // Проверим, что мы пришли к полю  FkDbField и модель у тего такая же, как в $entity
            if ($field->model != $entity->model) {
                throw new NamiException("QueryEntity loading error: got {$field->model} instead {$entity->model}");
            }

            // Проверим, что у нас есть нужные данные (PK)
            $meta = NamiCore::getInstance()->getNamiModelMetadata($entity->model);
            if ($data[$meta->pkname]) {
                // Создадим экземпляр конечной модели и запишем его в качестве значения поля
                $value = new $entity->model();
                $value->setValuesFromDatabase($data);
                $field->setValue($value);
            }
        }
    }

}
