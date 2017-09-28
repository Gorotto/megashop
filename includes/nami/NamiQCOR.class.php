<?

/**
  Класс OR
 */
class NamiQCOR extends NamiQC {

    function __construct(NamiQCOp $left, NamiQCOp $right) {
        $this->optree = new NamiQCOrOp($left->optree, $right->optree);
    }

}
