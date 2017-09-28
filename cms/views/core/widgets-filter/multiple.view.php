<div class="uk-form-row">
    <label class="uk-form-label">
        <?= $this->filter['title'] ?>
    </label>
    <div class="uk-form-controls">

        <select data-placeholder="выбрать..." multiple="multiple" name="<?= $this->filter['field'] ?>[]" class="js-cms_chosen_widget">
            <option value="">...</option>

            <? foreach ($this->filter['available_values'] as $value): ?>
                <option value="<?= $value ?>" <? if (in_array($value, $this->filter['value'])): ?>selected<? endif ?>>
                    <?= $value ?>
                </option>
            <? endforeach ?>
        </select>

    </div>
</div>