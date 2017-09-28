<?

/**
 * Временный файл
 */
class TempFile extends NamiModel {

    static function definition() {
        return array(
            'file' => new NamiFileDbField(array('path' => "/static/uploaded/temp/files")),
            'date' => new NamiDatetimeDbField(array('default_callback' => 'return time();', 'format' => '%d.%m.%Y', 'index' => true)),
        );
    }

    function afterSave($new) {
        $temp_files_to_kill = TempFiles(array("date__lt" => strtotime("-7 day")))->limit(20);
        if ($temp_files_to_kill) {
            foreach ($temp_files_to_kill as $file) {
                $file->delete();
            }
        }
    }

}
