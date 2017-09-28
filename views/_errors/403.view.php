<? $this->beginFilter('HtmlFilter::spaceless'); ?>
<!DOCTYPE HTML>
<html lang="ru-RU">
    <head>
        <meta charset="UTF-8">
        <link rel="icon" href="/favicon.ico" type="image/x-icon">
        <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
        <link rel="stylesheet" href="/static/css/style.css">
        <title>Ошибка 403 - <?= Config::get('common.site_title'); ?></title>
    </head>
    <body>
        <h1>Ошибка 403</h1>
        <p class="form_annotation">
            Доступ к данному ресурсу ограничен.<br>
            Перейдите на <a href="/">главную страницу</a> и попробуйте найти то, что вас интересовало.
        </p>
    </body>
</html>
<? $this->endFilter(); ?>