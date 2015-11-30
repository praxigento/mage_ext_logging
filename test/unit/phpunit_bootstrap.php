<?php
/* Start M1 app */
include_once(__DIR__ . '/../../../../../htdocs/app/Mage.php');
// Start Magento application
Mage::app('default');
// Avoid issues "Headers already send"
session_start();
