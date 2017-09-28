<?

/**
 * Временный файл для пользователей сайта
 * имеет защиту на количество загружаеммых данных в сутки
 */
class FeedbackTempFile extends NamiModel {

    static $max_files_per_day = 100;

    static function definition() {
        return array(
            'identify' => new NamiCharDbField(array('maxlength' => 50)),
            'browser_info' => new NamiTextDbField(),
            'browser_info_md5' => new NamiCharDbField(array('maxlength' => 250, 'index' => true)),
            'date' => new NamiDatetimeDbField(array('default_callback' => 'return time();', 'format' => '%d.%m.%Y', 'index' => true)),
        );
    }

    function beforeSave() {
        if (self::uploadAvailable()) {
            $browser_info = self::getBrowserInfo();

            $this->browser_info = $browser_info['json'];
            $this->browser_info_md5 = $browser_info['md5'];
        } else {
            throw new Exception("Превышен лимит на количество загружаемых файлов в сутки");
        }
    }

    function afterSave($new) {
        $this->identify = md5($this->id . time() . rand(1, 1000));
        $this->hiddenSave();


        $temp_images_to_kill = FeedbackTempFiles(array("date__lt" => strtotime("-7 day")))->limit(10);
        if ($temp_images_to_kill) {
            foreach ($temp_images_to_kill as $img) {
                $img->delete();
            }
        }
    }

    static function fileExist($identify) {
        return (bool) FeedbackTempFiles()->get(array("identify" => $identify));
    }

    static function uploadAvailable() {
        $browser_info = self::getBrowserInfo();

        $loaded_files_today = FeedbackTempFiles()
            ->filter(array(
                "date__gt" => strtotime(date("Y-m-d")),
                "browser_info_md5" => $browser_info['md5']
            ))
            ->count();

        return ($loaded_files_today < self::$max_files_per_day);
    }

    static function getBrowserInfo() {
        $forwarded = array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
        $forwarded = substr($forwarded, 0, 200);

        $browser_info = array(
            "remote_addr" => array_key_exists('REMOTE_ADDR', $_SERVER) ? $_SERVER['REMOTE_ADDR'] : '',
            "host" => array_key_exists('HTTP_HOST', $_SERVER) ? $_SERVER['HTTP_HOST'] : '',
            "referrer" => array_key_exists('HTTP_REFERER', $_SERVER) ? $_SERVER['HTTP_REFERER'] : '',
            "forwarded" => $forwarded,
        );

        return array(
            "json" => json_encode($browser_info),
            "md5" => md5(implode(",", $browser_info)),
        );
    }

}
