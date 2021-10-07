<?php

namespace cr1gger\EasySelenium;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\WebDriverCapabilityType;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\WebDriverCommand;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class Selenium
{
    /**
     * @var RemoteWebDriver
     * Chrome driver instance
     */
    private RemoteWebDriver $driver;

    /**
     * Server Url
     * @var string
     */
    private string $host = 'http://localhost:4444/wd/hub';

    /**
     * Debug mod, allows you to run selenium in UI rendering mode
     * @var bool
     */
    private bool $debugging = false;

    /**
     * Specifies the platform chrome is running on
     * @var string
     */
    private string $platform = 'Linux';

    /**
     * Default arguments for start chrome
     * @var array|string[]
     */
    private array $arguments = [
        '--headless', '--disable-gpu', '--no-sandbox',
        '--window-size=1920,1080', '--accept-ssl-certs=true'
    ];

    /**
     * Default arguments for start chrome in debug mode
     * @var array
     */
    private array $debug_args = [];

    /**
     * Timeout connection in milliseconds
     * @var int
     */
    private int $connection_timeout_in_ms;

    /**
     * Request timeout in milliseconds
     * @var int
     */
    private int $request_timeout_in_ms;

    /**
     * Array with proxy. Example:
     * ```
     * [
     *      'httpProxy' => 'http://localhost:5480',
     *      'sslProxy' => 'http://localhost:5443',
     *      "proxyType" => "MANUAL",
     *      "socksUsername" => 'username',
     *      "socksPassword" => 'password1234'
     *]
     * ```
     * @var array
     */
    private array $proxy;

    private Logger $logger;

    public function __construct(string $who_run)
    {
        if (empty($who_run)) throw new \Exception('Selenium: who_run cannot be empty');
        $this->who_run = $who_run;
    }

    public function close()
    {
        if ($this->driver) $this->driver->quit();
    }

    public function setHost(string $host)
    {
        $this->host = $host;
        return $this;
    }

    public function setArguments(array $arguments)
    {
        $this->arguments = array_merge($this->arguments, $arguments);
        return $this;
    }

    public function addArgument(string $argument)
    {
        $this->arguments[] = $argument;
        return $this;
    }

    public function setDebug(bool $debug, $debug_args = [])
    {
        $this->debugging = $debug;
        $this->debug_args = $debug_args;
        return $this;
    }

    public function setPlatform(string $platform)
    {
        $this->platform = $platform;
        return $this;
    }

    public function setConnectionTimeout($timeout)
    {
        $this->connection_timeout_in_ms = $timeout;
        return $this;
    }

    public function setRequestTimeout($timeout)
    {
        $this->request_timeout_in_ms = $timeout;
        return $this;
    }

    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
        return $this;
    }

    public function start()
    {
        $capabilities = DesiredCapabilities::chrome();
        if ($this->proxy) {
            $capabilities->setCapability(WebDriverCapabilityType::PROXY, $this->proxy);
        }
        $options = new ChromeOptions();

        if ($this->debugging) $options->addArguments($this->debug_args);
        else $options->addArguments($this->arguments);

        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
        $capabilities->setPlatform($this->platform);
        $this->driver = RemoteWebDriver::create($this->host, $capabilities, $this->connection_timeout_in_ms, $this->request_timeout_in_ms);

        return $this;
    }

//    private function registerChrome()
//    {
//        $this->chrome_session = new ChromeSession();
//        $this->chrome_session->parser_title = $this->who_run;
//        $this->chrome_session->session_id = $this->driver->getSessionID();
//        $this->chrome_session->last_ping = time();
//        $this->chrome_session->save();
//    }

    public function get($url)
    {
        $this->log();
        $this->driver->get($url);
    }

    public function querySelector($selector)
    {
        $this->log();

        return $this->driver->findElement(
            WebDriverBy::cssSelector($selector)
        );
    }

    public function querySelectorAll($selector)
    {
        $this->log();

        return $this->driver->findElements(
            WebDriverBy::cssSelector($selector)
        );
    }

    public function parentSelector($childElement)
    {
        $this->log();

        return $childElement->findElement(
            WebDriverBy::xpath('./..')
        );
    }

    public function childSelector($parentElement, $needle)
    {
        $this->log();

        return $parentElement->findElement(
            WebDriverBy::cssSelector($needle)
        );
    }

    public function childSelectorAll($parentElement, $needle)
    {
        $this->log();

        return $parentElement->findElements(
            WebDriverBy::cssSelector($needle)
        );
    }

    public function waitElement($selector, $timeout = 10)
    {
        $this->log();
        $this->driver->wait($timeout)->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector($selector)));
    }

    public function waitUntil($callback, $result, $timeout = 30)
    {
        $this->log();
        $this->driver->wait($timeout)->until(
            $callback,
            $result
        );
    }

    public function writeToInput($input, $text)
    {
        $this->log();

        $symbols = mb_str_split($text);
        foreach ($symbols as $symbol) {
            $input->sendKeys($symbol);
            usleep(rand(3, 5) * 10000);
        }
    }

    public function executeScript($js)
    {
        $this->log();
        return $this->driver->executeScript($js);
    }

    public function getCurrentUrl()
    {
        $this->log();
        return $this->driver->getCurrentURL();
    }

    public function getConsoleLog()
    {
        $this->log();
        return $this->driver->manage()->getLog('browser');
    }

    public function refreshPage()
    {
        $this->log();
        $this->driver->navigate()->refresh();
    }

    public static function closeChromeBySession(string $session)
    {
        $commandKill = new WebDriverCommand($session, 'quit', []);
        RemoteWebDriver::createBySessionID($session)->getCommandExecutor()->execute($commandKill);
    }

    public function getDriver($are, $you, $sure)
    {
        # не стоит обращаться на прямую к драйверу
        # потому что там он не будет пинговаться.
        # только если вы точно уверены что он вам нужен
        # лучше создать нужный метод с пингом здесь, в этом классе =)

        if ($are && $you && $sure) return $this->driver;
        else return false;
    }

    public function isVisibleElement($selector)
    {
        try {
            $element = $this->querySelector($selector);
            return $element->isDisplayed();
        } catch (\Throwable $e) {
            return true;
        }
    }

    public function isExistsElement($selector)
    {
        try {
            $element = $this->querySelector($selector);
            if ($element) return true;
            else return false;
        } catch (\Throwable $e) {
            return true;
        }
    }

    public function takeScreenshot(string $path)
    {
        $this->log();
        $this->driver->takeScreenshot($path);
    }

    public function getHTML()
    {
        $this->log();
        return $this->driver->getPageSource();
    }

    public function pressKey($key)
    {
        $this->driver->getKeyboard()->pressKey($key);
    }

    public function clearAllCookies()
    {
        $this->log();
        $this->driver->manage()->deleteAllCookies();
    }
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
        return $this;
    }
    protected function log()
    {
        if ($this->logger)
        {
            echo 'log...';
        }
    }
}