<?

class NamiValidationException extends NamiException
{
    public $field = null;
    
    function __construct( $message, NamiDbField $field = null )
    {
        if( $field ) $this->field = $field;

        parent::__construct( $message );
    }

}

