<?

//    Абстрактная модель для реализации каталога с настраиваемыми наборами полей.                                          
//    Структура данных выглядит следующим образом (несущественные поля моделей опущены):                                   
//                                                                                                                         
//      FieldType                         Хранит список типов полей, типа «Строка», «Изображение», «Число» и все такое.    
//    +-------------+                                                                                                      
//    | id          |-+ 1                                                                                                  
//    +-------------+ |                                                                                                    
//    |storage_model| |                   Это поле содержит имя модели, в которой хранится значение соответствующего типа. 
//    +-------------+ |                   Для разных типов созданы разные модели, все - потомки CatalogEntryAbstractValue. 
//                    |                                                                                                    
//                    |                                                                                                    
//      Field         |                   Хранит список полей.                                                             
//    +-------------+ |                                                                                                    
//    |             |-|-----------+ 1                                                                                      
//    | id          |-|---+ 1     |                                                                                        
//    +-------------+ |   |       |                                                                                        
//    | title       | |   |       |   	Название, например «Цена».                                                       
//    | type        |-+ N |       |       Тип поля.                                                                        
//    | name        |     |       |       Имя, под которым это поле будет фигурировать в модели Entry.                     
//    +-------------+     |       |                                                                                        
//                        |       |                                                                                        
//                        |       |                                                                                        
//      FieldSet          |       |   	Наборы полей. Например, «Обувь» или «Автомобиль».                                
//    +-------------+     |       |                                                                                        
//    |             |-----|---+ 1 |                                                                                        
//    | id          |-+ 1 |   |   |                                                                                        
//    +-------------+ |   |   |   |                                                                                        
//    | title       | |   |   |   |       Название, например «Обувь».                                                      
//    +-------------+ |   |   |   |                                                                                        
//                    |   |   |   |                                                                                        
//                    |   |   |   |                                                                                        
//      FieldSetField |   |   |   |       Привязка поля к набору.                                                          
//    +-------------+ |   |   |   |                                                                                        
//    | id          | |   |   |   |                                                                                        
//    +-------------+ |   |   |   |                                                                                        
//    | fieldset    |-+ N |   |   |                                                                                        
//    | field       |-----+ N |   |                                                                                        
//    | filter_mode |         |   |       Настройки фильтрации по этому полю (не чистый many-to-many)                      
//    +-------------+         |   |                                                                                        
//                            |   |                                                                                        
//                            |   |                                                                                        
//      CatalogCategory       |   |       Категория каталога, которой принадлежат позиции.                                 
//    +-------------+         |   |                                                                                        
//    | id          |-+ 1     |   |                                                                                        
//    +-------------+ |       |   |                                                                                        
//    | fieldset    |-|-------+ N |       Набор полей, который будет использоваться позициями этой категории.              
//    +-------------+ |           |                                                                                        
//                    |           |                                                                                        
//                    |           |                                                                                        
//      CatalogEntry  |           |   	Позиция каталога. В самой модели есть только общие поля - title, enabled.        
//    +-------------+ |           |                                                                                        
//    | id          |-|---+ 1     |                                                                                        
//    +-------------+ |   |       |                                                                                        
//    | category    |-+ N |       |   	Категория, через ее fieldset будут вытащены дополнительные поля позиции.         
//    +-------------+     |       |                                                                                        
//                        |       |                                                                                        
//                        |       |                                                                                        
//      CatalogEntry-     |       |       Значения строковых полей позиций. Аналогичные модели есть для Date,              
//      StringValue       |       |       Image, Boolean и остальных типов значений.                                       
//    +-------------+     |       |                                                                                        
//    | id          |     |       |       Имя модели значений определяется полем field_type.value_class.                   
//    +-------------+     |       |                                                                                        
//    | entry       |-----+ N     |       Собственно позиция.                                                              
//    | field       |-------------+ N     Поле, значение которого храним.                                                  
//    | value       |                     Хранимое значение указанного поля для указанной позиции :)                       
//    +-------------+                     Для каждой модели подобного класса тип поля value соответствует хранимым данным, 
//                                        а поле value как правило проиндексировано.                                       


abstract class CatalogEntryModel extends NamiModel {

    // Тут будем хранить загруженные поля
    protected $fieldset = null;
    protected $extra_fields = array();
    protected static $fieldsets = array();

