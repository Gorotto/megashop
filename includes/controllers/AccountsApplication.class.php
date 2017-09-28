<?php

class AccountsApplication extends UriConfApplication {

    protected $uriconf = [
        ['~^/?$~', 'profile'],
        ['~^/login/?$~', 'login'],
        ['~^/logout/?$~', 'logout'],
        ['~^/edit/password/?$~', 'edit_password'],
        ['~^/edit/info/?$~', 'edit_info'],
        //восстановление пароля        
        ['~^/password_recover/?$~', 'password_recover'],
        ['~^/password_change/(?P<code>[a-fA-F0-9]{32})?/?$~', 'password_change']
    ];

    public function registration($vars, $page) {
        if (Meta::isAjaxRequest()) {
            
            $validator_rules = [
                'name' => array('trim', 'required', array('length', 1, 100)),
                'phone' => array('trim', 'required', 'phone', array('length', 1, 100)),
                'email' => array('trim', 'required', 'email', 'useremailavailable', array('length', 1, 100)),
                'password' => array('trim', 'required', array('length', 4, 30)),
                'password_confirm' => array('trim', 'required', array('matchfield', 'password')),
            ];
                
            $validator_messages = [
                'phone.phone' => 'введите корректный телефон',
                'email.email' => 'введите корректный адрес электронной почты',
                'email.useremailavailable' => 'данный адрес электронной почты уже используется',
                'password.length' => 'длина пароля должна быть от 4 до 30 символов',
                'password_confirm.matchfield' => 'введенные пароли не совпадают',                
            ];

            $validator = new DataValidator($validator_rules, $validator_messages);
            $status = $validator->process(Meta::vars());
            $msg = "";

            if ($status->ok) {
                $data = $status->data;

                $data["enabled"] = false;
                $data["password"] = md5($status->data['register_password']);

                $user = SiteUsers()->create($data);

                $user->activation_code = md5($user->id . rand(1, 1000) . time());
                $user->save();

                // отправим письмо с ссылкой на активацию
                $mail = PhpMailerLibrary::create();
                $mail->AddAddress($user->email);
                $mail->Subject = "Активация учетной записи на сайте {$_SERVER['HTTP_HOST']}";
                $mail->Body = new View('_mails/site_user_activate', compact('user'));
                $mail->IsHTML();
                @$mail->Send();
            }

            $json = json_encode(array($status, $msg));

            header('Content-Type: application/json');
            print $json;
            return true;
        } else {
            $page->title = "Регистрация на сайте";

            print new View('accounts/page-registration', compact('page'));
            return true;
        }
    }

    function activate($vars, $page) {
        $msg = "";
        $user = SiteUsers()->get(["activation_code" => $vars->code]);

        if ($user) {
            $user->activation_code = null;
            $user->save();

            $msg = "Ваш Email успешно подтвержден.<br>"
                    . "Для авторизации перейдите по <a href='" . Builder::getAppUri("AccountsApplication") . "login/'>ссылке</a>.";
        } else {
            $msg = "Ссылка для активации недействительна.";
        }

        $page->title = "Активация учетной записи";

        print new View('accounts/page-activation_result', compact('page', 'msg'));
        return true;
    }

    function login($vars, $page) {
        if (SiteSession::getInstance()->isAuthorized()) {
            SiteSession::getInstance()->logout();
        }

        if (Meta::isAjaxRequest()) {
            $validator_rules = array(
                'login_form-email' => array('trim', 'required', 'email', array('length', 1, 100)),
                'login_form-password' => array('trim', 'required', array('length', 1, 100)),
            );

            $validator_messages = array();

            $validator = new DataValidator($validator_rules, $validator_messages);
            $status = $validator->process(Meta::vars());
            $msg = "Пожалуйста, заполните отмеченные поля";

            if ($status->ok) {
                $data = $status->data;
                $auth = SiteSession::getInstance()->auth($data['login_form-email'], $data['login_form-password']);

                if (!$auth['success']) {
                    $response = new stdClass();
                    $response->ok = false;
                    $response->data = array();
                    $response->errors = array(
                        "login_form-email" => array("key" => "login_form-email"),
                        "login_form-password" => array("key" => "login_form-password")
                    );
                    $msg = $auth['error'];

                    $json = json_encode(array($response, $msg));

                    header('Content-Type: application/json');
                    print $json;
                    return true;
                }
            }

            $json = json_encode(array($status, $msg));

            header('Content-Type: application/json');
            print $json;
        } else {
            $page->title = "Авторизация на сайте";

            print new View('accounts/page-login', compact('page'));
        }

        return true;
    }

