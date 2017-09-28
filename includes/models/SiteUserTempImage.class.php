<?php

/**
 * Временная картинка для пользователей сайта
 *
 * имеет защиту на количество загружаеммых данных в сутки
 */
class SiteUserTempImage extends NamiModel {

    static $max_files_per_day = 100;

    static function definition() {
        return array(
            'image' => new NamiImageDbField(array('path' => "/static/uploaded/usertemp/images",
                'variants' => array(
                    'user_avatar_preview' => array('width' => 310, 'height' => 310, 'crop' => true),
                ))),
            'idetify' => new NamiCharDbField(array('maxlength' => 50)),
            'browser_info' => new NamiTextDbField(),
            'browser_info_md5' => new NamiCharDbField(array('maxlength' => 250, 'index' => true)),
            'date' => new NamiDatetimeDbField(array('default_callback' => 'return time();', 'format' => '%d.%m.%Y', 'index' => true)),
        );
    }

    function beforeSave() {
        $forwarded = array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
        $forwarded = substr($forwarded, 0, 200);

        $browser_info = array(
            "remote_addr" => array_key_exists('REMOTE_ADDR', $_SERVER) ? $_SERVER['REMOTE_ADDR'] : '',
            "host" => array_key_exists('HTTP_HOST', $_SERVER) ? $_SERVER['HTTP_HOST'] : '',
            "referrer" => array_key_exists('HTTP_REFERER', $_SERVER) ? $_SERVER['HTTP_REFERER'] : '',
            "forwarded" => $forwarded,
        );

        $browser_info_md5 = md5(implode(",", $browser_info));

        $loaded_files_today = SiteUserTempImages()
                ->filter(array(
                    "date__gt" => strtotime(date("Y-m-d")),
                    "browser_info_md5" => $browser_info_md5
                ))
                ->count();


        if ($loaded_files_today > self::$max_files_per_day) {
            throw new Exception("Превышен лимит на количество загружаемых файлов в сутки");
        } else {
            $this->browser_info = json_encode($browser_info);
            $this->browser_info_md5 = $browser_info_md5;
        }
    }

    function afterSave($new) {
        $this->idetify = md5($this->id . time() . rand(1, 1000));
        $this->hiddenSave();


        $temp_images_to_kill = SiteUserTempImages(array("date__lt" => strtotime("-7 day")))->limit(10);
        if ($temp_images_to_kill) {
            foreach ($temp_images_to_kill as $img) {
                $img->delete();
            }
        }
    }

}
