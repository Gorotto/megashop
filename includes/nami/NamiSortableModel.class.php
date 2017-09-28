<?

/**
  Модель Nami, записи которой можно сортировать
 */
abstract class NamiSortableModel extends NamiModel {

    /** Массив полей, по которым ветвится сортировка записей.
      Записи сортируются в рамках ветки, которая имеет одно и тоже значение полей,
      перечисленных в branchingFields */
    protected $branchingFields = array();

    final function _definition() {
        return array(
            'sortpos' => new NamiIntegerDbField(array('index' => true)),
        );
    }

    function getBranchFilterParams() {
        $params = array();
        foreach ($this->branchingFields as $field) {
            $params[$field] = $this->$field;
        }
        return $params;
    }

    final function _afterSave($new) {
        if ($new && !$this->sortpos) {
            $last = $this
                ->getQuerySet()
                ->filter($this->getBranchFilterParams())
                ->filter(array('pk__ne' => $this->meta->pkname))
                ->orderDesc('sortpos')
                ->only('sortpos')
                ->first();
            $this->sortpos = $last ? $last->sortpos + 1 : 1;
            $this->hiddenSave();
        }
    }

    function getQuerySet() {
        return new NamiQuerySet($this->meta->name);
    }

    public function createCopy() {
        $params = $this->getDataToCopy();
        unset($params['sortpos']);
        $new_copy = NamiQuerySet($this->meta->name)->create($params);

//  сортировка очень часто ломается, 
//  т.к. метод переопределения элементов очень ресурсоемуий
//  поэтому я оставлю это тут до лучших времен
//  
//            $ids_list = NamiQuerySet($this->meta->name)
//                ->filter(array(
//                    "sortpos__gt" => $this->sortpos
//                ))
//                ->values("id");
//
//            if ($ids_list) {
//                array_unshift($ids_list, $this->id, $new_copy->id);
//            }
//
//            NamiQuerySet($this->meta->name)
//                ->filter(array("id__in" => $ids_list))
//                ->reorder($ids_list);

        return $new_copy->asArray();
    }

}