    /**
     *   Получение списка полей указанной категории
     */
    protected static function getCategoryFieldset($category) {
        $cache_size = 20;

        if ($category && $category->id && $category->fieldset && $category->fieldset->id) {
            if (array_key_exists($category->fieldset->id, self::$fieldsets) && array_key_exists('__loaded__', self::$fieldsets[$category->fieldset->id])) {
                return self::$fieldsets[$category->fieldset->id];
            } else {
                $fieldset_fields = $category->fieldset->fields->follow(2)->all();

                if ($fieldset_fields) {
                    $fieldset = array();

                    foreach ($fieldset_fields as $i) {
                        $fieldset[$i->field->name] = $i->field;
                    }

                    self::$fieldsets[$category->fieldset->id] = $fieldset;

                    if (count(self::$fieldsets) > $cache_size) {
                        array_shift(self::$fieldsets);
                    }

                    return $fieldset;
                }
            }
        }
        return null;
    }

    /**
     *   Получение поля набора полей
     */
    public static function getFieldsetField($fieldset_id, $field_name) {
        return array_key_exists($fieldset_id, self::$fieldsets) &&
            array_key_exists($field_name, self::$fieldsets[$fieldset_id]) ? self::$fieldsets[$fieldset_id][$field_name] : null;
    }

    /**
     *   Получение списка дополнительных полей.
     *   Возвращает ассоциативный массив объектов CatalogField, ключ - имя поля.
     */
    function getFieldset() {
        // Если поля еще не загружены — попробуем загрузить
        if (!$this->fieldset) {
            $this->fieldset = self::getCategoryFieldset($this->category);
        }
        return $this->fieldset ? $this->fieldset : array();
    }

    /**
     *   Получение массива имен дополнительных полей
     */
    function getExtraFieldNames() {
        return array_keys($this->getFieldset());
    }

    /**
     *   Проверка существования дополнительного поля с указанным именем.
     */
    function extraFieldExists($name) {
        return $this->hasProperty($name);
    }

    /**
     *   Проверка наличия свойства (дополнительного поля) модели
     *   $name - имя дополнительного поля
     */
    function hasProperty($name) {
        // Идентификатор набора полей нашей категории
        $fid = $this->category->fieldset->id;

        /*  Наборы полей могут быть загружены не полностью, поэтому
          в каждом филдсете есть ключ 'loaded', который устанавливается
          только после полной загрузки набора полей из базы. */
        if (array_key_exists($fid, self::$fieldsets)) {
            if (array_key_exists($name, self::$fieldsets[$fid])) {
                return self::$fieldsets[$fid][$name] ? true : false;
            } elseif (array_key_exists('__loaded__', self::$fieldsets[$fid])) {
                return false;
            }
        } else {
            self::$fieldsets[$fid] = array();
        }

        // Подгружаем набор полей и складываем в кеш
        foreach (CatalogFieldsetFields(array('fieldset' => $fid))->follow(2)->all() as $i) {
            self::$fieldsets[$fid][$i->field->name] = $i->field;
        }
        self::$fieldsets[$fid]['__loaded__'] = true;

        return array_key_exists($name, self::$fieldsets[$fid]);
    }

    static public function cacheFieldLinks($fieldsets) {
        foreach ($fieldsets as $id => $fields) {
            if (!array_key_exists($id, self::$fieldsets)) {
                self::$fieldsets[$id] = array();
            }
            foreach ($fields as $name => $field) {
                if (!array_key_exists($name, self::$fieldsets[$id])) {
                    self::$fieldsets[$id][$name] = $field;
                }
            }
        }
    }

    protected function getField($name) {
        if ($this->hasProperty($name)) {
            return self::$fieldsets[$this->category->fieldset->id][$name];
        }
        return null;
    }

    /**
     *   Получение дополнительного поля по имени.
     *   Возвращает объект класса-потомка CatalogEntryAbstractValue.
     *   При работе с дополнительными полями следует помнить, что каждое из них представлено
     *   объектом-моделью ORM, а не обычным значением.
     */
    function getExtraField($name) {
        // Если такое поле уже инициализировано - вернем его
        if (array_key_exists($name, $this->extra_fields)) {
            return $this->extra_fields[$name];
        }

        $fieldset = $this->getFieldset();

        // Проверим наличие такого поля в модели
        if (!array_key_exists($name, $fieldset)) {
            throw new NamiException("Unknown extra field '$name' or too early field usage");
        }

        $field = $fieldset[$name];
        $value = null;

        // Определим класс модели
        $model = $field->field_type->storage_model;

        // Попробуем выбрать существующее значение из БД        
        if ($this->id > 0) {
            $value = NamiQuerySet($model)->get(array('entry' => $this, 'field' => $field));
        }

        // Существующего значения нет - создадим нужный объект самостоятельно
        if (!$value) {
            $value = new $model(array('entry' => $this, 'field' => $field));
            if (!is_null($field->default)) {
                $value->value = $field->default;
            }
        }

        // Сохраним на будущее
        $this->extra_fields[$name] = $value;

        return $value;
    }

