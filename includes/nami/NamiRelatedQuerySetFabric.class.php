<?
/*
    Фабрика FkQuerySet-ов.
    Умеет производить связанные QuerySet-ы для переданных объектов исходной модели.
*/
class NamiRelatedQuerySetFabric {
    protected $model;
    protected $field;

    function __construct($model, $field) {
        $this->model = $model;
        $this->field = $field;
    }
    
    function create(NamiModel $key_instance) {
        return new NamiFkQuerySet($this->model, $this->field, $key_instance);
    }
}