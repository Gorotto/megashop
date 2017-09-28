<?php

//    Класс валидации данных.
//    Принимает данные из некоторого источника, проверяет их в соответствии с правилами, возвращает набор сообщений и отвалидированные данные.
//
//    ----------------- пример использования -----------------
//    $v = new DataValidator(
//        array(
//            'name'  => array( 'trim', 'required', array( 'length', 1, 10 ) ),
//            'email' => 'trim optional email',
//            'text'  => 'trim striptags required',
//        ),
//        array(
//            'name' => 'Введите имя.',
//            'name.length' => 'Имя не должно быть длинее 10 символов.',
//            'email' => 'Введите правильный адрес электронной почты.',
//            'text'  => 'Введите текст вопроса.',
//    ));
//
//    $s = $v->process( Meta::vars() );
//
//    if( $s->ok ) {
//        echo '<p>Ошибок нет</p>';
//    } else {
//        echo 'Ошибки: <ul>';
//        foreach( $s->errors as $e ) {
//            echo "<li>$e</li>";
//        }
//        echo '</ul>';
//    }
//    -----------------------------------------------------------


class ValidationException extends Exception {

    public $stop;   // Остановить валидацию поля
    public $error;  // Сгенерировать ошибку
    public $wipe;   // Удалить поле из обработанных данных

    public function __construct($error = true) {
        $this->error = $error;
    }

}

/**
 * проверка пароля для текущего пользователя
 */
class Is_Current_PasswordDataValidation extends AbstractDataValidation {

    public function check($data, $dataset, $key) {
        SiteSession::getInstance()->reloadData();
        $user = SiteSession::getInstance()->getUser();

        if ($user->password == md5($data)) {
            return true;
        } else {
            return false;
        }
    }

}

/**
 * Емейл доступен для регистрации
 */
class UserEmailAvailableDataValidation extends AbstractDataValidation {

    public function check($data, $dataset, $key) {
        $user = SiteUsers()->get(array('email' => $data));

        if (!$user) {
            return true;
        } else {
            return false;
        }
    }

}

/**
 * емейл имеется в базе
 */
class UserEmailExistDataValidation extends AbstractDataValidation {

    public function check($data, $dataset, $key) {
        $user = SiteUsers()->get(array('email' => $data));

        if ($user) {
            return true;
        } else {
            return false;
        }
    }

}

class TrimDataValidation extends AbstractDataValidation {

    public function preprocess($data, $dataset, $key) {
        return trim($data);
    }

}

class LengthDataValidation extends AbstractDataValidation {

    protected $min, $max;

    public function __construct($min, $max) {
        $this->min = $min;
        $this->max = $max;
    }

    public function check($data, $dataset, $key) {
        return mb_strlen($data) >= $this->min && mb_strlen($data) <= $this->max;
    }

}

class OptionalDataValidation extends AbstractDataValidation {

    public function check($data, $dataset, $key) {
        if (!$data) {
            $e = new ValidationException(false);
            $e->stop = true;
            $e->wipe = true;
            throw $e;
        }
        return true;
    }

}

class StriptagsDataValidation extends AbstractDataValidation {

    protected $allowed = '';

    public function __construct($allowed = '') {
        if ($allowed) {
            foreach (explode(' ', $allowed) as $tag) {
                $this->allowed .= "<{$tag}>";
            }
        }
    }

    public function preprocess($data, $dataset, $key) {
        return strip_tags($data, $this->allowed);
    }

}

class StripbadattrsDataValidation extends AbstractDataValidation {

    protected $stripper;

    public function __construct() {
        $sa = new StripAttributes();
        $sa->allow = array();
        $sa->exceptions = array(
            'p' => array('style', 'align'),
            'img' => array('src', 'alt', 'width', 'height'),
            'a' => array('href', 'title'),
            'object' => array('style', 'width', 'height'),
            'embed' => array('style', 'width', 'height',
                'src', 'type', 'allowfullscreen',
                'allowscriptaccess', 'wmode', 'flashvars'),
            'param' => array('name', 'value'),
        );
        $sa->ignore = array();
        $this->stripper = $sa;
    }

    public function preprocess($data, $dataset, $key) {
        return $this->stripper->strip($data);

        return strip_tags($data, $this->allowable_tags);
    }

}

