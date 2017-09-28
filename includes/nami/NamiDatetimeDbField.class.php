<?

/**
  Поле типа varchar
 */
class NamiDatetimeDbField extends NamiDbField {

    protected $valueClassname = 'NamiDatetimeDbFieldValue'; // Имя класса значений

    static function parseValue($value) {
        if (!is_null($value) && $value !== '') {
            if (is_numeric($value))
                return (int) $value;

            if (( $time = strtotime($value) ) !== false)
                return $time;

            throw new NamiValidationException("Value '{$value}' is not valid Datetime value");
        }

        return null;
    }

    /**
      Получение значения, отформатированного для запроса
     */
    static public function getCheckOpValue($value) {
        if ($value = self::parseValue($value)) {
            return strftime('%Y-%m-%d %H:%M:%S', $value);
        } else {
            return $value;
        }
    }

}
