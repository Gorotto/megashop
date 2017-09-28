<?

/**
  Класс условия выборки
 */
class NamiQC extends NamiQCOp {

    public $optree; #TODO Заменить на protected!

    function __construct($params, $arg = null) {
        // Проверим параметры, это должен быть массив, integer или объект NamiModel или же строка и $arg - строка
        if (func_num_args() == 2) {
            $params = array($params => $arg);
        } else if (!is_array($params)) {
            if (!( is_numeric($params) || $params instanceof NamiModel )) {
                throw new NamiException('Must use array, integer or NamiModel instance to create query parameter');
            }

            $params = array('pk' => is_object($params) ? $params->meta->getPkValue() : (int) $params);
        }

        foreach ($params as $field => $value) {
            if ($this->optree) {
                $this->optree = new NamiQCAndOp($this->optree, new NamiQCCheckOp($field, $value));
            } else {
                $this->optree = new NamiQCCheckOp($field, $value);
            }
        }
    }

    function getNamiQueryParts($model, $queryset = null) {
        return $this->optree->getNamiQueryParts($model, $queryset);
    }

}
