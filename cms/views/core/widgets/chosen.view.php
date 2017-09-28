<div class="uk-form-row">
    <label class="uk-form-label">
        <?= $this->title ?>
    </label>
    <div class="uk-form-controls">

        <select data-placeholder="выбрать..." multiple="multiple" name="<?= $this->name ?>" class="js-cms_chosen_widget"<? if ($this->has("not_use_own_id")): ?> data-not_use_own_id="true"<? endif; ?>>
            <!-- <option value="">...</option> -->
            <? foreach ($this->choices as $group => $item): ?>
                <option value="<?= $item['id'] ?>"><?= $item['title'] ?></option>
            <? endforeach ?>
        </select>

        <span class="uk-text-primary uk-text-small">
            <?= $this->info ?>
        </span>
    </div>
</div>