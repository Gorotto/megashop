<?php

class PasswordModule extends AbstractModule {

    function handleAjaxRequest($uri) {

        try {

            $vars = Meta::vars();

            Session::getInstance()->reloadData();

            $user = Session::getInstance()->getUser();

            if ($user->password != md5($vars['old_password'])) {
                throw new Exception('Неправильный старый пароль.');
            } else {
                $user->password = md5($vars['new_password']);
                $user->save();
                Session::getInstance()->reloadData();
            }

            return json_encode(array('success' => true));
        } catch (Exception $e) {
            return json_encode(array('success' => false, 'message' => $e->getMessage()));
        }

        return null;
    }

}
