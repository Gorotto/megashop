<?php

class SiteUser extends NamiModel {

    static function definition() {
        return array(
            'name' => new NamiCharDbField(array('maxlength' => 250)),
            //-------- system params ---------
            'email' => new NamiCharDbField(array('maxlength' => 255, 'index' => true, 'null' => false)),
            'password' => new NamiCharDbField(array('maxlength' => 32)),
            'enabled' => new NamiBoolDbField(array('default' => false)),
            //хак для проверки смены статуса учетной записи
//            'prev_enabled_value' => new NamiBoolDbField(array('default' => false)),
            //код для активации
            'activation_code' => new NamiCharDbField(array('maxlength' => 32, 'index' => true)),
            //код для восстановления пароля
            'password_recover_code' => new NamiCharDbField(array('maxlength' => 32, 'index' => true)),
            'password_recover_code_created' => new NamiDatetimeDbField(array('format' => '%d.%m.%Y %H:%M:%S')),
            //корзинка для магаза
            'cart_data' => new NamiTextDbField(),
            'created' => new NamiDatetimeDbField(array('default_callback' => 'return time();', 'format' => '%d.%m.%Y %H:%M:%S')),
            'last_login' => new NamiDatetimeDbField(array('default_callback' => 'return time();', 'format' => '%d.%m.%Y %H:%M:%S')),
        );
    }

    public $description = array(
        'name' => array('title' => 'ФИО'),
        'email' => array('title' => 'Email'),
        'enabled' => array('title' => 'Учетная запись активирована'),
    );

    function beforeSave() {
        if (SiteUsers(array('email' => $this->email, 'id__ne' => $this->id))->count() > 0) {
            throw new Exception("Адрес {$this->email} принадлежит другой учетной записи и не может быть использован.");
        }

        if (!preg_match('/^[a-f0-9]{32}$/i', $this->password)) {
            $this->password = md5($this->password);
        }

        if (!$this->password && $this->enabled) {
            throw new Exception("Поле «пароль» не может быть пустым");
        }

        //уведомление о смене статуса
//        if ($this->prev_enabled_value != $this->enabled) {
//            //уведомление о смене статуса учетки
//            $mail = PhpMailerLibrary::create();
//            $mail->AddAddress($this->email);
//            $mail->Subject = "Учетная запись на сайте {$_SERVER['HTTP_HOST']}";
//            $mail->Body = new View('_mails/site_user_ch_status_notice', array(
//                'email' => $this->email,
//                'enabled' => $this->enabled,
//                'name' => $this->name
//            ));
//            $mail->IsHTML();
//            @$mail->Send();
//        }
//        $this->prev_enabled_value = $this->enabled;
    }

    /**
     * 	Генерация пароля и запись его мд5
     * @return string пароль
     */
    function setNewPass($number = 8) {
        $sumbols = array('a', 'b', 'c', 'd', 'e', 'f', 'g'
            , 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p'
            , 'r', 's', 't', 'u', 'v', 'x', 'y', 'z', 'A'
            , 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'
            , 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T'
            , 'U', 'V', 'X', 'Y', 'Z', '1', '2', '3', '4'
            , '5', '6', '7', '8', '9', '0');

        $new_pass = "";
        for ($i = 0; $i < $number; $i++) {
            $index = rand(0, count($sumbols) - 1);
            $new_pass .= $sumbols[$index];
        }

        $this->password = md5($new_pass);
        $this->save();
        return $new_pass;
    }

}
