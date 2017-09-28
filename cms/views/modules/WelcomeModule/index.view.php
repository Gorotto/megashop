<div class="uk-margin-large">
    <?php if (Builder::developmentMode()): ?>
        <div class="uk-badge uk-badge-danger uk-badge-big uk-margin-top">
            DEVELOPER MODE
        </div>
    <?php endif; ?>
</div>

<div class="uk-margin-large">&nbsp;</div>

<div class="uk-margin-large">
    <?php if ($this->modules): ?>

        <?php $el_counts = 0; ?>
        <div class="uk-grid uk-grid-small" data-uk-grid-match="{target:'.uk-panel'}">
            <?php foreach ($this->modules as $module): ?>
                <?php $el_counts++; ?>

                <div class="uk-width-1-6">
                    <a class="uk-panel uk-panel-box uk-panel-hover" href="/cms/<?= $module->name ?>/" title='<?= $this->HE($module->title) ?>'>
                        <div class="uk-grid">

                            <div class="uk-width-1-4">
                                <h3 class="uk-panel-title">
                                    <i class="uk-icon-<?= $module->icon_name ?> uk-icon-small"></i>
                                </h3>
                            </div>

                            <div class="uk-width-3-4">
                                <span>
                                    <?= mb_strtolower($module->title) ?>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>

                <?php if ($el_counts == 6): ?>
                </div>
                <div class="uk-grid uk-grid-small" data-uk-grid-match="{target:'.uk-panel'}">
                    <?php $el_counts = 0; ?>
                <?php endif; ?>
            <?php endforeach ?>
        </div>

    <?php else: ?>
        <h4 class='uk-text-warning'>
            Для вашей учетной записи нет доступных модулей.
        </h4>
    <?php endif; ?>
</div>
