<div class="uk-form-row">
    <span class="uk-form-label">
        <?= $this->filter['title'] ?>
    </span>

    <div class="uk-form-controls">
        <label ><input type="radio" name="<?= $this->filter['field'] ?>" value="yes" <? if ("yes" == $this->filter['value']): ?>checked="checked"<? endif ?>> да</label>
        <label ><input type="radio" name="<?= $this->filter['field'] ?>" value="no" <? if ("no" == $this->filter['value']): ?>checked="checked"<? endif ?>> нет</label>
        <label ><input type="radio" name="<?= $this->filter['field'] ?>" value="whatever" <? if ("whatever" == $this->filter['value'] || !$this->filter['value']): ?>checked="checked"<? endif ?>> неважно</label>
    </div>
</div>
