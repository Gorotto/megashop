<h2>Здравствуйте!</h2>

<p><?= $this->title ?>. Сайт «<?= $_SERVER['HTTP_HOST'] ?>»</p>

<p>
    <? foreach ($this->data as $key => $value): ?>
        <?= $key ?>: <?= $value ? $value : 'не указано' ?><br>
    <? endforeach; ?>
</p>

<p>
    <i style="color: #999">Это сообщение отправлено автоматически, отвечать на него не нужно.</i>
</p>
