<?

/**
  Класс полной выборки
 */
class NamiQCALL extends NamiQCOp {

    public $optree;

    function __construct() {
        $this->optree = new NamiQCEmptyOp();
    }

    function getNamiQueryParts($model, $queryset = null) {
        return $this->optree->getNamiQueryParts($model, $queryset);
    }

}
