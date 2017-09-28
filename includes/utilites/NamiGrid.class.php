<?
/**
* Генерирование таблицы на основе query set
* с ссылками на сортировку по полям
*/
class NamiGrid {
    private $items;
    private $view_name = false;
    private $sorted_fields;

    /**
     */
    public function __construct($view = '_grid/default', $items, $sorted_fields = array('id')) {
        if (is_null($items)) {
            throw new NamiException("NamiGrid need the items var data");
        }

        $this->items = $items;
        $this->view_name = $view;
        $this->sorted_fields = $sorted_fields;
    }

    public function set_view($view_name) {
        $this->view_name = $view_name;
    }

    protected function render() {
        echo "<table>";
        echo "<tr>";
        if ($this->sorted_fields) {
            // foreach ($this->qs as $i) {
            //     if (in_array($i->, $this->sorted_fields)) continue;
            // }
        } else {
            $field_names = NamiCore::getNamiModelMetadata();
            foreach ($this->items as $i) {
                var_dump($i);
            }
        }

        echo "</tr>";
        echo "</table>";
    }
}