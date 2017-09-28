<?

class CatalogEntrySimilar extends NamiModel {

    static function definition() {
        return array(
            'entry' => new NamiFkDbField(array('model' => 'CatalogEntry',)),
            'similar' => new NamiFkDbField(array('model' => 'CatalogEntry',)),
        );
    }

    function beforeSave() {
        if ($this->entry->id == $this->similar->id) {
            throw new Exception("Невозможно добавить похожий товар, поскольку товар слишком похож на похожий.");
        }
        if (CatalogEntrySimilars(array('entry' => $this->entry, 'similar' => $this->similar))->count() > 0) {
            throw new Exception("Невозможно добавить похожий товар, поскольку такой похожий товар уже отмечен как похожий.");
        }
    }

}
