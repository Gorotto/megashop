<?
/**
*   Поле типа time
*/
class NamiTimeDbField extends NamiDbField {
	protected $valueClassname = 'NamiTimeDbFieldValue'; // Имя класса значений

    /**
    *   Приведение значения во внутренний формат - количество секунд
    *   Возвращает целое число, null или выбрасывает NamiValidationException
    */
    static public function parseValue($value) {
        if (!is_null($value) && $value !== '') {
            if (is_numeric($value)) {
                return (int)$value;
            }
            
            if (preg_match('~^(\d+):(\d+)(?::(\d+))?$~', $value, $matches)) {
                if (!array_key_exists(3, $matches)) {
                    $matches[3] = 0;
                }
                
                if ($matches[2] < 60 && $matches[3] < 60) {
                    return (int)$matches[1] * 3600 + (int)$matches[2] * 60 + (int)$matches[3];
                }
            }

            throw new NamiValidationException("Invalid time value '{$value}'");
        }
        
        return null;
    }
    
    /**
    *   Перевод в представление БД
    */
    static public function getDbRepresentation($value) {
        $parsed = self::parseValue($value);
        
        if ($parsed) {
            $hours   = (int)($parsed / 3600);
            $minutes = (int)(($parsed % 3600) / 60);
            $seconds = $parsed % 60;
            
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return $parsed;
    }

    /**
    *   Получение значения, отформатированного для запроса
    */
    static public function getCheckOpValue($value) {
        return self::getDbRepresentation($value);
    }
}

