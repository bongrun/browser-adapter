<?php

namespace adapter\browser;

use Facebook\WebDriver\Chrome\ChromeDriverService;
use Exception;
use Facebook\WebDriver\Net\URLChecker;
use Facebook\WebDriver\Remote\Service\DriverService;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class ChromeDriverServiceCustom extends ChromeDriverService
{
    /**
     * @var string
     */
    protected $executable;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var array
     */
    protected $args;

    /**
     * @var array
     */
    protected $environment;

    /**
     * @var Process|null
     */
    protected $process;

    public function __construct($host = 'localhost')
    {
        // The environment variable storing the path to the chrome driver executable.

        $exe = getenv(self::CHROME_DRIVER_EXE_PROPERTY);
        $port = 9515; // TODO: Get another port if the default port is used.
        $args = ["--port=$port"];



        $this->executable = self::checkExecutable($exe);
        $this->url = sprintf('http://'.$host.':%d', $port);
        $this->args = $args;
        $this->environment = $_ENV;
    }

    /**
     * @return string
     */
    public function getURL()
    {
        return $this->url;
    }

    /**
     * @return DriverService
     */
    public function start()
    {
        if ($this->process !== null) {
            return $this;
        }

        $processBuilder = (new ProcessBuilder())
            ->setPrefix($this->executable)
            ->setArguments($this->args)
            ->addEnvironmentVariables($this->environment);

        $this->process = $processBuilder->getProcess();
        $this->process->start();

        $checker = new URLChecker();
        $checker->waitUntilAvailable(20 * 1000, $this->url . '/status');

        return $this;
    }

    /**
     * @return DriverService
     */
    public function stop()
    {
        if ($this->process === null) {
            return $this;
        }

        $this->process->stop();
        $this->process = null;

        $checker = new URLChecker();
        $checker->waitUntilUnavailable(3 * 1000, $this->url . '/shutdown');

        return $this;
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        if ($this->process === null) {
            return false;
        }

        return $this->process->isRunning();
    }

    /**
     * Check if the executable is executable.
     *
     * @param string $executable
     * @throws Exception
     * @return string
     */
    protected static function checkExecutable($executable)
    {
        if (!is_file($executable)) {
            throw new Exception("'$executable' is not a file.");
        }

        if (!is_executable($executable)) {
            throw new Exception("'$executable' is not executable.");
        }

        return $executable;
    }
}