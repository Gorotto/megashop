<div class="uk-form-row">
    <label class="uk-form-label">
        <?= $this->title ?>
    </label>

    <div class="uk-form-controls">
        <select name="<?= $this->name ?>">
            <option value="">…</option>

            <? foreach ($this->choices as $i): ?>
                <option value="<?= $i['id'] ?>">
                    <?= str_repeat("&nbsp;", ($i['lvl'] - 1) * 2) ?>
                    <?= $i['title'] ?>
                </option>
            <? endforeach ?>
        </select>
    </div>
</div>