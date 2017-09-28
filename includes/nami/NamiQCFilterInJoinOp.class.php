<?

class NamiQCFilterInJoinOp extends NamiQCOp {

    private $tableToJoin;
    private $fieldToLink;
    private $filteredField;
    private $filteredValue;

    function __construct($tableToJoin, $fieldToLink, $filteredField, $filteredValue) {
        $this->tableToJoin = $tableToJoin;
        $this->fieldToLink = $fieldToLink;
        $this->filteredField = $filteredField;
        $this->filteredValue = $filteredValue;
    }

    function getNamiQueryParts($model, $queryset = null) {
        $linkJoin = new NamiQueryJoin("LEFT", strtolower($this->tableToJoin), "_" . strtolower($this->tableToJoin), "_" . strtolower($model) . ".id = _" . strtolower($this->tableToJoin) . "." . $this->fieldToLink);

        $where = "1";

        if (preg_match("/^(?<field>[^\s]*)\_\_(?<operator>[\w]+)$/", $this->filteredField, $matches)) {
            switch ($matches['operator']) {
                case "in":
                    $where = '_' . strtolower($this->tableToJoin) . "." . $matches['field'] . " IN (" . implode(", ", $this->filteredValue) . ")";
                    break;
            }
        } else {
            $where = '_' . strtolower($this->tableToJoin) . "." . $this->filteredField . "=" . $this->filteredValue;
        }


        return array(
            'joins' => array($linkJoin),
            'where' => $where,
            'params' => array(),
            'expressions' => array(),
        );
    }

}
