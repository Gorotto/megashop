<?

/**
  Класс AND
 */
class NamiQCAND extends NamiQC {

    function __construct(NamiQCOp $left, NamiQCOp $right) {
        $this->optree = new NamiQCAndOp($left->optree, $right->optree);
    }

}
