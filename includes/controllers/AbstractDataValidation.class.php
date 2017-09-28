<?php

class AbstractDataValidation {
    public function preprocess( $data, $dataset, $key ) {
        return $data;
    }
    public function check( $data, $dataset, $key ) {
        return true;
    }
    public function postprocess( $data, $dataset, $key ) {
        return $data;
    }
}

