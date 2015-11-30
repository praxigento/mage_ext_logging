<?php
/**
 * Wrapper for default Magento 1 logger or for Cascaded Monolog logger.
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\Logging;

use Cascade\Cascade;
use Monolog\Logger as Monolog;
use Psr\Log\LoggerInterface;

class Logger implements LoggerInterface {
    /**
     * Registry to save all absolute paths to config files to prevent double loading.
     *
     * @var array
     */
    private static $_cascadeConfigs = [ ];
    /**
     * @var bool
     */
    private $_isMonologUsed = false;
    /**
     * This is default name for the logger in cascade config file.
     */
    const DEFAULT_LOGGER_NAME = 'main';
    /**
     * Default relative path to cascade config (relative to magento app root folder - BP)
     */
    const DEFAULT_LOGGER_CFG = 'var/log/logging.yaml';
    /** @var  \Symfony\Component\Filesystem\Filesystem */
    private static $_fs;

    /**
     * Name of the logger from cascaded config.
     * @var
     */
    private $_loggerName = self::DEFAULT_LOGGER_NAME;

    private $_mapLevelMono2Zend = [
        Monolog::DEBUG     => \Zend_Log::DEBUG,
        Monolog::INFO      => \Zend_Log::INFO,
        Monolog::NOTICE    => \Zend_Log::NOTICE,
        Monolog::WARNING   => \Zend_Log::WARN,
        Monolog::ERROR     => \Zend_Log::ERR,
        Monolog::CRITICAL  => \Zend_Log::CRIT,
        Monolog::ALERT     => \Zend_Log::ALERT,
        Monolog::EMERGENCY => \Zend_Log::EMERG
    ];

    /**
     * @param string $loggerName
     * @param null   $configFile
     */
    public function __construct(
        $configFile = self::DEFAULT_LOGGER_CFG,
        $loggerName = self::DEFAULT_LOGGER_NAME
    ) {
        $this->_loggerName = $loggerName;
        /* try to use Cascaded Monolog */
        $this->_initLoggerCascade($configFile);

    }

    /**
     * Create new logger instance with given name.
     *
     * @param string $loggerName
     * @param string $configFile
     *
     * @return Logger
     */
    public static function instance($loggerName = self::DEFAULT_LOGGER_NAME, $configFile = self::DEFAULT_LOGGER_CFG) {
        $result = new Logger($configFile, $loggerName);
        return $result;
    }

    /**
     * Use Magento default logger.
     */
    private function  _initLoggerMagento() {
        $this->_isMonologUsed = false;
    }

    /**
     * This is a dirty trick to get Filesystem component that can be mocked in tests.
     *
     * @return \Symfony\Component\Filesystem\Filesystem
     */
    public function getFilesystem() {
        if(is_null(self::$_fs)) {
            self::$_fs = new \Symfony\Component\Filesystem\Filesystem();
        }
        return self::$_fs;
    }

    /**
     * This is a dirty trick to set Filesystem component that is mocked in tests.
     *
     * @param \Symfony\Component\Filesystem\Filesystem $fs
     */
    public function setFilesystem($fs) {
        self::$_fs = $fs;
    }

    /**
     * Configure Cascaded Monolog logger and use it.
     *
     * @param        $configFile
     */
    private function  _initLoggerCascade($configFile) {
        $err = '';
        try {
            $fs = $this->getFilesystem();
            if($fs->isAbsolutePath($configFile)) {
                $fileName = $configFile;
            } else {
                $fileName = BP . '/' . $configFile;
            }
            $realPath = realpath($fileName);
            if($realPath) {
                /* check configs registry and load config if not loaded before */
                if(!isset(self::$_cascadeConfigs[$realPath])) {
                    Cascade::fileConfig($realPath);
                    self::$_cascadeConfigs[$realPath] = true;
                }
                $this->_isMonologUsed = true;
            } else {
                $err = "Cannot open logging configuration file '$fileName'. Default Magento logger is used.";
            }
        } catch
        (\Exception $e) {
            $err = $e->getMessage();
        } finally {
            if(!$this->_isMonologUsed) {
                $this->_initLoggerMagento();
                $this->warning($err);
            }
        }
    }

    /**
     * @return bool 'true' if Monolg Logger is used, 'false' - Magento Default logger is used.
     */
    public function isMonologLogger() {
        return $this->_isMonologUsed;
    }

    /**
     * @return string current logger name.
     */
    public function getName() {
        return $this->_loggerName;
    }

    public function emergency($message, array $context = [ ]) {
        $this->log(Monolog::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = [ ]) {
        $this->log(Monolog::ALERT, $message, $context);
    }

    public function critical($message, array $context = [ ]) {
        $this->log(Monolog::CRITICAL, $message, $context);
    }

    public function error($message, array $context = [ ]) {
        $this->log(Monolog::ERROR, $message, $context);
    }

    public function warning($message, array $context = [ ]) {
        $this->log(Monolog::WARNING, $message, $context);
    }

    public function notice($message, array $context = [ ]) {
        $this->log(Monolog::NOTICE, $message, $context);
    }

    public function info($message, array $context = [ ]) {
        $this->log(Monolog::INFO, $message, $context);
    }

    public function debug($message, array $context = [ ]) {
        $this->log(Monolog::DEBUG, $message, $context);
    }

    /**
     * General  method to write out logs.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, array $context = [ ]) {
        if(!$this->_isMonologUsed) {
            /* prepare log message for Magento default log */
            if(is_array($context) && count($context)) {
                $msg = $this->_loggerName . ': ' . $message . ' [' . str_replace("\n", ' ', var_export($context, true)) . ']';
            } else {
                $msg = $this->_loggerName . ': ' . $message;
            }
            $zendLevel = $this->_getZendLevel($level);
            \Mage::log($msg, $zendLevel);
        } else {
            Cascade::getLogger($this->_loggerName)->log($level, $message, $context);
        }
    }

    /**
     * Map Monolog Level to Zend Level.
     *
     * @param $monologLevel
     *
     * @return int
     */
    private function _getZendLevel($monologLevel) {
        $result = \Zend_Log::DEBUG;
        if(isset($this->_mapLevelMono2Zend[$monologLevel])) {
            $result = $this->_mapLevelMono2Zend[$monologLevel];
        }
        return $result;
    }
}