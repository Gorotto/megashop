<div class="uk-form-row js-map_widget">
    <label class="uk-form-label">
        <?= $this->title ?>
    </label>

    <div class="uk-form-controls">
        <div class="builder-map_widget js-map_widget__map">
            <div style="padding-top: 180px; text-align: center; color: #888;">
                загружаю карту...
            </div>
        </div>

        <input type="text" class="uk-width-1-1 uk-form-small js-map_widget__coord" name="<?= $this->name ?>">

        <span class="uk-text-primary uk-text-small">
            <?= $this->info ?>
        </span>
    </div>
</div>