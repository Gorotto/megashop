<?
/**
*	Поле-первичный ключ. Должно использовать автоинкремент в БД.
*/
class NamiAutoDbField extends NamiDbField {
	protected $null = false;
	protected $localized = false;

	function __construct( array $params = array() ) {
		if( array_key_exists( 'localized', $params ) && $params['localized'] ) {
			throw new NamiException( get_class( $this )." cannot be localized" );
		}
		if( array_key_exists( 'null', $params ) && $params['null'] ) {
			throw new NamiException( get_class( $this )." cannot be null" );
		}
		parent::__construct( $params );
	}

	function setValue( $string, $language = null ) {
		if( ! is_numeric( $string ) || $string <= 0 ) {
			throw new NamiValidationException( "'$string' is not valid NamiAutoDbField value", $this );
		}
		return parent::setValue( (int)$string, $language );
	}
}

