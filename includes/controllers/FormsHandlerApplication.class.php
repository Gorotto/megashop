<?php

/**
 * Обработчик ajax форм
 */
class FormsHandlerApplication extends UriConfApplication {

    protected $uriconf = array(
        array('~^/?$~', 'index'),
        array('~^/(?P<action>\w+)/?~', 'call_action'),
    );

    function index($vars, $page) {
        Builder::show404();
        return true;
    }

    function call_action($vars) {
        $this->check_ajax_request();

        header('Content-Type: application/json');
        echo $this->{$vars->action}();
        return true;
    }

    function check_ajax_request() {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            throw new Http404();
        }
    }

    function callback() {
        $validator_rules = array(
            'name' => array('trim', 'required', array('length', 1, 100)),
            'phone' => array('trim', 'required', 'phone', array('length', 1, 100)),
        );

        $validator_messages = array();

        $validator = new DataValidator($validator_rules, $validator_messages);
        $status = $validator->process(Meta::vars());

        if ($status->ok) {
            $data = array(
                "ФИО" => $status->data['name'],
                "Телефон" => $status->data['phone'],
            );

            $form_name = "Обратный звонок";


            self::add_to_log($data, $form_name);

            $mail = PhpMailerLibrary::create();
            foreach (explode(",", Config::get('callback.email')) as $email) {
                $mail->AddAddress(trim($email));
            }
            $mail->Subject = "Форма «{$form_name}». Сайт {$_SERVER['HTTP_HOST']}";
            $mail->Body = (string) new View('_mails/default', array("data" => $data, "title" => "Информация отправленная через форму «{$form_name}»"));
            $mail->IsHTML(true);
            @$mail->Send();


            $text_block = TextBlocks()->getOrCreate(array('name' => "Форма «{$form_name}»: форма отправлена", 'rich' => '1'));
            $msg = (string) $text_block->text;
        } else {
            $msg = "Пожалуйста, корректно заполните отмеченные поля";
        }


        $json = json_encode(array($status, $msg));
        return $json;
    }

    /**
     * Добавление данных в лог
     * @param array $data данные в виде массива, название поля => значение
     * @param string $type тип формы, обратная связь, обратный звонок и пр.
     */
    static function add_to_log($data, $type) {
        $data_string = "";
        foreach ($data as $key => $value) {
            $data_string .= $key . ": " . ($value ? $value : "не указано") . "<br>";
        }

        $record = FormsHandlerLogItems()
            ->create(array(
            "user_info" => json_encode($_SERVER),
            "type" => $type,
            "text" => $data_string,
        ));

        return $record;
    }

}
