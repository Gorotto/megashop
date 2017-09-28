<?

/**
 * 	Обертка к классу PHPMailer
 */
class MPDFLibrary {

    /**
     * 	Инициализация класса — подгружаем библиотеку PHPMailer.
     */
    static function init() {
        require_once( dirname(__FILE__) . "/MPDF/mpdf.php" );
    }

    /**
     * 	Получение объекта PHPMailer, аналог new PHPMailer().
     * 	Устанавливает некоторые нужные настройки по умолчанию.
     */
    static function create($mode = '', $format = 'A4', $default_font_size = 0, $default_font = '', $mgl = 15, $mgr = 15, $mgt = 16, $mgb = 16, $mgh = 9, $mgf = 9, $orientation = 'P') {
        // Создаем объект, ошибки генерируют исключения
        $mpdf = new mPDF($mode, $format, $default_font_size, $default_font, $mgl, $mgr, $mgt, $mgb, $mgh, $mgf, $orientation);

        return $mpdf;
    }

}

?>