<?php

namespace adapter;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\DriverCommand;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use interfaces\ProxyDataInterface;
use ZipArchive;

class BrowserAdapter
{
    /** @var RemoteWebDriver */
    private $driver;
    /** @var ProxyDataInterface */
    private $proxy;
    /** @var string */
    private $host;
    /** @var bool */
    private $isInit = false;

    public function __construct(ProxyDataInterface $proxy = null, $host = 'http://s_selenium:4444/wd/hub')
    {
        $this->proxy = $proxy;
        $this->host = $host;
    }

    private function init()
    {
        if ($this->isInit) {
            return;
        }
        $caps = DesiredCapabilities::chrome();
        $options = new ChromeOptions();
        $options->addArguments(['--user-agent=Mozilla/5.0 (iPad; CPU OS 9_0 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/9.0 Mobile/13A340 Safari/600.1.4']);
        $caps->setCapability(ChromeOptions::CAPABILITY, $options);
        if ($this->proxy instanceof ProxyDataInterface && $this->proxy->getIp()) {
            $pluginForProxyLogin = '/tmp/a' . uniqid() . '.zip';
            $zip = new ZipArchive();
            $zip->open($pluginForProxyLogin, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $zip->addFile(__DIR__ . '/browser/plugin/proxy/manifest.json', 'manifest.json');
            $background = file_get_contents(__DIR__ . '/browser/plugin/proxy/background.js');
            $background = str_replace(['%proxy_host', '%proxy_port', '%username', '%password'], [$this->proxy->getIp(), $this->proxy->getPort(), $this->proxy->getUser(), $this->proxy->getPassword()], $background);
            $zip->addFromString('background.js', $background);
            $zip->close();
            $options = new ChromeOptions();
            $options->addExtensions([$pluginForProxyLogin]);
            $caps->setCapability(ChromeOptions::CAPABILITY, $options);
        }
        $this->driver = RemoteWebDriver::create($this->host, $caps);
        $this->isInit = true;
    }

    /**
     * @param string $url
     *
     * @return $this
     */
    public function get(string $url)
    {
        $this->init();
        $this->driver->get($url);
        return $this;
    }

    /**
     * @param WebDriverBy $webDriverBy
     *
     * @return RemoteWebElement
     */
    public function e(WebDriverBy $webDriverBy)
    {
        return $this->driver->findElement($webDriverBy);
    }

    /**
     * @param int $timeout_in_second
     * @param int $interval_in_millisecond
     *
     * @return \Facebook\WebDriver\WebDriverWait
     */
    public function w($timeout_in_second = 30, $interval_in_millisecond = 250)
    {
        return $this->driver->wait($timeout_in_second, $interval_in_millisecond);
    }

    /**
     * @param WebDriverBy $webDriverBy
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebElement[]
     */
    public function es(WebDriverBy $webDriverBy)
    {
        return $this->driver->findElements($webDriverBy);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->driver->getTitle();
    }

    /**
     * @return array
     */
    public function getCookies()
    {
        $cookies = [];
        $cookies = $this->driver->execute(DriverCommand::GET_ALL_COOKIES);
//        foreach ($this->driver->manage()->getCookies() as $cookie) {
//            $cookies = (array)$cookie;
//        }
        return $cookies;
    }

    public function getCurrentURL()
    {
        return $this->driver->getCurrentURL();
    }

    public function close()
    {
        try {
            try {
                try {
                    $this->driver->quit();
                } catch (\Exception $e) {
                }
            } catch (\Error $e) {
            }
        } catch (\Throwable $e) {
        }
    }

    public function getDomain($url)
    {
        return parse_url($url, PHP_URL_HOST);
    }
}