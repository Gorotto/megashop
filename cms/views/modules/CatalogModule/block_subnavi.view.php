<?
$items = array(
    array('path' => '', 'title' => 'Товары'),
    array('path' => '/categories', 'title' => 'Категории'),
    array('path' => '/fields', 'title' => 'Поля'),
    array('path' => '/fieldsets', 'title' => 'Наборы полей'),
);
?>


<? foreach ($items as $i): ?>
    <li class="<? if (Meta::getUriPath() === $this->uri . $i['path']): ?>uk-active<? endif ?>">
        <a href="<?= $this->uri ?><?= $i['path'] ?>/"><?= $i['title'] ?></a>
    </li>
<? endforeach ?>
