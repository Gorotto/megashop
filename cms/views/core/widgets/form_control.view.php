<div class="uk-form-row js-form_control_widget">
    <label class="uk-form-label">
        <?= $this->title ?>
    </label>
    <div class="uk-form-controls">

        <select name="<?= $this->name ?>" class="uk-width-1-1" <?= ($this->rules) ?>>
            <? foreach ($this->values as $key => $data): ?>
                <option value="<?= $key ?>"><?= $data['title'] ?></option>
            <? endforeach ?>
        </select>

        <span class="uk-text-primary uk-text-small">
            <?= $this->info ?>
        </span>
    </div>
</div>