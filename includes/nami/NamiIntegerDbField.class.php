<?
/**
    Поле типа integer
*/
class NamiIntegerDbField extends NamiDbField
{
    /**
        Установка значения поля
    */
    function setValue( $string, $language = null )
    {
        if( ! is_null( $string ) )
        {
            if( $this->null && $string === '' ) {
                return parent::setValue( null, $language );
            }

            // Проверяем значение, нехорошее значение вызывает exception
            if( ! is_numeric( $string ) )
            {
                throw new NamiValidationException( "'$string' is not valid integer value", $this );
            }

            return parent::setValue( (int)$string, $language );
        }
        else
        {
            return parent::setValue( $string, $language );
        }
    }
}

