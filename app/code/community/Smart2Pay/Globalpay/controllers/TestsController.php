<?php
class Smart2Pay_Globalpay_TestsController extends Mage_Core_Controller_Front_Action
{
    public function indexAction(){
        $method = Mage::getModel('globalpay/method');
        $collection = $method->getCollection();
        foreach ($collection as $m) {
            echo '"' . $m->getDisplayName() . ' description",' . '"' . htmlspecialchars(str_replace('"', '""', $m->getDescription())) . '"' . '<br />';
        }
        die;
    }
}
?>