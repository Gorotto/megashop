<div class="uk-form-row">
    <label class="uk-form-label">
        <?= $this->title ?>
    </label>
    <div class="uk-form-controls js-custom_edit_widget">
        <input name="<?= $this->name ?>" type="hidden" />

        <a class="uk-button uk-button-primary js-custom_edit_widget__link" href="" target="_blank">
            редактировать содержимое
            <i class="uk-icon-external-link"></i>
        </a>

        <span class="uk-text-primary uk-text-small">
            <?= $this->info ?>
        </span>
    </div>
</div>