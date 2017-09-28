<div class="uk-form-row">
    <label class="uk-form-label">
        <?= $this->title ?>
    </label>
    <div class="uk-form-controls">
        <div class="uk-form-icon">
            <i class="uk-icon-calendar"></i>
            <input type="text" datepicker="yes" name="<?= $this->name ?>" />
        </div>

        <br>
        <span class="uk-text-primary uk-text-small">
            <?= $this->info ?>
        </span>
    </div>
</div>