<?

/**
 * Поле типа varchar
 */
class NamiCharDbField extends NamiDbField {

    protected $maxlength; // Максимальная длина поля
    protected $localized = false;

    /**
     * Конструктор.
     * Проверяет параметры, касающиеся настроек поля
     */
    function __construct(array $params) {
        // Проверим длину поля
        if (!( isset($params['maxlength']) && $params['maxlength'] > 0 )) {
            throw new NamiException("Must specify maxlength as positive integer");
        }

        // Вызовем родительский конструктор, он прочитает все настройки
        parent::__construct($params);
    }

    /**
     * Установка значения поля
     * @return
     * @param object $string
     */
    function setValue($string, $language = null) {
        // Проверяем значение, нехорошее значение вызывает exception
        if (mb_strlen($string, 'utf-8') > $this->maxlength) {
            $msg = "Максимальное количество символов в поле «" . $this->title . "» — " . $this->maxlength . " (" . Meta::decline(mb_strlen($string, 'utf-8') - $this->maxlength, "", "%n лишний", "%n лишних", "%n лишних") . ")";
            throw new NamiValidationException($msg, $this);
        }

        return parent::setValue($string, $language);
    }

}
