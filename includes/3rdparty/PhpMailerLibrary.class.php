<?
/**
*	Обертка к классу PHPMailer
*/

class PhpMailerLibrary {

	/**
	*	Инициализация класса — подгружаем библиотеку PHPMailer.
	*/
	static function init() {
		require_once( dirname( __FILE__ )."/PHPMailer/class.phpmailer.php" );
	}

	/**
	*	Получение объекта PHPMailer, аналог new PHPMailer().
	*	Устанавливает некоторые нужные настройки по умолчанию.
	*/
	static function create( $exceptions = true ) {
		// Создаем объект, ошибки генерируют исключения
		$mailer = new PHPMailer( $exceptions );

//        YANDEX SMTP MAILER
//        $mailer->CharSet = 'utf-8';
//        $mailer->IsSMTP();
//        $mailer->Host = 'smtp.yandex.ru';
//        $mailer->SMTPAuth = true;
//        $mailer->Port = 465;
//        $mailer->SMTPSecure = "ssl";
//
//        $mailer->Username = 'your.mail.address@yandex.ru';
//        $mailer->Password = 'your.password';
//
//        $mailer->From = "your.mail.address@yandex.ru";
//        $mailer->FromName = 'your.mail.address@yandex.ru';

		// Кодировка сообщения — UTF-8!
		$mailer->CharSet = 'utf-8';

		// Адрес и имя - из настроек сайта
		$mailer->From =  'noreply@example.ru';
		$mailer->FromName = Config::get( 'common.site_title' );

		return $mailer;
	}
}

?>