<?
class ParseOrderReplacer {
    public $joins = array();
    protected $queryset;
    
    static $untouchables = array(
        'and'   => 1,
        'or'    => 1,
        'is'    => 1,
        'null'  => 1,
        'not'   => 1,
        'in'    => 1,
        'coalesce'  => 1,
        'CURRENT_TIMESTAMP' => 1,
        'false' => 1,
    );
    
    function __construct($queryset) {
        $this->queryset = $queryset;
    }

    function process($matches) {
        $field = $matches[0];
        if (! array_key_exists($field, self::$untouchables)) {
            try {
                $info = $this->queryset->parseOrderField($field);
                if (array_key_exists('joins', $info)) {
                    $this->joins = array_merge($this->joins, $info['joins']);
                }

                return $info['field'];
            } catch(NamiException $e) {
                // >_>
            }
        }
        return $field;
    }
}