class RequiredDataValidation extends AbstractDataValidation {

    public function check($data, $dataset, $key) {
        return $data ? true : false;
    }

}

class EmailDataValidation extends AbstractDataValidation {

    public function check($data, $dataset, $key) {
        return IsEmailLibrary::check($data);
    }

}

class EmailOrPhoneDataValidation extends AbstractDataValidation {

    public function check($data, $dataset, $key) {
        if (preg_match("/^[+]{0,1}[0-9-\s()]{5,18}$/", $data)) {
            return true;
        } elseif (IsEmailLibrary::check($data)) {
            return true;
        } else {
            return false;
        }
    }

}

class EmailRegexpDataValidation extends AbstractDataValidation {

    public function check($data, $dataset, $key) {
        if (preg_match("%^[A-Za-z0-9](([_\.\-]?[a-zA-Z0-9]+)*)@([A-Za-z0-9]+)(([\.\-]?[a-zA-Z0-9]+)*)\.([A-Za-z])+$%", $data)) {
            return true;
        }
        return false;
    }

}

class UploadedfileDataValidation extends AbstractDataValidation {

    protected $maxsize;

    public function __construct($maxsize = null) {
        $this->maxsize = $maxsize;
    }

    public function check($data, $dataset, $key) {
        if (is_array($data) && array_key_exists('error', $data) && array_key_exists('tmp_name', $data) && array_key_exists('size', $data) && $data['error'] == UPLOAD_ERR_OK && is_uploaded_file($data['tmp_name']) && (!$this->maxsize || $data['size'] <= $this->maxsize )
        ) {
            return true;
        }
        return false;
    }

}

class MatchfieldDataValidation extends AbstractDataValidation {

    protected $key;

    public function __construct($key) {
        $this->key = $key;
    }

    public function check($data, $dataset, $key) {
        return $data == $dataset[$this->key];
    }

}

class RequiredifsetDataValidation extends AbstractDataValidation {

    protected $key;

    public function __construct($key) {
        $this->key = $key;
    }

    public function check($data, $dataset, $key) {
        return ( $data || !$dataset[$this->key] ) ? true : false;
    }

}

class RequiredifmatchDataValidation extends AbstractDataValidation {

    protected $key_to_match;
    protected $regexp;

    public function __construct($key_to_match, $regexp) {
        $this->key_to_match = $key_to_match;
        $this->regexp = $regexp;
    }

    public function check($data, $dataset, $key) {
        return ( $data || !preg_match($this->regexp, $dataset[$this->key_to_match]) ) ? true : false;
    }

}

class CaptchaDataValidation extends AbstractDataValidation {

    public function check($data, $dataset, $key) {
        return Captcha::check();
    }

}

class InarrayDataValidation extends AbstractDataValidation {

    protected $values;

    public function __construct(array $values) {
        $this->values = $values;
    }

    public function check($data, $dataset, $key) {
        return in_array($data, $this->values);
    }

}

class DateDataValidation extends AbstractDataValidation {

    public function check($data, $dataset, $key) {
        return preg_match('/^\d+\.\d+\.\d+$/', $data) && strtotime($data);
    }

}

class TimeDataValidation extends AbstractDataValidation {

    public function check($data, $dataset, $key) {
        if (preg_match('/^(\d+):(\d+)$/', $data, $matches)) {
            return (int) $matches[1] < 24 && (int) $matches[2] < 60;
        }
        return false;
    }

}

class PhoneDataValidation extends AbstractDataValidation {

    public function check($data, $dataset, $key) {
        if (preg_match("/^[+]{0,1}[0-9-\s()]{7,18}$/", $data)) {
            return true;
        } else {
            return false;
        }
    }

}

class ValidationStatus {

    public $ok = false;
    public $data = array();
    public $errors = array();

    function __construct($ok = true) {
        $this->ok = $ok;
    }

    function failed($name) {
        return array_key_exists($name, $this->errors);
    }

}

class ValidationError {

    public $message;
    public $key;
    public $data;
    public $constraint;

    function __construct($message, $key = null, $data = null, $constraint = null) {
        $this->message = $message;
        $this->key = $key;
        $this->data = $data;
        $this->constraint = $constraint;
    }

