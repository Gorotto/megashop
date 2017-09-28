<?

/**
  Поле дата+время
 */
class NamiTimeDbFieldValue extends NamiDbFieldValue {

    protected $format = '%H:%M'; // Формат отображения даты, может быть только    %H, %M, %S

    /**
     *    Конструктор.
     *    Принимает параметры от конструктора поля, заполняет всякие штуки
     */
    function __construct(array $params = array()) {
        if (array_key_exists('format', $params)) {
            $this->format = $params['format'];
        }
    }

    // Нормальная работа в качестве поля таблицы
    function get() {
        return is_null($this->value) ? null : $this;
    }

    // Нормальная работа в качестве поля таблицы
    function set($value) {
        if (is_null($value) || $value === '') {
            $this->value = null;
        } else if (is_numeric($value)) {
            $this->value = (int) $value;
        } else {
            $this->value = NamiTimeDbField::parseValue($value);
        }

        return $this;
    }

    // Для сохранения значения в БД
    function getForDatabase() {
        return NamiTimeDbField::getDbRepresentation($this->value);
    }

    // Для инициализации значением из БД
    function setFromDatabase($value) {
        $this->value = is_null($value) ? null : NamiTimeDbField::parseValue($value);
        return $this;
    }

    // Получение значения в виде отдельного объекта упрощенного вида, например перевода в JSON
    function getSimplified($short = false) {
        return is_null($this->value) ? null : $this->format($this->format);
    }

    /**
     *   Форматирование времени по переданному формату
     *   Поддерживает placeholders %H %M %S
     */
    function format($format) {
        if (is_null($this->value)) {
            return null;
        }

        $parts = array();
        $parts['H'] = sprintf('%02d', ($this->value / 3600));
        $parts['M'] = sprintf('%02d', (($this->value % 3600) / 60));
        $parts['S'] = sprintf('%02d', $this->value % 60);

        return preg_replace('~(?<!%)%([HMS])~e', '$parts["$1"]', $format);
    }

    function __toString() {
        return is_null($this->value) ? '' : $this->format($this->format);
    }

}
