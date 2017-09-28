<?php

class CsvEntriesProcessor extends CsvProcessor {

    private $CATALOG_ENTRY_MODEL_NAME = "CatalogEntry";
    private $CATALOG_CATEGORY_MODEL_NAME = "CatalogCategory";
    private $entries_cache = array();
    private $entries_to_enabled_ids = array();
    public $entries_add = 0;
    public $entries_del = 0;
    public $entries_upd = 0;
    protected $fields_map;

    function __construct($filepath, $fields) {
        parent::__construct($filepath);
        $this->fields_map = $fields;
        $this->load_entries_cache();
    }

    function print_log() {
        echo "Добавлено {$this->entries_add} позиций для товаров каталога<br>";
        echo "Обновлено {$this->entries_upd} позиций для товаров каталога<br>";
        echo "Отключено {$this->entries_del} позиций для товаров каталога<br>";
        echo "<br>";
    }

    private function pack_md5($data) {
        $str = "";

        foreach ($this->fields_map as $field_num => $field_data) {
            if (
                ($field_data['name'] == "category_onec_code") ||
                ($field_data['name'] == "entry_onec_code")
            ) {
                continue;
            }

            switch ($field_data['type']) {
                case "string":
                    $str .= (string) $data[$field_data['name']];
                    break;
                case "float":
                    $str .= (float) $data[$field_data['name']];
                    break;
                case "bool":
                    $str .= (bool) $data[$field_data['name']];
                    break;
                case "int":
                    $str .= (int) $data[$field_data['name']];
                    break;
            }
        }

        $str .= (string) $data['category'] . (string) $data['enabled'];
        return md5($str);
    }

    /**
     * кеширование дерева для проверки на наличие
     */
    private function load_entries_cache() {
        $fields = array(
            "id",
            "onec_code",
            "enabled",
            "category" => array(
                "fk_name" => "category",
                "values" => "onec_code"
            ),
        );

        foreach ($this->fields_map as $field_num => $field_data) {
            if (!in_array($field_data['name'], array("category_onec_code", "entry_onec_code"))) {
                $fields[] = $field_data['name'];
            }
        }

        $entries = NamiQuerySet($this->CATALOG_ENTRY_MODEL_NAME)->values($fields);

        if ($entries) {
            foreach ($entries as $entry_data) {
                $this->entries_cache[$entry_data['onec_code']] = array(
                    "md5" => $this->pack_md5($entry_data),
                    "id" => $entry_data['id']
                );
            }
        }
    }

    /**
     * пуск!
     */
    function process() {
        $this->delimiter = "&";
        $this->detect_dublicates();

        while (!feof($this->file_link)) {
            $line = fgets($this->file_link);
            if ($line) {
                $cur_fields = $this->prepare_csv_line($line);

                $this->process_line($cur_fields);
            }
        }
        fclose($this->file_link);

        $this->disable_entries();
        $this->detect_dublicates();
    }

    /**
     * Обработка строки выгрузки
     */
    private function process_line($data) {

        if (!$data["category_onec_code"]) {
            throw new Exception("Товар не содержит идентификатора категории<br>" . implode("///", $data));
        }

        if (!$data["entry_onec_code"]) {
            throw new Exception("Товар не содержит идентификатора<br>" . implode("///", $data));
        }

        if (array_key_exists($data["entry_onec_code"], $this->entries_cache)) {
            $this->update_entry($data);
        } else {
            $this->create_entry($data);
        }
    }

    /**
     * Обновление данных по категории
     */
    private function update_entry($data) {
        $md5_in_db = $this->entries_cache[$data["entry_onec_code"]]['md5'];

        $data['category'] = $data['category_onec_code'];
        $data['onec_code'] = $data['entry_onec_code'];
        unset($data['category_onec_code']);
        unset($data['entry_onec_code']);

        $md5_in_file = $this->pack_md5($data);

        if ($md5_in_file != $md5_in_db) {
            $entry = NamiQuerySet($this->CATALOG_ENTRY_MODEL_NAME)->get(array("onec_code" => $data["onec_code"]));
            $category = NamiQuerySet($this->CATALOG_CATEGORY_MODEL_NAME)->get(array("onec_code" => $data["category"]));
//
            if (!$category) {
                throw new Exception("Категории с кодом " . $data["category"] . " не существует");
//                $category = CatalogCategories()->first();
            }
            $data['category'] = $category->id;

//            поле с категорий не обновлется
//            unset($data['category']);


            $entry->copyFrom($data);
            $entry->save();

            $this->entries_cache[$entry->onec_code] = array(
                "md5" => $md5_in_file,
                "id" => $entry->id
            );

            $this->entries_upd++;
        }

        $this->entries_to_enabled_ids[] = $this->entries_cache[$data["onec_code"]]['id'];
    }

    /**
     * Создаем категорию
     */
    private function create_entry($data) {
        $category = NamiQuerySet($this->CATALOG_CATEGORY_MODEL_NAME)->get(array("onec_code" => $data["category_onec_code"]), false);
        if (!$category) {
            throw new Exception("Категории с кодом " . $data["category_onec_code"] . " не существует");
//            $category = CatalogCategories()->first();
        }

        $data['category'] = $category->id;
        $data['onec_code'] = $data['entry_onec_code'];
        unset($data['category_onec_code']);
        unset($data['entry_onec_code']);

        $new_entry = NamiQuerySet($this->CATALOG_ENTRY_MODEL_NAME)->create($data);

        $this->entries_add++;
        $this->entries_to_enabled_ids[] = $new_entry->id;
        $this->entries_cache[$new_entry->onec_code] = array(
            "md5" => $this->pack_md5($data),
            "id" => $new_entry->id
        );
    }

    /**
     * Выключение категорий
     */
    private function disable_entries() {
        $this->entries_del = NamiQuerySet($this->CATALOG_ENTRY_MODEL_NAME)
            ->filter(array(
                "id__notin" => $this->entries_to_enabled_ids
            ))
            ->count();

        if ($this->entries_to_enabled_ids) {
            $query = "update `" . mb_strtolower($this->CATALOG_ENTRY_MODEL_NAME) . "` set enabled = 0 " .
                "where id not in ('" . implode("','", $this->entries_to_enabled_ids) . "') " .
                "or onec_code IS NULL";
        } else {
            $query = "update `" . mb_strtolower($this->CATALOG_ENTRY_MODEL_NAME) . "` set enabled = 0";
        }

        NamiCore::getBackend()->cursor->execute($query);
    }

    /**
     * Проверка на дублирование информации
     */
    private function detect_dublicates() {
        $query = "SELECT `onec_code`, count(`onec_code`) FROM `" . mb_strtolower($this->CATALOG_ENTRY_MODEL_NAME) . "` GROUP BY `onec_code` HAVING COUNT(`onec_code`) > 1";

        $backend = NamiCore::getBackend();
        $backend->cursor->execute($query);
        $data = $backend->cursor->fetchAll();

        if ($data) {
            $codes = array();
            foreach ($data as $row) {
                $categories_title = NamiQuerySet($this->CATALOG_ENTRY_MODEL_NAME)
                    ->filter(array("onec_code" => $row[0]))
                    ->values("title");

                $codes[] = implode(", ", $categories_title) . "(" . $row[0] . ")";
            }

            throw new Exception("В каталоге присутствуют дублирующие товары. " . implode("; ", $codes));
        }
    }

}

?>