    function __toString() {
        return $this->message;
    }

}

class DataValidator {

    protected $constraints;
    protected $messages;
    public $unsetWiped = false;

    public function __construct($constraints, $messages = array()) {
        $this->constraints = array();

        foreach ($constraints as $datakey => $constraint) {
            $this->constraints[$datakey] = array();

            if (is_string($constraint)) {
                $constraint = explode(' ', $constraint);
            }

            if (is_array($constraint)) {
                foreach ($constraint as $validation) {
                    $name = is_array($validation) ? array_shift($validation) : $validation;
                    $classname = strtoupper($name[0]) . substr($name, 1, strlen($name)) . 'DataValidation';
                    if (!( class_exists($classname) && is_subclass_of($classname, 'AbstractDataValidation') )) {
                        throw new Exception("Некорректный тип правила проверки данных {$validation} ({$classname}).");
                    }
                    $this->constraints[$datakey][$name] = ( is_array($validation) && $validation ) ? eval("return new {$classname}(" . join(', ', array_map(create_function('$arg', 'return var_export( $arg, true );'), $validation)) . ");") : new $classname();
                }
            } else {
                throw new Exception("Некорректное правило проверки данных '{$constraint}'.");
            }
        }

        $this->messages = $messages;
    }

    function process($in, $inplace = false) {
        $data = array();

        /* Важно выполнить непосредственное копирование исходных данных,
          оно избавит от связи по ссылкам,если таковые есть,  а они обязательно есть
          после Meta::vars() */
        foreach ($in as $k => $v) {
            $data[$k] = $v;
        }

        $errors = array();
        $wipes = array();

        // preprocess
        foreach ($this->constraints as $datakey => $validations) {
            if (!array_key_exists($datakey, $data)) {
                $data[$datakey] = '';
            }

            foreach ($validations as $validation) {
                try {
                    $data[$datakey] = $validation->preprocess($data[$datakey], $data, $datakey);
                } catch (ValidationException $e) {
                    if ($e->error) {
                        $errors[$datakey] = $vname;
                    }
                    if ($e->wipe) {
                        $wipes[] = $datakey;
                        $data[$datakey] = '';
                    }
                    if ($e->stop) {
                        break;
                    }
                }
            }
        }

        // check
        foreach ($this->constraints as $datakey => $validations) {
            foreach ($validations as $vname => $validation) {
                try {
                    if (!$validation->check($data[$datakey], $data, $datakey)) {
                        $errors[$datakey] = $vname;
                        break;
                    }
                } catch (ValidationException $e) {
                    if ($e->error) {
                        $errors[$datakey] = $vname;
                    }
                    if ($e->wipe) {
                        $wipes[] = $datakey;
                        $data[$datakey] = '';
                    }
                    if ($e->stop) {
                        break;
                    }
                }
            }
        }

        //postprocess
        foreach ($this->constraints as $datakey => $validations) {
            foreach ($validations as $validation) {
                $data[$datakey] = $validation->postprocess($data[$datakey], $data, $datakey);
            }
        }

        // wipe
        if ($this->unsetWiped) {
            foreach ($wipes as $key) {
                if (array_key_exists($key, $data)) {
                    unset($data[$key]);
                }
            }
        }

        // inplace patch
        if ($inplace) {
            foreach (array_keys($this->constraints) as $key) {
                if (array_key_exists($key, $data)) {
                    $in[$key] = $data[$key];
                } else {
                    unset($in[$key]);
                }
            }
        }

        // error processing
        if ($errors) {
            $niceErrors = array();
            foreach ($errors as $key => $validation) {
                $message = null;
                foreach (array("{$key}.{$validation}", $key) as $k) {
                    if (array_key_exists($k, $this->messages)) {
                        $message = $this->messages[$k];
                        break;
                    }
                }
                if (!$message) {
                    $message = "Не заполнено поле {$key}.";
                }

                $niceErrors[$key] = new ValidationError($message, $key, $data[$key], $validation);
            }
            $errors = $niceErrors;
        }

        $status = new ValidationStatus($errors ? false : true );
        $status->errors = $errors;

        foreach ($data as $key => $value) {
            $status->data[$key] = htmlentities($value, ENT_QUOTES, "UTF-8");
        }

        return $status;
    }

}
