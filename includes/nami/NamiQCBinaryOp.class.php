<?

/**
  Бинарный оператор (два аргумента)
 */
class NamiQCBinaryOp extends NamiQCOp {

    protected $left;    // Левый операнд
    protected $right;   // Правый операнд
    protected $sqlop;

    /**
      Конструктор
     */
    function __construct(NamiQCOp $left, NamiQCOp $right) {
        $this->left = $left;
        $this->right = $right;
    }

    /**
      Получение SQL
     */
    function getNamiQueryParts($model, $queryset = null) {
        if ($this->left instanceof NamiQCEmptyOp) {
            return $this->right->getNamiQueryParts($model, $queryset);
        }
        if ($this->right instanceof NamiQCEmptyOp) {
            return $this->left->getNamiQueryParts($model, $queryset);
        }

        $l = $this->left->getNamiQueryParts($model, $queryset);
        $r = $this->right->getNamiQueryParts($model, $queryset);

        return array(
            'joins' => array_merge($l['joins'], $r['joins']),
            'where' => "( {$l['where']} {$this->sqlop} {$r['where']} )",
            'params' => array_merge($l['params'], $r['params']),
            'expressions' => array_merge($l['expressions'], $r['expressions']),
        );
    }

}