    function logout($vars, $page) {
        if (!SiteSession::getInstance()->isAuthorized()) {
            Builder::show403();
        }

        SiteSession::getInstance()->logout();
        header("Location: /", true);
    }

    function profile($vars, $page) {
        if (!SiteSession::getInstance()->isAuthorized()) {
            Builder::show403();
        }

        $page->title = "Личный кабинет";

        print new View('accounts/page-profile', compact('page'));
        return true;
    }

    function edit_password($vars, $page) {
        if (!SiteSession::getInstance()->isAuthorized()) {
            Builder::show403();
        }

        if (Meta::isAjaxRequest()) {
            $validator_rules = array(
                'old_password' => array('trim', 'required', 'is_current_password', array('length', 1, 100)),
                'new_password' => array('trim', 'required', array('length', 1, 100)),
                'new_password_confirm' => array('trim', 'required', array('length', 1, 100), array('matchfield', 'new_password')),
            );

            $validator_messages = array(
                'old_password' => 'введите текущий пароль',
                'old_password.is_current_password' => 'введите правильный текущий пароль',
                'new_password' => 'введите новый пароль',
                'new_password_confirm' => 'введите подтверждение нового пароля',
                'new_password_confirm.matchfield' => 'введенные пароли не совпадают',
            );

            $validator = new DataValidator($validator_rules, $validator_messages);
            $status = $validator->process(Meta::vars());
            $msg = "";

            if ($status->ok) {
                $data = $status->data;

                SiteSession::getInstance()->reloadData();
                $user = SiteSession::getInstance()->getUser();

                //данные — ок. обновляем.
                $user_db = SiteUsers()->get($user->id);
                $user_db->password = $data['new_password'];
                $user_db->save();

                $msg = "Ваш пароль успешно изменен";
            } else {
                $msg = "Пожалуйста, заполните отмеченные поля";
            }

            $json = json_encode(array($status, $msg));
            header('Content-Type: application/json');
            echo $json;
        } else {
            print new View('accounts/page-edit_password', compact('page'));
        }
        return true;
    }

    function edit_info($vars, $page) {
        if (!SiteSession::getInstance()->isAuthorized()) {
            Builder::show403();
        }

        if (Meta::isAjaxRequest()) {

            $validator_rules = array(
                'name' => array('trim', 'required', array('length', 1, 1000)),
            );

            $validator_messages = array();

            $validator = new DataValidator($validator_rules, $validator_messages);
            $status = $validator->process(Meta::vars());
            $msg = "";

            if ($status->ok) {
                $data = $status->data;

                $user = SiteSession::getInstance()->getUser();

                $user_db = SiteUsers()->get($user->id);
                $user_db->name = $data['name'];
                $user_db->save();

                SiteSession::getInstance()->reloadData();
            } else {

                $msg = "Пожалуйста, заполните отмеченные поля";
            }

            $json = json_encode(array($status, $msg));
            header('Content-Type: application/json');
            echo $json;
        } else {
            print new View('accounts/page-edit_info', compact('page'));
        }

        return true;
    }

