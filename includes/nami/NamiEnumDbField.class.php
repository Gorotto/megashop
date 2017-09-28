<?
/**
    Поле перечисляемого типа
*/
class NamiEnumDbField extends NamiDbField {
	protected $valueClassname = 'NamiEnumDbFieldValue'; // Имя класса значений
    protected $values;
    
    function __construct( array $params = array() ) {
		if( ! ( array_key_exists( 'values', $params ) && is_array( $params['values'] ) && $params['values'] ) ) {
            throw new NamiException( "Для типа ".__CLASS__." необходимо указать параметр values — массив вариантов значения поля." );
        }
        parent::__construct( $params );
    }
    
	public function getCreationVariable( $name ) {
        if( $name == 'values' ) {
            $escaped = array();
            foreach( $this->values as $v ) {
                $escaped[] = "'".preg_replace( "/'/", "\'", $v )."'";
            }
            return join( ',', $escaped );
        }
        
        return parent::getCreationVariable( $name );
	}
}

