<? if ($this->pages): ?>

    <aside class="paginator">

        <? if (!$this->pages['prev']['is_current']): ?>
            <a href="<?= $this->pages['prev']['uri'] ?>" class="paginator__prev">
                <span class="icon icon-prev"></span>
            </a>
        <? endif; ?>

        <? foreach ($this->pages['links'] as $i): ?>
            <? if ($i['is_separator']): ?>
                <span><?= $i['title'] ?></span>
            <? elseif ($i['is_current']): ?>
                <a class="paginator__current" href="<?= $i['uri'] ?>"><?= $i['title'] ?></a>
            <? else: ?>
                <a href="<?= $i['uri'] ?>"><?= $i['title'] ?></a>
            <? endif; ?>
        <? endforeach; ?>

        <? if (!$this->pages['next']['is_current']): ?>
            <a href="<?= $this->pages['next']['uri'] ?>" class="paginator__next">
                <span class="paginator__label">cледующая</span>
                <span class="paginator__icon">
                    <span class="icon icon-next"></span>
                </span>
            </a>
        <? endif; ?>

    </aside>

<? endif; ?>
