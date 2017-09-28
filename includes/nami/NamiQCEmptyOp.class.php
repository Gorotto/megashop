<?

/**
  Пустой оператор.
  Нужен для начальной инициализации дерева условий NamiQuerySet-а.
 */
class NamiQCEmptyOp extends NamiQCOp {

    /**
      Получение SQL
     */
    function getNamiQueryParts($model, $queryset = null) {
        // То, что будем возвращать
        return array(
            'joins' => array(),
            'where' => '',
            'params' => array(),
            'expressions' => array(),
        );
    }

}
