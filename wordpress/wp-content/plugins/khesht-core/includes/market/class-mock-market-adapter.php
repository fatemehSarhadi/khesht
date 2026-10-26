<?php

if (!defined('ABSPATH')) {
    exit;
}

class Khesht_Mock_Market_Adapter extends Khesht_Market_Adapter {
    public function quotes() {
        return array(
            'updated_at' => gmdate('c'),
            'source' => 'mock',
            'items' => array(
                array('id' => 'gold', 'label' => 'طلا', 'value' => '—', 'unit' => 'تومان'),
                array('id' => 'coin', 'label' => 'سکه', 'value' => '—', 'unit' => 'تومان'),
                array('id' => 'usd', 'label' => 'دلار', 'value' => '—', 'unit' => 'تومان'),
                array('id' => 'eur', 'label' => 'یورو', 'value' => '—', 'unit' => 'تومان'),
            ),
        );
    }
}
