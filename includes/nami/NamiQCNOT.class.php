<?

/**
  Класс NOT
 */
class NamiQCNOT extends NamiQC {

    function __construct(NamiQCOp $op) {
        $this->optree = new NamiQCNotOp($op->optree);
    }

}
