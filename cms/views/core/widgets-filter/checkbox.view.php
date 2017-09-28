<div class="uk-form-row">
    <label class="uk-form-label" for="id_<?= $this->filter['field'] ?>">
        <?= $this->filter['title'] ?>
    </label>
    <div class="uk-form-controls">
        <input type="checkbox" name="<?= $this->filter['field'] ?>" value="1" <? if($this->filter['value']): ?>checked="checked"<? endif ?> id="id_<?= $this->filter['field'] ?>" />
    </div>
</div>