<html>
    <body>
        <h2>При открытии страницы произошла ошибка.</h2>
        <p>Запрашиваемую страницу открыть не удалось. Пожалуйста, попробуйте зайти позже.</p>
        <p>Если ошибка повторяется — обратитесь к администратору сайта.</p>
        <p>
            <i>
                <small>
                    <? if ($this->has('message') && $this->message): ?>
                        Сообщение: <?= $this->message ?><br/>
                    <? endif ?>
                    Адрес: <?= $_SERVER['REQUEST_URI'] ?>
                </small>
            </i>
        </p>
    </body>
</html>