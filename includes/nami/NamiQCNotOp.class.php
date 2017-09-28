<?

/**
  Оператор — отрицание
 */
class NamiQCNotOp extends NamiQCOp {

    protected $op;

    /**
      Конструктор
     */
    function __construct(NamiQCOp $op) {
        $this->op = $op;
    }

    /**
      Получение SQL
     */
    function getNamiQueryParts($model, $queryset = null) {
        // Получим SQL-параметры оператора, которые мы отрицаем
        $r = $this->op->getNamiQueryParts($model, $queryset);

        // И собственно, отрицаем его условия выборки
        $r['where'] = "( NOT {$r['where']} )";

        return $r;
    }

}
