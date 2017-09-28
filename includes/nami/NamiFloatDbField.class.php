<?
/**
    Поле типа float
*/
class NamiFloatDbField extends NamiDbField
{
    /**
     * setValue function.
     * @access public
     * @param mixed $string
     * @return void
     */
    function setValue( $string, $language = null )
    {
        if( ! is_null( $string ) )
        {
            if( $this->null && $string == '' ) {
                return parent::setValue( null, $language );
            }
        
            if( ! is_numeric( $string ) )
            {
                throw new NamiValidationException( "'$string' is not valid float value", $this );
            }

            return parent::setValue( (float)$string, $language );
        }
        else
        {
            return parent::setValue( $string, $language );
        }
    }
    
    function getValueForDatabase( $language = null )
    {
    	$value = $this->value->get( $language );
        return is_null( $value ) ? null : str_replace(',', '.', $value->getForDatabase());
    }
}

