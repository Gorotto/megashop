<!DOCTYPE HTML>
<html class="uk-height-1-1">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= Config::get('common.site_title') ?> - OAMI <?= Builder::$version ?></title>

        <link rel="stylesheet" href="/cms/static/css/login_page.css">
        <link rel="stylesheet" href="/cms/static/3rdparty/uikit/css/uikit.min.css">
        <link rel="stylesheet" href="/cms/static/3rdparty/uikit/css/uikit.almost-flat.min.css">

        <script src="/cms/static/js/jquery-1.11.1.min.js"></script>
        <script src="/cms/static/3rdparty/uikit/js/uikit.min.js"></script>
    </head>

    <body class="uk-height-1-1">

        <div class="uk-vertical-align uk-text-center uk-height-1-1 login_bg_helmet">
            <div class="uk-vertical-align-middle" style="width: 100%;">
                <div class="login_bg_panel"></div>

                <div class="login_box uk-container-center">
                    <div class="login_box__title_box">
                        <img src="/cms/static/images/login_page/oami.png" alt="CMS OAMI" class="login_box__title_img" />
                    </div>

                    <div class="uk-panel login_box__form">

                        <form class="uk-form uk-text-right" method="POST">
                            <div class="uk-form-row">
                                <input
                                    value="<?= Meta::vars('_builder_login') ?>"
                                    name='_builder_login'
                                    class="uk-form-small uk-width-1-1<? if (Session::getInstance()->getLoginAttempt()): ?> uk-animation-shake uk-form-danger<? endif; ?>"
                                    type="text"
                                    placeholder="логин">
                            </div>

                            <div class="uk-form-row uk-margin-small-top">
                                <input
                                    class="uk-form-small uk-width-1-1<? if (Session::getInstance()->getLoginAttempt()): ?> uk-animation-shake uk-form-danger<? endif; ?>"
                                    type="password"
                                    placeholder="пароль"
                                    name='_builder_password'>
                            </div>

                            <div class="uk-form-row uk-clearfix" style="margin-top: 25px;">
                                <? if (Session::getInstance()->getLoginAttempt()): ?>
                                    <div class="uk-float-left uk-text-left">
                                        <span class="uk-text-warning">
                                            Неверный логин или пароль
                                        </span>
                                    </div>
                                <? endif ?>

                                <div class="uk-float-right">
                                    <button class="uk-button uk-button-success">
                                        войти
                                        <i class="uk-icon uk-icon-sign-in"></i>
                                    </button>
                                </div>

                            </div>
                        </form>

                    </div>
                </div>
            </div>

        </div>

        <script>
            $(function() {
                $('input[name=_builder_login]').focus().select();
            });
        </script>

    </body>
</html>
