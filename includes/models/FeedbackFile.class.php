<?

/**
 * файл для фидбеков
 */
class FeedbackFile extends NamiModel {

    static function definition() {
        return array(
            'file' => new NamiFileDbField(array('path' => '/static/uploaded/feedbackfiles')),
            'date' => new NamiDatetimeDbField(array('default_callback' => 'return time();', 'format' => '%d.%m.%Y', 'index' => true)),
        );
    }

}
