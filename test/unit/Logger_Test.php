<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\Logging;

include_once(__DIR__ . '/phpunit_bootstrap.php');

class Call_ManualTest extends \PHPUnit_Framework_TestCase {

    public function test_constructor_Mage() {
        $log = new Logger();
        $this->assertTrue($log instanceof Logger);
    }

    public function test_constructor_Monolog() {
        $log = new Logger();
        $this->assertTrue($log instanceof Logger);
        $this->assertEquals(Logger::DEFAULT_LOGGER_NAME, $log->getName());
        $this->assertTrue($log->isMonologLogger());
        $logTest = Logger::instance('test');
        $this->assertEquals('test', $logTest->getName());
        $this->assertTrue($log->isMonologLogger());
        $logMage = Logger::instance('magento', 'some/file/that/is/not/exist');
        $this->assertEquals('magento', $logMage->getName());
        $this->assertFalse($logMage->isMonologLogger());
    }

    public function test_methods_Monolog() {
        $log = new Logger();
        $context = [ 'test' => true, 'env' => [ 'param1' => 'value1' ] ];
        $log->debug('debug', $context);
        $log->info('info', $context);
        $log->notice('notice', $context);
        $log->warning('warning', $context);
        $log->error('error', $context);
        $log->alert('alert', $context);
        $log->critical('critical', $context);
        $log->emergency('emergency', $context);
    }

    public function test_initLoggerCascade_absolutePath() {
        $CONFIG_FILE_NAME = __DIR__ . '/logging.yaml';
        $LOGGER_NAME = 'defaultLoggerName';
        /**
         * Perform testing.
         */
        $logger = new Logger($CONFIG_FILE_NAME, $LOGGER_NAME);
        $this->assertTrue($logger instanceof \Praxigento\Logging\Logger);
    }

    public function test_constructor_exception() {
        $CONFIG_FILE_NAME = __DIR__ . '/logging_exception.yaml';
        $LOGGER_NAME = 'defaultLoggerName';
        /**
         * Perform testing.
         */
        $logger = new Logger($CONFIG_FILE_NAME, $LOGGER_NAME);
        $this->assertTrue($logger instanceof \Praxigento\Logging\Logger);
    }

    public function test_methods_Magento() {
        $CONFIG_FILE_NAME = __DIR__ . '/logging_exception.yaml';
        $LOGGER_NAME = 'defaultLoggerName';
        /**
         * Perform testing.
         */
        $log = new Logger($CONFIG_FILE_NAME, $LOGGER_NAME);
        $context = [ 'test' => true, 'env' => [ 'param1' => 'value1' ] ];
        $log->debug('debug', $context);
        $log->info('info', $context);
        $log->notice('notice', $context);
        $log->warning('warning', $context);
        $log->error('error', $context);
        $log->alert('alert', $context);
        $log->critical('critical', $context);
        $log->emergency('emergency', $context);
    }

}