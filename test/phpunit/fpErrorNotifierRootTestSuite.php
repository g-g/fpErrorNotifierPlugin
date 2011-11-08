<?php

class fpErrorNotifierRootTestSuite extends sfBasePhpunitTestSuite {

    /**
     * Dev hook for custom "setUp" stuff
     */
    protected function _start() {
        sfConfig::set('sf_plugin_test_dir', dirname(__FILE__));
        sfConfig::set('app_fp_error_notifier_handler',
                array(
            'class' => 'fpErrorNotifierHandlerIgnore',
            'options' => array('ignore_@' => false)
        ));
        sfConfig::set('app_fp_error_notifier_message',
                array(
            'class' => 'fpErrorNotifierMessage',
            'options' => array()
        ));
        sfConfig::set('app_fp_error_notifier_helper',
                array(
            'class' => 'fpErrorNotifierMessageHelper',
            'options' => array()
        ));
        sfConfig::set('app_fp_error_notifier_decorator',
                array(
            'class' => 'fpErrorNotifierDecoratorHtml',
            'options' => array()
        ));
        sfConfig::set('app_fp_error_notifier_driver',
                array(
            'class' => 'fpErrorNotifierDriverNull',
            'options' => array()
        ));
        $notifier = new fpErrorNotifier(new sfEventDispatcher());
        fpErrorNotifier::setInstance($notifier);
    }

    public function testTest() {
        
    }

}