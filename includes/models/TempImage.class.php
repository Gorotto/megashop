<?

/**
 * Временная картинка
 */
class TempImage extends NamiModel {

    static function definition() {
        return array(
            'image' => new NamiImageDbField(array('path' => "/static/uploaded/temp/images",
                'variants' => array(
                    'cms' => array('width' => 120, 'height' => 120, 'crop' => true),
                ))),
            'date' => new NamiDatetimeDbField(array('default_callback' => 'return time();', 'format' => '%d.%m.%Y', 'index' => true)),
        );
    }

    function afterSave($new) {
        $temp_images_to_kill = TempImages(array("date__lt" => strtotime("-7 day")))->limit(20);
        if ($temp_images_to_kill) {
            foreach ($temp_images_to_kill as $img) {
                $img->delete();
            }
        }
    }

}
