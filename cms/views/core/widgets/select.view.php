<div class="uk-form-row">
    <label class="uk-form-label">
        <?= $this->title ?>
    </label>
    <div class="uk-form-controls">

        <select name="<?= $this->name ?>" class="uk-width-1-1">
            <option value="">…</option>
            <? foreach ($this->choices as $group => $item): ?>
                <option value="<?= $item['id'] ?>"><?= $item['title'] ?></option>
            <? endforeach ?>
        </select>

        <span class="uk-text-primary uk-text-small">
            <?= $this->info ?>
        </span>
    </div>
</div>