    /**
     *   Запись экстра поля
     *   Если value не указано - создается пустое value
     */
    function putExtraField($name, $value = null) {
        if (is_null($value)) {
            $fieldset = $this->getFieldset();
            $field = $fieldset[$name];
            $model = $field->field_type->storage_model;
            $value = new $model(array('entry' => $this, 'field' => $field));
            if (!is_null($field->default)) {
                $value->value = $field->default;
            }
        }
        $this->extra_fields[$name] = $value;    // Problems officer? :D
    }

    /**
     *   Получение всех дополнительных полей 
     *   в виде ассоциативного массива объектов-моделей.
     */
    function getExtraFields() {
        $fields = array();
        foreach ($this->getExtraFieldNames() as $name) {
            $fields[$name] = $this->getExtraField($name);
        }
        return $fields;
    }

    /**
     *   Запись значения поля
     */
    function __set($name, $value) {
        try {
            return parent::__set($name, $value);
        } catch (NamiException $e) {
            if ($this->extraFieldExists($name)) {
                $this->getExtraField($name)->value = $value;
                return true;
            }
        }
        throw new NamiException("There is no '${name}' property in {$this->meta->name} model");
    }

    /**
     *   Получение значения поля
     */
    function & __get($name) {
        try {
            return parent::__get($name);
        } catch (NamiException $e) {
            if ($this->extraFieldExists($name)) {
                $value = $this->getExtraField($name)->value;
                return $value;
            }
        }
        throw new NamiException("There is no field '$name' in {$this->meta->name} model");
    }

    /**
     *   Копирование данных из переданного источника
     */
    public function copyFrom(array $source, $copy_pk = false) {
        parent::copyFrom($source, $copy_pk);

        foreach ($this->getExtraFields() as $name => $field) {
            if (array_key_exists($name, $source)) {
                $field->value = $source[$name];
            }
        }

        return $this;
    }

    /**
     *   Представление модели в виде массива.
     *   TODO: добавить обработку многоязычных значений, так же как в NamiModel
     */
    function asArray($full = false) {
        $result = parent::asArray($full);

        foreach ($this->getExtraFields() as $name => $field) {
            $result[$name] = $field->value_object->asArrayElement($full);
        }

        return $result;
    }

    /**
     *   Сохранение модели в бд
     */
    public function save($callBeforeAfterHandlers = true) {
        parent::save($callBeforeAfterHandlers);

        foreach ($this->getExtraFields() as $field) {
            $field->save();
        }

        return $this;
    }

    public function createCopy() {
        $params = $this->getDataToCopy();
        $new_copy = NamiQuerySet($this->meta->name)->create($params);

        foreach ($this->getExtraFieldNames() as $name) {
            $field = $this->getExtraField($name);
            $new_copy->$name = $field->value;
        }

        $new_copy->hiddenSave();

        return $new_copy->asArray();
    }

    /**
     *   Удаление модели из бд
     */
    public function delete() {
        parent::delete();

        foreach ($this->getExtraFields() as $field) {
            try {
                $field->delete();
            } catch (NamiException $e) {
                // Пропускаем exception, потому что часть полей может быть не заполнена, и их удаление обломится
            }
        }

        return $this;
    }

    /**
     *   Загрузка сущности запроса
     */
    function loadQueryEntity(NamiQueryEntity $entity, $data) {
        // Обычные entity имеют модель, их загружаем как обычно
        if ($entity->model) {
            return parent::loadQueryEntity($entity, $data);
        } else {
            /*  Здесь выполняется загрузка данных, выбранных хитрым join-ом в QuerySet-е.
              Выбранные поля могут быть не у всех записей каталога, поэтому нужно проверять на NULL.
              Модель для этих полей заботливо подготовлена в поле $data['model'] */
            $field_name = $entity->path[0];

            if ($this->hasProperty($field_name)) {
                $field = $this->getField($field_name);
                if (!$field) {
                    print 'omg';
                    var_dump($field);
                }


                $model = $field->field_type->storage_model;
                $value = new $model();
                if (!is_null($data[$value->meta->pkname])) {
                    $value->setValuesFromDatabase($data);
                } else {
                    $value->copyFrom(array('entry' => $this, 'field' => $field));
                }
                $this->extra_fields[$field_name] = $value;
            }
        }
    }

}
