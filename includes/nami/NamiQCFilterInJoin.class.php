<?

class NamiQCFilterInJoin extends NamiQCOp {

    public $optree;

    function __construct($tableToJoin, $fieldToLink, $filteredField, $filteredValue) {
        $this->optree = new NamiQCFilterInJoinOp($tableToJoin, $fieldToLink, $filteredField, $filteredValue);
    }

    function getNamiQueryParts($model, $queryset = null) {
        return $this->optree->getNamiQueryParts($model, $queryset);
    }

}
