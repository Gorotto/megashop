<?
/**
    ���� ���� integer
*/
class NamiIntegerDbField extends NamiDbField
{
    /**
        ��������� �������� ����
    */
    function setValue( $string, $language = null )
    {
        if( ! is_null( $string ) )
        {
            if( $this->null && $string === '' ) {
                return parent::setValue( null, $language );
            }

            // ��������� ��������, ��������� �������� �������� exception
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

