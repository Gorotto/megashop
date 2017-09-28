<? $this->beginFilter('HtmlFilter::spaceless'); ?>
<!DOCTYPE HTML>
<html lang="ru-RU">
    <head>
        <meta charset="UTF-8">
        <link rel="icon" href="/favicon.ico" type="image/x-icon">
        <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
        <link rel="stylesheet" href="/static/css/style.css">
        <title>Ошибка 404 - <?= Config::get('common.site_title'); ?></title>
    </head>
    <body>
        <h1>Ошибка 404</h1>
        <p class="form_annotation">
            Вы перешли по неверной ссылке или устаревшей закладке.<br>
            Перейдите на <a href="/">главную страницу</a> и попробуйте найти то, что вас интересовало.
        </p>
    </body>
</html>
<? $this->endFilter(); ?>