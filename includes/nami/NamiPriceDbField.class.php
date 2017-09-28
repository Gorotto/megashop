<?

/**
 * поле для цены. decimal
 */
class NamiPriceDbField extends NamiDbField
{

    /**
     * setValue function.
     * @access public
     * @param mixed $string
     * @return void
     */
    function setValue($string, $language = null)
    {
        return parent::setValue((float)$string, $language);
    }

    function getValueForDatabase($language = null)
    {
        $value = $this->value->get($language);
        return is_null($value) ? null : str_replace(',', '.', $value->getForDatabase());
    }

}
