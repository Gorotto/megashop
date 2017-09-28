<?

class NamiArrayDbFieldValue extends NamiDbFieldValue {

    protected $dropped = array(); // Отброшенные в процессе работы поля
    protected $type;        // Тип полей в массиве (класс-потомок NamiDbField)
    protected $db_field;    // Ссылка на поле, которому принаддежит значение. Нужно для установки грязноты поля.
    protected $params;      // Параметры, передающиеся в хранимые поля при инициализации.

    /**
     *   Конструктор
     */

    function __construct(array $params = array()) {
        // Проверим и скопируем параметры type и db_field
        foreach (array('type', 'db_field') as $k) {
            if (!array_key_exists($k, $params)) {
                throw new NamiException("Missing '$k' constructor argument");
            }
            $this->$k = $params[$k];
            unset($params[$k]);
        }

        // Проверим type
        if (!is_subclass_of($this->type, 'NamiDbField')) {
            throw new NamiException("type must be a NamiDbField subclass");
        }

        $this->params = $params;

        $this->value = array();
    }

    /**
     *   Нормальная работа в качестве поля таблицы
     */
    function get() {
        // Возвращаем фронтэнд к внутреннему массиву значений, он значет что с ними делать и имеет нужный интерфейс
        return new NamiArrayDbFieldFrontend($this);
    }

    /**
     *   Создание поля для хранения значения.
     *   Полученные таким образом поля будут храниться в массиве value.
     *   Для этих полей нужно будет эмулировать окружение так,
     *   чтобы каждое из них думало, что оно лежит в базе данных :3
     *   Так можно сорвать все профиты со сторонних эффектов полей
     *   типа удаления файлов.
     */
    function createValueField() {
        $class = $this->type;
        return new $class($this->params);
    }

    /**
     *   Запись значений при присваивании значений поля объекта модели.
     */
    function set($values) {
        if (is_null($values) || $values === '') {
            foreach ($this->value as $field) {
                $this->dropped[] = $field;
            }
            $this->value = array();
        } else if (is_array($values)) {
            $this->dropped = array_merge($this->dropped, $this->value);
            $this->value = array();

            // Запишем имеющиеся поля массива, если не хватает элементов - создадим
            foreach (array_values($values) as $i => $value) {
                $this->value[$i] = $this->createValueField();
                $this->value[$i]->setValue($value);
            }
        } else {
            throw new NamiException("Cannot assign value '{$value}' to NamiArrayDbFieldValue. Accepts arrays, nulls and empty strings only.");
        }

        $this->db_field->markDirty();

        return $this;
    }

    /**
     * Удаление значений полей
     */
    function beforeDelete() {
        if (count($this->value)) {
            $db_values = array();
            foreach ($this->value as $value) {
                //вызов обработчика
                $value->beforeDelete();
            }
        }
    }

    /**
     *   Получение значения для сохранения в базу данных.
     */
    function getForDatabase($language = null) {
        /*  Принцип работы прост — сделаем вид, что каждое из полей в массиве сохраняется в БД,
          получим его значение и все это дело сериализуем в виде JSON-массива.
          Есть один тонкий момент — в процессе работы поля могли появиться отброшенные значения,
          которые раньше были в массиве, но теперь они не нужны.
          Для этих значений нужно прогнать цикл установки значения в null и так же сделать вид,
          что они сохраняются в БД, чтобы поля могли выполнить всю свою работу. */
        $db_values = array();

        // Если есть значения - сереализуем их в JSON-массив
        if (count($this->value)) {
            if (count($this->value) > 100) {
                $msg = "Максимальное количество элементов в поле «" . $this->db_field->title . "» — 100 (" . Meta::decline(count($this->value) - 100, "", "%n лишний", "%n лишних", "%n лишних") . ")";
                throw new NamiException($msg);
            }

            $db_values = array();
            foreach ($this->value as $value) {
                $value->beforeSave();
                $db_values[] = $value->getValueForDatabase();
            }
        }

        /*  Теперь обрабатываем отброшенные значения. Важно сделать это после обработки
          сохраненных значений, так как отброшенные могли предоставлять сохраненным свои данные
          (например, изображения) */
        foreach ($this->dropped as $field) {
            $field->setValue(null);
            $field->beforeSave();
            $field->getValueForDatabase();
        }

        // Если массив пуст, храним его в виде null
        return count($db_values) ? json_encode($db_values) : null;
    }

    /**
     *   Для инициализации значением из БД
     */
    function setFromDatabase($value) {
        $this->value = array();

        if (!is_null($value)) {
            $values = json_decode($value);

            // Запишем имеющиеся поля массива, если не хватает элементов - создадим
            foreach (array_values($values) as $i => $value) {
                if (!array_key_exists($i, $this->value)) {
                    $this->value[$i] = $this->createValueField();
                }
                $this->value[$i]->setValueFromDatabase($value);
            }

            // Если массив, который записали короче, чем был до этого - удалим ненужные значения
            if (count($this->value) > count($values)) {
                $unwanted = array_splice($this->value, count($values));
                foreach ($unwanted as $field) {
                    $this->dropped[] = $field;
                }
            }
        }

        return $this;
    }

    /**
     *   Получение значения в виде отдельного объекта упрощенного вида, например перевода в JSON
     */
    function getSimplified($short = false) {
        $values = array();
        foreach ($this->value as $field) {
            $values[] = $field->asArrayElement(!$short);
        }
        return $values;
    }

    /**
     *   Установка значения по указанному смещению.
     *   Не возвращает ничего, выбрасывает NamiException, если $offset не существует.
     *   Если $offset == null, добавляет значение в массив.
     */
    public function offsetSet($offset, $value) {
        /*  Поддерживаем только числовые индексы, ассоциативность не одобряем,
          потому что ее сложно будет редактировать через JS */
        if (is_null($offset)) {
            $field = $this->createValueField();
            $field->setValue($value);
            $this->value[] = $field;
        } else {
            if (is_int($offset)) {
                $this->value[$offset]->setValue($value);
            } else {
                throw new NamiException("Invalid offset '$offset'. Only integer offsets are supported.");
            }
        }

        $this->db_field->markDirty();
    }

    /**
     *   Проверка существования указанного смещения $offset
     */
    public function offsetExists($offset) {
        return array_key_exists($offset, $this->value);
    }

    /**
     *   Удаление указанного смещения.
     */
    public function offsetUnset($offset) {
        if (array_key_exists($offset, $this->value)) {
            $this->dropped[] = $this->value[$offset];
            unset($this->value[$offset]);
            $this->db_field->markDirty();
        } else {
            throw new NamiException("Invalid offset '$offset'");
        }
    }

    /**
     *   Получение значение по смещению $offset.
     *   При отсутствии возвращает null.
     */
    public function offsetGet($offset) {
        return array_key_exists($offset, $this->value) ? $this->value[$offset]->getValue() : null;
    }

    /**
     *   Получение количества элементов в массиве.
     */
    function getLength() {
        return count($this->value);
    }

}
