<? if ($this->pages): ?>
    <ul class="uk-pagination uk-pagination-left">
        <? if (!$this->pages['prev']['is_current']): ?>
            <li>
                <a href="<?= $this->pages['prev']['uri']; ?>">
                    <i class="uk-icon-angle-double-left"></i>
                </a>
            </li>
        <? endif; ?>

        <? foreach ($this->pages['links'] as $page): ?>
            <? if ($page['is_current']): ?>
                <li class="uk-active"><span><?= $page['title'] ?></span></li>
            <? elseif ($page['is_separator']): ?>
                <li><span>...</span></li>
            <? else: ?>
                <li><a href="<?= $page['uri']; ?>"><?= $page['title']; ?></a></li>
            <? endif; ?>
        <? endforeach; ?>

        <? if (!$this->pages['next']['is_current']): ?>
            <li>
                <a href="<?= $this->pages['next']['uri']; ?>">
                    <i class="uk-icon-angle-double-right"></i>
                </a>
            </li>
        <? endif; ?>
    </ul>
<? endif; ?>