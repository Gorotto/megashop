<?php

class CsvProcessor {

    protected $delimiter = ";";
    private $filepath;
    protected $file_link;

    function __construct($filepath) {
        if (!preg_match('~^(.+)\.csv$~', $filepath)) {
            throw new Exception("Формат файла должен быть .csv");
        }
        if ($this->upload_in_process($filepath)) {
            throw new Exception("Файл в процессе загрузки.");
        }
        $fp = @fopen($filepath, "r");
        if (!$fp) {
            throw new Exception("Не удается открыть файл {$filepath}");
        }
        $this->file_link = $fp;
        $this->filepath = $filepath;
    }

    function prepare_csv_line($str) {
        //перегоняем кодировку если нужно
//        $str = iconv('windows-1251', 'UTF-8', $str);
        //$array_data = explode($this->delimiter, $str);
        // парсить csv надо встроенными функциами
        $array_data = str_getcsv($str, $this->delimiter, '"');

        $new_array_data = array();

        foreach ($array_data as $data) {
            //замена пробелов
            $data_tmp = preg_replace('~(\x{C2A0}|\x{FEFF})+~ui', ' ', $data);
            $data_tmp = trim($data_tmp);
            //удаление кавычек
            $data_tmp = preg_replace("~^[\x22\x27](.*)[\x22\x27]$~ui", '$1', $data_tmp);
            $new_array_data[] = $data_tmp;
        }

        if (property_exists($this, "fields_map")) {
            $new_array_data_ = array();

            foreach ($this->fields_map as $field_num => $field_data) {
                $value = null;
                switch ($field_data['type']) {
                    case "string":
                        $value = (string) $new_array_data[$field_num];
                        break;
                    case "float":
                        $value = (float) str_replace(",", ".", $new_array_data[$field_num]);
                        break;
                    case "bool":
                        $value = (bool) str_replace(",", ".", $new_array_data[$field_num]);
                        break;
                    case "int":
                        $value = (int) $new_array_data[$field_num];
                        break;
                }

                $new_array_data_[$field_data['name']] = $value;
            }

            $new_array_data_["enabled"] = true;

            $new_array_data = $new_array_data_;
        }

        return $new_array_data;
    }

    /**
     * выгрузка работает только при стабильном файле
     */
    private function upload_in_process($file_path) {
        if (Builder::developmentMode()) {
            return false;
        }

        $size1 = filesize($file_path);
        sleep(3);
        clearstatcache();
        $size2 = filesize($file_path);
        if ($size1 != $size2) {
            return true;
        }

        return false;
    }

}

?>
