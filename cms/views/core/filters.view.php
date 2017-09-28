<div class="uk-grid">
    <? foreach ($this->filters as $filter): ?>
        <div class="uk-width-1-2">
            <?= new View('core/widgets-filter/' . $filter['widget'], compact('filter')) ?>
        </div>
    <? endforeach ?>
</div>


<input type="hidden" name="f" value="1">

<div class="uk-grid">
    <div class="uk-margin-top uk-width-1-1" style="text-align: right">
        <button type="submit" class="uk-button uk-button-primary">
            Выбрать
        </button>
    </div>
</div>