    /**
      Страница восстановления пароля (шаг 1)
      Отсылаем человеку на почту ссылку на страницу со сменой пароля (password_change).
     */
    function password_recover($vars, $page) {
        if (Meta::isAjaxRequest()) {
            $validator_rules = array(
                'email' => array(
                    'trim',
                    'required',
                    'email',
                    'useremailexist',
                    array('length', 1, 100)
                ),
            );

            $validator_messages = array(
                'email' => 'Укажите адрес электронной почты',
                'email.email' => 'Укажите верный адрес электронной почты',
                'email.useremailexist' => 'Пользователь с данным email не найден. Возможно при регистрации на нашем сайте вы указывали другой адрес электронной почты.',
            );

            $validator = new DataValidator($validator_rules, $validator_messages);
            $status = $validator->process(Meta::vars());
            $msg = "";

            if ($status->ok) {
                $data = $status->data;

                $user = SiteUsers()->get(array('email' => $data['email']));
                if ($user) {
                    if (!$user->enabled) {
                        $msg = "Пользователь с данным email заблокирован. Обратитесь к администратору сайта.";
                    } else {

                        //генерим код для создания временной сессии
                        $user->password_recover_code = md5($user->id . rand(1, 1000) . time());
                        // запомним время создания кода (он будет рабочим только 3 часа)
                        $user->password_recover_code_created = time();
                        $user->save();

                        // отправим письмо
                        $mail = PhpMailerLibrary::create();
                        $mail->AddAddress($user->email);
                        $mail->Subject = "Восстановление пароля сайта {$_SERVER['HTTP_HOST']}";
                        $mail->Body = new View('_mails/site_user_password_recover', compact('user'));
                        $mail->IsHTML();
                        @$mail->Send();

                        $msg = "На ваш email отправлено письмо, содержащее ссылку, перейдя по которой вы сможете изменить свой пароль.";
                    }
                }
            }

            $json = json_encode(array($status, $msg));
            header('Content-Type: application/json');
            print $json;
        } else {

            $page->title = "Восстановление пароля";
            print new View('accounts/page-password_recover', compact('page'));
        }

        return true;
    }

    /**
      Страница восстановления пароля (шаг 2)

      Сюда человек приходит по ссылке, которую получил по почте из password_recover,
      для того, чтобы установить новый пароль. Ему дается одноразовая сессия и возможность
      один раз воспользоваться формой изменения пароля.
      Эта страница остается доступной в течение 3-х часов после отправки письма.
     */
    function password_change($vars, $page) {
        $code_error = false;
        //3 часа
        $live_time = 3 * 3 * 60;

        if ($vars->code) {
            $user = SiteUsers()->get(array('password_recover_code' => $vars->code));
        } else {
            $user = SiteSession::getInstance()->getUser();
        }

        if ($user) {
            SiteSession::getInstance()->login($user->email, $user->password, false);

            if (time() > (strtotime($user->password_recover_code_created) + $live_time)) {
                //ссылка старая, удаляем код
                $user->password_recover_code = null;
                $user->password_recover_code_created = null;
                $user->save();

                $code_error = true;
            }
        } else {
            //ссылка битая
            $code_error = true;
        }

        if (Meta::isAjaxRequest()) {
            if ($code_error) {
                return true;
            }

            $validator_rules = array(
                'new_password' => array('trim', 'required', array('length', 1, 100)),
                'new_password_confirm' => array('trim', 'required', array('length', 1, 100), array('matchfield', 'new_password')),
            );

            $validator_messages = array(
                'new_password' => 'введите новый пароль',
                'new_password_confirm' => 'введите подтверждение нового пароля',
                'new_password_confirm.matchfield' => 'введенные пароли не совпадают',
            );

            $validator = new DataValidator($validator_rules, $validator_messages);
            $status = $validator->process(Meta::vars());
            $msg = "";

            if ($status->ok) {
                //обновление данных
                // удалялем код из базы
                $user->password_recover_code = null;
                $user->password_recover_code_created = null;
                // ставим новый пароль
                $user->password = md5($status->data['new_password']);
                $user->save();
                SiteSession::getInstance()->reloadData();

                $msg = "Ваш пароль успешно изменен";
            } else {
                $msg = "Пожалуйста, заполните отмеченные поля";
            }

            $json = json_encode(array($status, $msg));
            header('Content-Type: application/json');
            print $json;
        } else {

            $page->title = "Восстановление пароля";
            print new View('accounts/page-password_change', compact('page', 'code_error'));
        }

        return true;
    }

}
