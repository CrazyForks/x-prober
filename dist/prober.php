<?php
namespace InnStudio\Prober\Components\PreDefine;
$version = phpversion();
version_compare($version, '5.4.0','<') && exit("PHP 5.4+ is required. Currently installed version is: {$version}");
\define('XPROBER_TIMER', \microtime(true));
\define('XPROBER_IS_DEV', false);
\define('XPROBER_DIR', __DIR__);
namespace InnStudio\Prober\Components\PhpInfo;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class Conf extends PhpInfoConstants
{
    public function __construct()
    {
        EventsApi::on('conf', function (array $conf) {
            if (XconfigApi::isDisabled($this->ID)) {
                return $conf;
            }
            $conf[$this->ID] = array(
                'version' => \PHP_VERSION,
                'sapi' => \PHP_SAPI,
                'displayErrors' => (bool) \ini_get('display_errors'),
                'errorReporting' => (int) \ini_get('error_reporting'),
                'memoryLimit' => (string) \ini_get('memory_limit'),
                'postMaxSize' => (string) \ini_get('post_max_size'),
                'uploadMaxFilesize' => (string) \ini_get('upload_max_filesize'),
                'maxInputVars' => (int) \ini_get('max_input_vars'),
                'maxExecutionTime' => (int) \ini_get('max_execution_time'),
                'defaultSocketTimeout' => (int) \ini_get('default_socket_timeout'),
                'allowUrlFopen' => (bool) \ini_get('allow_url_fopen'),
                'smtp' => (bool) \ini_get('SMTP'),
                'disableFunctions' => XconfigApi::isDisabled('phpDisabledFunctions') ? array() : array_filter(explode(',', (string) \ini_get('disable_functions'))),
                'disableClasses' => XconfigApi::isDisabled('phpDisabledClasses') ? array() : array_filter(explode(',', (string) \ini_get('disable_classes'))),
            );
            return $conf;
        });
    }
}
namespace InnStudio\Prober\Components\PhpInfo;
use InnStudio\Prober\Components\Config\ConfigApi;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Rest\RestResponse;
use InnStudio\Prober\Components\Rest\StatusCode;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class FetchLatestPhpVersion extends PhpInfoConstants
{
    public function __construct()
    {
        EventsApi::on('init', function ($action) {
            if (XconfigApi::isDisabled($this->ID)) {
                return $action;
            }
            if ('latest-php-version' !== $action) {
                return $action;
            }
            $response = new RestResponse();
            $content = file_get_contents('https://www.php.net/releases/?json');
            if ( ! $content) {
                $response->setStatus(StatusCode::$NOT_FOUND)->end();
            }
            $versions = json_decode($content, true);
            if ( ! $versions) {
                $response->setStatus(StatusCode::$NOT_FOUND)->end();
            }
            $version = isset($versions[ConfigApi::$LATEST_PHP_STABLE_VERSION]['version']) ? $versions[ConfigApi::$LATEST_PHP_STABLE_VERSION]['version'] : '';
            if ( ! $version) {
                $response->setStatus(StatusCode::$NOT_FOUND)->end();
            }
            $response->setData(array(
                'version' => $version,
                'date' => $versions[ConfigApi::$LATEST_PHP_STABLE_VERSION]['date'],
            ))->json()->end();
        });
    }
}
namespace InnStudio\Prober\Components\PhpInfo;
final class PhpInfo
{
    public function __construct()
    {
        new Conf();
        new FetchLatestPhpVersion();
    }
}
namespace InnStudio\Prober\Components\PhpInfo;
class PhpInfoConstants
{
    protected $ID = 'phpInfo';
}
namespace InnStudio\Prober\Components\Events;
final class EventsApi
{
    private static $events = array();
    private static $PRIORITY_ID = 'priority';
    private static $CALLBACK_ID = 'callback';
    public static function on($name, $callback, $priority = 10)
    {
        if ( ! isset(self::$events[$name])) {
            self::$events[$name] = array();
        }
        self::$events[$name][] = array(
            self::$PRIORITY_ID => $priority,
            self::$CALLBACK_ID => $callback,
        );
    }
    public static function emit()
    {
        $args = \func_get_args();
        $name = $args[0];
        $return = isset($args[1]) ? $args[1] : null;
        unset($args[0], $args[1]);
        $events = isset(self::$events[$name]) ? self::$events[$name] : false;
        if ( ! $events) {
            return $return;
        }
        $sortArr = array();
        foreach ($events as $k => $filter) {
            $sortArr[$k] = $filter[self::$PRIORITY_ID];
        }
        array_multisort($sortArr, $events);
        foreach ($events as $filter) {
            $return = \call_user_func_array($filter[self::$CALLBACK_ID], array($return, $args));
        }
        return $return;
    }
}
namespace InnStudio\Prober\Components\PhpInfoDetail;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class PhpInfoDetail extends PhpInfoDetailConstants
{
    public function __construct()
    {
        EventsApi::on('init', function ($action) {
            if (XconfigApi::isDisabled($this->ID)) {
                return $action;
            }
            if ($this->ID !== $action) {
                return $action;
            }
            phpinfo();
            exit;
        });
    }
}
namespace InnStudio\Prober\Components\PhpInfoDetail;
class PhpInfoDetailConstants
{
    protected $ID = 'phpInfoDetail';
}
namespace InnStudio\Prober\Components\Ping;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class Conf extends PingConstants
{
    public function __construct()
    {
        EventsApi::on('conf', function (array $conf) {
            if (XconfigApi::isDisabled($this->ID)) {
                return $conf;
            }
            $conf[$this->ID] = array();
            return $conf;
        });
    }
}
namespace InnStudio\Prober\Components\Ping;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Rest\RestResponse;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class Ping extends PingConstants
{
    public function __construct()
    {
        new Conf();
        EventsApi::on('init', function ($action) {
            if (XconfigApi::isDisabled($this->ID)) {
                return $action;
            }
            if ($this->ID !== $action) {
                return $action;
            }
            $response = new RestResponse(array(
                'time' => \defined('XPROBER_TIMER') ? microtime(true) - XPROBER_TIMER : 0,
            ));
            $response->json()->end();
        });
    }
}
namespace InnStudio\Prober\Components\Ping;
class PingConstants
{
    protected $ID = 'ping';
}
namespace InnStudio\Prober\Components\PhpExtensions;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class Conf extends PhpExtensionsConstants
{
    public function __construct()
    {
        EventsApi::on('conf', function (array $conf) {
            if (XconfigApi::isDisabled($this->ID)) {
                return $conf;
            }
            $jitEnabled = false;
            if (\function_exists('opcache_get_status')) {
                $status = opcache_get_status();
                if (isset($status['jit']['enabled']) && true === $status['jit']['enabled']) {
                    $jitEnabled = true;
                }
            }
            $conf[$this->ID] = array(
                'redis' => \extension_loaded('redis') && class_exists('Redis'),
                'sqlite3' => \extension_loaded('sqlite3') && class_exists('Sqlite3'),
                'memcache' => \extension_loaded('memcache') && class_exists('Memcache'),
                'memcached' => \extension_loaded('memcached') && class_exists('Memcached'),
                'opcache' => \function_exists('opcache_get_status'),
                'opcacheEnabled' => $this->isOpcEnabled(),
                'opcacheJitEnabled' => $jitEnabled,
                'swoole' => \extension_loaded('swoole') && \function_exists('swoole_version'),
                'imagick' => \extension_loaded('imagick') && class_exists('Imagick'),
                'gmagick' => \extension_loaded('gmagick'),
                'exif' => \extension_loaded('exif') && \function_exists('exif_imagetype'),
                'fileinfo' => \extension_loaded('fileinfo'),
                'simplexml' => \extension_loaded('simplexml'),
                'sockets' => \extension_loaded('sockets') && \function_exists('socket_accept'),
                'mysqli' => \extension_loaded('mysqli') && class_exists('mysqli'),
                'zip' => \extension_loaded('zip') && class_exists('ZipArchive'),
                'mbstring' => \extension_loaded('mbstring') && \function_exists('mb_substr'),
                'phalcon' => \extension_loaded('phalcon'),
                'xdebug' => \extension_loaded('xdebug'),
                'zendOptimizer' => \function_exists('zend_optimizer_version'),
                'ionCube' => \extension_loaded('ioncube loader'),
                'sourceGuardian' => \extension_loaded('sourceguardian'),
                'ldap' => \function_exists('ldap_connect'),
                'curl' => \function_exists('curl_init'),
                'loadedExtensions' => XconfigApi::isDisabled('phpExtensionsLoaded') ? array() : get_loaded_extensions(),
            );
            return $conf;
        });
    }
    private function isOpcEnabled()
    {
        $isOpcEnabled = \function_exists('opcache_get_configuration');
        if ($isOpcEnabled) {
            $isOpcEnabled = opcache_get_configuration();
            $isOpcEnabled = isset($isOpcEnabled['directives']['opcache.enable']) && true === $isOpcEnabled['directives']['opcache.enable'];
        }
        return $isOpcEnabled;
    }
}
namespace InnStudio\Prober\Components\PhpExtensions;
class PhpExtensionsConstants
{
    protected $ID = 'phpExtensions';
}
namespace InnStudio\Prober\Components\PhpExtensions;
final class PhpExtensions
{
    public function __construct()
    {
        new Conf();
    }
}
namespace InnStudio\Prober\Components\Footer;
use InnStudio\Prober\Components\Events\EventsApi;
final class Footer
{
    private $ID = 'footer';
    public function __construct()
    {
        EventsApi::on('conf', function (array $conf) {
            $conf[$this->ID] = array(
                'memUsage' => memory_get_usage(),
                'time' => microtime(true) - (\defined('XPROBER_TIMER') ? XPROBER_TIMER : 0),
            );
            return $conf;
        }, \PHP_INT_MAX);
    }
}
namespace InnStudio\Prober\Components\TemperatureSensor;
use Exception;
use InnStudio\Prober\Components\Config\ConfigApi;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Rest\RestResponse;
use InnStudio\Prober\Components\Rest\StatusCode;
final class TemperatureSensor
{
    public function __construct()
    {
        EventsApi::on('init', function ($action) {
            if ('temperature-sensor' !== $action) {
                return $action;
            }
            $response = new RestResponse();
            $items = $this->getItems();
            if ($items) {
                $response->setData($items)->json()->end();
            }
            $cpuTemp = $this->getCpuTemp();
            if ( ! $cpuTemp) {
                $response->setStatus(StatusCode::$NO_CONTENT);
            }
            $items[] = array(
                'id' => 'cpu',
                'name' => 'CPU',
                'celsius' => round((float) $cpuTemp / 1000, 2),
            );
            $response->setData($items)->json()->end();
        });
    }
    private function curl($url)
    {
        if ( ! \function_exists('curl_init')) {
            return;
        }
        $ch = curl_init();
        curl_setopt_array($ch, array(
            \CURLOPT_URL => $url,
            \CURLOPT_RETURNTRANSFER => true,
        ));
        $res = curl_exec($ch);
        curl_close($ch);
        return (string) $res;
    }
    private function getItems()
    {
        $items = array();
        foreach (ConfigApi::$APP_TEMPERATURE_SENSOR_PORTS as $port) {
            // check curl
            $res = $this->curl(ConfigApi::$APP_TEMPERATURE_SENSOR_URL . ":{$port}");
            if ( ! $res) {
                continue;
            }
            $item = json_decode($res, true);
            if ( ! $item || ! \is_array($item)) {
                continue;
            }
            $items = $item;
            break;
        }
        return $items;
    }
    private function getCpuTemp()
    {
        try {
            $path = '/sys/class/thermal/thermal_zone0/temp';
            return file_exists($path) ? (int) file_get_contents($path) : 0;
        } catch (Exception $e) {
            return 0;
        }
    }
}
namespace InnStudio\Prober\Components\ServerStatus;
final class ServerStatus
{
    public function __construct()
    {
        new Conf();
        new Fetch();
    }
}
namespace InnStudio\Prober\Components\ServerStatus;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Utils\UtilsCpu;
use InnStudio\Prober\Components\Utils\UtilsMemory;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class Conf extends ServerStatusConstants
{
    public function __construct()
    {
        EventsApi::on('conf', function (array $conf) {
            if (XconfigApi::isDisabled($this->ID)) {
                return $conf;
            }
            $conf[$this->ID] = array(
                'sysLoad' => UtilsCpu::getLoadAvg(),
                'memRealUsage' => array(
                    'value' => UtilsMemory::getMemoryUsage('MemRealUsage'),
                    'max' => UtilsMemory::getMemoryUsage('MemTotal'),
                ),
                'memBuffers' => array(
                    'value' => UtilsMemory::getMemoryUsage('Buffers'),
                    'max' => UtilsMemory::getMemoryUsage('MemUsage'),
                ),
                'memCached' => array(
                    'value' => UtilsMemory::getMemoryUsage('Cached'),
                    'max' => UtilsMemory::getMemoryUsage('MemUsage'),
                ),
                'swapUsage' => array(
                    'value' => UtilsMemory::getMemoryUsage('SwapUsage'),
                    'max' => UtilsMemory::getMemoryUsage('SwapTotal'),
                ),
                'swapCached' => array(
                    'value' => UtilsMemory::getMemoryUsage('SwapCached'),
                    'max' => UtilsMemory::getMemoryUsage('SwapUsage'),
                ),
            );
            return $conf;
        });
    }
}
namespace InnStudio\Prober\Components\ServerStatus;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Utils\UtilsCpu;
use InnStudio\Prober\Components\Utils\UtilsMemory;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class Fetch extends ServerStatusConstants
{
    public function __construct()
    {
        EventsApi::on('fetch', array($this, 'filter'));
        EventsApi::on('nodes', array($this, 'filter'));
    }
    public function filter(array $items)
    {
        if (XconfigApi::isDisabled($this->ID)) {
            return $items;
        }
        $items[$this->ID] = array(
            'sysLoad' => UtilsCpu::getLoadAvg(),
            'cpuUsage' => UtilsCpu::getUsage(),
            'memRealUsage' => array(
                'value' => UtilsMemory::getMemoryUsage('MemRealUsage'),
                'max' => UtilsMemory::getMemoryUsage('MemTotal'),
            ),
            'memBuffers' => array(
                'value' => UtilsMemory::getMemoryUsage('Buffers'),
                'max' => UtilsMemory::getMemoryUsage('MemUsage'),
            ),
            'memCached' => array(
                'value' => UtilsMemory::getMemoryUsage('Cached'),
                'max' => UtilsMemory::getMemoryUsage('MemUsage'),
            ),
            'swapUsage' => array(
                'value' => UtilsMemory::getMemoryUsage('SwapUsage'),
                'max' => UtilsMemory::getMemoryUsage('SwapTotal'),
            ),
            'swapCached' => array(
                'value' => UtilsMemory::getMemoryUsage('SwapCached'),
                'max' => UtilsMemory::getMemoryUsage('SwapUsage'),
            ),
        );
        return $items;
    }
}
namespace InnStudio\Prober\Components\ServerStatus;
class ServerStatusConstants
{
    protected $ID = 'serverStatus';
}
namespace InnStudio\Prober\Components\Style;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Utils\UtilsApi;
final class Style
{
    public function __construct()
    {
        EventsApi::on('init', function ($action) {
            if ('style' !== $action) {
                return $action;
            }
            $this->output();
        });
    }
    private function output()
    {
        UtilsApi::setFileCacheHeader();
        header('Content-type: text/css');
        echo <<<'HTML'
.src-Components-Card-components-styles-module__des--EgOss{margin-bottom:var(--x-gutter);border-radius:var(--x-radius);background-color:var(--x-card-des-bg);padding:calc(var(--x-gutter)/2) var(--x-gutter);color:var(--x-card-des-fg)}.src-Components-Card-components-styles-module__link--QMvaX::before{content:"👆 "}.src-Components-Card-components-styles-module__ruby--lRf3T{cursor:pointer;background:var(--x-benchmark-ruby-bg)}.src-Components-Card-components-styles-module__ruby--lRf3T:hover{text-decoration:underline}.src-Components-Card-components-styles-module__ruby--lRf3T rt{opacity:.5;font-size:.75rem}.src-Components-Card-components-styles-module__ruby--lRf3T[data-is-result]{font-weight:bold}.src-Components-Card-components-styles-module__error--RxEjQ{padding:var(--x-gutter)}.src-Components-Card-components-styles-module__title--sQBIC{flex:0 0 8rem;padding:calc(var(--x-gutter)/2) 0;color:var(--x-card-title-fg);word-break:normal}@media(min-width: 375px){.src-Components-Card-components-styles-module__title--sQBIC{flex:0 0 9rem}}@media(min-width: 425px){.src-Components-Card-components-styles-module__title--sQBIC{flex:0 0 10rem}}@media(min-width: 768px){.src-Components-Card-components-styles-module__title--sQBIC{flex:0 0 11rem}}.src-Components-Card-components-styles-module__group--onjSH{display:flex;align-items:center;border-bottom:1px dashed var(--x-card-split-color);width:100%}.src-Components-Card-components-styles-module__group--onjSH:hover{background:var(--x-card-bg-hover)}.src-Components-Card-components-styles-module__content--Ibvay{flex-grow:1;padding:calc(var(--x-gutter)/2) 0}.src-Components-Card-components-styles-module__fieldset--GoXuV{position:relative;margin-bottom:calc(var(--x-gutter)*1.5);box-shadow:var(--x-card-box-shadow);border:5px solid var(--x-card-border-color);border-radius:var(--x-radius);background:var(--x-card-bg);padding:calc(var(--x-gutter)*1.5) 0 0;scroll-margin-top:50px}.src-Components-Card-components-styles-module__body--aNmjc{padding:0 calc(var(--x-gutter)/2)}@media(min-width: 425px){.src-Components-Card-components-styles-module__body--aNmjc{padding:0 var(--x-gutter)}}.src-Components-Card-components-styles-module__arrow--YXo0g{opacity:.5;cursor:pointer;padding:0 .5rem;color:var(--x-card-legend-arrow-fg)}.src-Components-Card-components-styles-module__arrow--YXo0g:active,.src-Components-Card-components-styles-module__arrow--YXo0g:hover{opacity:1;color:var(--x-card-legend-arrow-fg);text-decoration:none}.src-Components-Card-components-styles-module__arrow--YXo0g[data-disabled],.src-Components-Card-components-styles-module__arrow--YXo0g[data-disabled]:hover{opacity:.1;cursor:not-allowed}.src-Components-Card-components-styles-module__legend--fgO2f{display:flex;position:absolute;top:0;left:50%;justify-content:center;align-items:center;transform:translate(-50%, -50%);margin:0 auto;border-radius:5rem;background:var(--x-card-legend-bg);padding:.5rem 1rem;color:var(--x-card-legend-fg);white-space:nowrap}.src-Components-Card-components-styles-module__legendText--q65Xw{padding:0 .5rem}.src-Components-Card-components-styles-module__multiItemContainer--CAVDM{display:flex;flex-wrap:wrap;margin-bottom:-0.2rem}
:root{--x-max-width: 1680px;--x-radius: 1rem;--x-fg: hsl(0, 0%, 20%);--x-bg: hsl(0, 0%, 97%);--x-html-bg: var(--x-fg);--x-body-fg: var(--x-fg);--x-body-bg: var(--x-bg);--x-gutter: 1rem;--x-app-border-color: var(--x-fg);--x-app-bg: var(--x-bg);--x-footer-fg: var(--x-bg);--x-footer-bg: var(--x-fg);--x-benchmark-ruby-bg: hsla(0, 0%, 0%, 0.05);--x-card-bg: hsla(0, 0%, 20%, 0.03);--x-card-bg-hover: linear-gradient(to right, transparent, hsla(0, 0%, 0%, 0.102), transparent);--x-card-legend-fg: var(--x-bg);--x-card-legend-bg: linear-gradient(hsl(0, 0%, 15%), var(--x-fg));--x-card-legend-arrow-fg: var(--x-card-legend-fg);--x-card-title-fg: var(--x-fg);--x-card-title-bg: var(--x-bg);--x-card-des-fg: var(--x-bg);--x-card-des-bg: var(--x-fg);--x-card-border-color: hsla(0, 0%, 0%, 0.1);--x-card-split-color: hsla(0, 0%, 0%, 0.3);--x-card-box-shadow: hsla(0, 0%, 20%, 0.3) 0px -1px 0px, hsl(0, 0%, 100%) 0px 1px 0px inset, hsla(0, 0%, 20%, 0.3) 0px -1px 0px inset, hsl(0, 0%, 100%) 0px 1px 0px;--x-title-fg: var(--x-bg);--x-title-bg: var(--x-fg);--x-title-box-shadow: 0 1px 0 hsl(0, 0%, 0%);--x-star-me-fg: var(--x-bg);--x-star-me-bg: var(--x-fg);--x-star-me-hover-fg: hsl(0, 0%, 100%);--x-star-me-hover-bg: var(--x-fg);--x-star-me-border-color: linear-gradient(90deg, transparent, hsl(0, 0%, 100%), transparent);--x-nav-fg: var(--x-bg);--x-nav-fg-hover: var(--x-fg);--x-nav-fg-active: var(--x-fg);--x-nav-bg: var(--x-fg);--x-nav-bg-hover: linear-gradient(hsla(0, 0%, 100%, 0.85), hsla(0, 0%, 100%, 0.65));--x-nav-bg-active: linear-gradient(hsla(0, 0%, 100%, 0.95), hsla(0, 0%, 100%, 0.75));--x-nav-border-color: hsla(0, 0%, 100%, 0.1);--x-status-ok-fg: hsl(0, 0%, 100%);--x-status-ok-bg: linear-gradient(hsl(120, 100%, 30%), hsl(120, 100%, 45%));--x-status-error-fg: hsl(0, 0%, 100%);--x-status-error-bg: linear-gradient(hsl(0, 0%, 50%), hsl(0, 0%, 73%));--x-search-fg: var(--x-fg);--x-search-bg: hsla(0, 0%, 0%, 0.05);--x-search-bg-hover: hsla(0, 0%, 0%, 0.15);--x-progress-fg: var(--x-bg);--x-progress-bg: linear-gradient(hsl(0, 0%, 0%), hsl(0, 0%, 20%));--x-progress-value-bg: hsl(120, 100%, 40%);--x-progress-value-highlight-bg: linear-gradient(hsla(0, 0%, 100%, 0.45), transparent);--x-progress-value-highlight-border-color: linear-gradient( to right, hsla(0, 0%, 100%, 0.1), hsla(0, 0%, 100%, 0.45), hsla(0, 0%, 100%, 0.1) );--x-progress-value-box-shadow: 0px 0px 1px 1px hsl(0, 0%, 0%);--x-network-stats-tx-fg: hsl(23, 100%, 38%);--x-network-stats-rx-fg: hsl(120, 100%, 23%);--x-network-node-fg: var(--x-fg);--x-network-node-bg: hsla(132, 4%, 23%, 0.1);--x-network-node-border-color: var(--x-card-split-color);--x-network-node-row-bg: linear-gradient(to right, transparent, hsla(0, 0%, 100%, 0.5), transparent);--x-ping-btn-fg: var(--x-bg);--x-ping-btn-bg: var(--x-fg);--x-ping-result-fg: var(--x-bg);--x-ping-result-bg: var(--x-fg);--x-ping-result-scrollbar-bg: var(--x-fg);--x-sys-load-fg: var(--x-bg);--x-sys-load-bg: var(--x-fg);--x-toast-fg: var(--x-bg);--x-toast-bg: var(--x-fg)}@media(prefers-color-scheme: dark){:root{--x-fg: hsl(0, 0%, 80%);--x-bg: hsl(0, 0%, 0%);--x-html-bg: hsl(0, 0%, 0%);--x-body-fg: var(--x-fg);--x-body-bg: hsl(0, 0%, 0%);--x-gutter: 1rem;--x-app-border-color: var(--x-bg);--x-app-bg: hsl(0, 0%, 13%);--x-footer-fg: var(--x-fg);--x-footer-bg: var(--x-bg);--x-benchmark-ruby-bg: hsl(0, 0%, 0%, 0.5);--x-card-bg: hsla(0, 0%, 100%, 0.05);--x-card-bg-hover: linear-gradient(to right, transparent, hsla(0, 0%, 0%, 0.5), transparent);--x-card-legend-fg: var(--x-fg);--x-card-legend-bg: linear-gradient(hsl(0, 0%, 10%), var(--x-bg));--x-card-legend-arrow-fg: var(--x-card-legend-fg);--x-card-title-fg: var(--x-fg);--x-card-title-bg: var(--x-bg);--x-card-des-fg: var(--x-fg);--x-card-des-bg: hsla(0, 0%, 0%, 0.5);--x-card-border-color: hsla(0, 0%, 0%, 0.5);--x-card-split-color: hsla(0, 0%, 100%, 0.1);--x-card-box-shadow: 0px 0px 0px 1px hsl(0, 0%, 0%) inset;--x-title-fg: var(--x-fg);--x-title-bg: var(--x-bg);--x-title-box-shadow: 0 1px 0 hsl(0, 0%, 0%);--x-star-me-fg: var(--x-fg);--x-star-me-bg: var(--x-bg);--x-star-me-hover-fg: hsl(0, 0%, 100%);--x-star-me-hover-bg: var(--x-bg);--x-star-me-border-color: linear-gradient(90deg, transparent, hsl(0, 0%, 100%), transparent);--x-nav-fg: var(--x-fg);--x-nav-fg-hover: var(--x-fg);--x-nav-fg-active: var(--x-fg);--x-nav-bg: var(--x-bg);--x-nav-bg-hover: linear-gradient(hsla(0, 0%, 100%, 0.15), hsla(0, 0%, 100%, 0.05));--x-nav-bg-active: linear-gradient(hsla(0, 0%, 100%, 0.25), hsla(0, 0%, 100%, 0.15));--x-nav-border-color: hsla(0, 0%, 100%, 0.1);--x-status-ok-fg: hsl(0, 0%, 100%);--x-status-ok-bg: linear-gradient(hsl(120, 100%, 20%), hsl(120, 100%, 25%));--x-status-error-fg: hsl(0, 0%, 100%);--x-status-error-bg: linear-gradient(hsl(0, 0%, 27%), hsl(0, 0%, 33%));--x-search-fg: var(--x-fg);--x-search-bg: hsla(0, 0%, 0%, 0.35);--x-search-bg-hover: hsla(0, 0%, 100%, 0.1);--x-progress-fg: var(--x-fg);--x-progress-bg: linear-gradient(hsl(0, 0%, 0%), hsla(0, 0%, 0%, 0.5));--x-progress-value-bg: hsl(120, 100%, 40%);--x-progress-value-highlight-bg: linear-gradient(hsla(0, 0%, 100%, 0.25), transparent);--x-progress-value-highlight-border-color: linear-gradient( to right, hsla(0, 0%, 100%, 0.1), hsla(0, 0%, 100%, 0.25), hsla(0, 0%, 100%, 0.1) );--x-progress-value-box-shadow: 0px 0px 0px 10px inset hsla(0, 0%, 0%, 0.55), 0px 0px 1px 1px hsl(0, 0%, 0%);--x-network-stats-tx-fg: hsl(23, 100%, 58%);--x-network-stats-rx-fg: hsl(120, 100%, 43%);--x-network-node-fg: var(--x-fg);--x-network-node-bg: hsla(0, 0%, 100%, 0.05);--x-network-node-border-color: var(--x-card-split-color);--x-network-node-row-bg: var(--x-card-bg-hover);--x-ping-btn-fg: var(--x-fg);--x-ping-btn-bg: var(--x-bg);--x-ping-result-fg: var(--x-fg);--x-ping-result-bg: var(--x-bg);--x-ping-result-scrollbar-bg: var(--x-bg);--x-sys-load-fg: var(--x-fg);--x-sys-load-bg: var(--x-bg);--x-toast-fg: var(--x-fg);--x-toast-bg: var(--x-bg)}}
.src-Components-Container-components-styles-module__main--rQ91J{margin-left:auto;margin-right:auto;padding-left:calc(var(--x-gutter)/2);padding-right:calc(var(--x-gutter)/2);max-width:var(--x-max-width)}@media(min-width: 768px){.src-Components-Container-components-styles-module__main--rQ91J{padding-left:var(--x-gutter);padding-right:var(--x-gutter)}}
.src-Components-Grid-components-styles-module__container--EXgkw{display:flex;flex-wrap:wrap;margin-left:calc(var(--x-gutter)*-0.5);margin-right:calc(var(--x-gutter)*-0.5)}.src-Components-Grid-components-styles-module__grid--qbVV1{padding-left:calc(var(--x-gutter)*.5);padding-right:calc(var(--x-gutter)*.5);flex:1 0 100%;width:100%}@media(min-width: 320px){.src-Components-Grid-components-styles-module__grid--qbVV1[data-xs="1"]{flex:0 0 100%;width:100%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xs="2"]{flex:0 0 50%;width:50%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xs="3"]{flex:0 0 33.3333333333%;width:33.3333333333%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xs="4"]{flex:0 0 25%;width:25%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xs="5"]{flex:0 0 20%;width:20%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xs="6"]{flex:0 0 16.6666666667%;width:16.6666666667%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xs="7"]{flex:0 0 14.2857142857%;width:14.2857142857%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xs="8"]{flex:0 0 12.5%;width:12.5%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xs="9"]{flex:0 0 11.1111111111%;width:11.1111111111%}}@media(min-width: 375px){.src-Components-Grid-components-styles-module__grid--qbVV1[data-sm="1"]{flex:0 0 100%;width:100%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-sm="2"]{flex:0 0 50%;width:50%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-sm="3"]{flex:0 0 33.3333333333%;width:33.3333333333%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-sm="4"]{flex:0 0 25%;width:25%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-sm="5"]{flex:0 0 20%;width:20%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-sm="6"]{flex:0 0 16.6666666667%;width:16.6666666667%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-sm="7"]{flex:0 0 14.2857142857%;width:14.2857142857%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-sm="8"]{flex:0 0 12.5%;width:12.5%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-sm="9"]{flex:0 0 11.1111111111%;width:11.1111111111%}}@media(min-width: 425px){.src-Components-Grid-components-styles-module__grid--qbVV1[data-md="1"]{flex:0 0 100%;width:100%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-md="2"]{flex:0 0 50%;width:50%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-md="3"]{flex:0 0 33.3333333333%;width:33.3333333333%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-md="4"]{flex:0 0 25%;width:25%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-md="5"]{flex:0 0 20%;width:20%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-md="6"]{flex:0 0 16.6666666667%;width:16.6666666667%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-md="7"]{flex:0 0 14.2857142857%;width:14.2857142857%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-md="8"]{flex:0 0 12.5%;width:12.5%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-md="9"]{flex:0 0 11.1111111111%;width:11.1111111111%}}@media(min-width: 768px){.src-Components-Grid-components-styles-module__grid--qbVV1[data-lg="1"]{flex:0 0 100%;width:100%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-lg="2"]{flex:0 0 50%;width:50%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-lg="3"]{flex:0 0 33.3333333333%;width:33.3333333333%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-lg="4"]{flex:0 0 25%;width:25%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-lg="5"]{flex:0 0 20%;width:20%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-lg="6"]{flex:0 0 16.6666666667%;width:16.6666666667%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-lg="7"]{flex:0 0 14.2857142857%;width:14.2857142857%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-lg="8"]{flex:0 0 12.5%;width:12.5%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-lg="9"]{flex:0 0 11.1111111111%;width:11.1111111111%}}@media(min-width: 1024px){.src-Components-Grid-components-styles-module__grid--qbVV1[data-xl="1"]{flex:0 0 100%;width:100%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xl="2"]{flex:0 0 50%;width:50%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xl="3"]{flex:0 0 33.3333333333%;width:33.3333333333%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xl="4"]{flex:0 0 25%;width:25%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xl="5"]{flex:0 0 20%;width:20%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xl="6"]{flex:0 0 16.6666666667%;width:16.6666666667%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xl="7"]{flex:0 0 14.2857142857%;width:14.2857142857%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xl="8"]{flex:0 0 12.5%;width:12.5%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xl="9"]{flex:0 0 11.1111111111%;width:11.1111111111%}}@media(min-width: 1440px){.src-Components-Grid-components-styles-module__grid--qbVV1[data-xxl="1"]{flex:0 0 100%;width:100%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xxl="2"]{flex:0 0 50%;width:50%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xxl="3"]{flex:0 0 33.3333333333%;width:33.3333333333%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xxl="4"]{flex:0 0 25%;width:25%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xxl="5"]{flex:0 0 20%;width:20%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xxl="6"]{flex:0 0 16.6666666667%;width:16.6666666667%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xxl="7"]{flex:0 0 14.2857142857%;width:14.2857142857%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xxl="8"]{flex:0 0 12.5%;width:12.5%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-xxl="9"]{flex:0 0 11.1111111111%;width:11.1111111111%}}@media(min-width: 2560px){.src-Components-Grid-components-styles-module__grid--qbVV1[data-4k="1"]{flex:0 0 100%;width:100%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-4k="2"]{flex:0 0 50%;width:50%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-4k="3"]{flex:0 0 33.3333333333%;width:33.3333333333%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-4k="4"]{flex:0 0 25%;width:25%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-4k="5"]{flex:0 0 20%;width:20%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-4k="6"]{flex:0 0 16.6666666667%;width:16.6666666667%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-4k="7"]{flex:0 0 14.2857142857%;width:14.2857142857%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-4k="8"]{flex:0 0 12.5%;width:12.5%}.src-Components-Grid-components-styles-module__grid--qbVV1[data-4k="9"]{flex:0 0 11.1111111111%;width:11.1111111111%}}
.src-Components-Utils-components-alert-styles-module__main--fj45p{display:inline-flex;border-radius:var(--x-radius);align-items:center;justify-content:center;font-family:"Arial Black",sans-serif;font-weight:bolder;min-width:2em;padding:0 .5rem;white-space:nowrap;cursor:pointer;text-shadow:0 1px 1px #000}.src-Components-Utils-components-alert-styles-module__main--fj45p:active{transform:scale3d(0.95, 0.95, 1)}.src-Components-Utils-components-alert-styles-module__main--fj45p[data-ok]{background:var(--x-status-ok-bg);color:var(--x-status-ok-fg)}.src-Components-Utils-components-alert-styles-module__main--fj45p[data-error]{background:var(--x-status-error-bg);color:var(--x-status-error-fg)}.src-Components-Utils-components-alert-styles-module__main--fj45p[data-ok][data-icon]::before{content:"✓"}.src-Components-Utils-components-alert-styles-module__main--fj45p[data-error][data-icon]::before{content:"×"}
.src-Components-ProgressBar-components-styles-module__main--vmjyU{position:relative}.src-Components-ProgressBar-components-styles-module__overview--bibEt{position:absolute;right:var(--x-gutter);bottom:0;z-index:1;font-weight:700;color:var(--x-progress-fg);line-height:2rem;font-family:"Arial Black",sans-serif,monospace;text-shadow:0 1px 1px #000}.src-Components-ProgressBar-components-styles-module__precent--wnWh2{left:var(--x-gutter);right:auto}.src-Components-ProgressBar-components-styles-module__shell--gG7gJ{position:relative;display:flex;width:100%;height:2rem;background:var(--x-progress-bg);border-radius:var(--x-radius);padding:.3rem}.src-Components-ProgressBar-components-styles-module__value--itYdo{position:relative;transition:width .5s;border-radius:var(--x-gutter);background-color:var(--x-progress-value-bg);overflow:hidden;box-shadow:var(--x-progress-value-box-shadow)}.src-Components-ProgressBar-components-styles-module__value--itYdo::after,.src-Components-ProgressBar-components-styles-module__value--itYdo::before{position:absolute;content:"";top:0;height:61.8%;width:100%;border-radius:0 0 50% 50%;background:var(--x-progress-value-highlight-bg)}.src-Components-ProgressBar-components-styles-module__value--itYdo::before{background:var(--x-progress-value-highlight-border-color);opacity:1;height:1px;border-radius:0}
.src-Components-Footer-components-styles-module__main--zdKev{background:var(--x-footer-bg);color:var(--x-footer-fg);width:100%;border-radius:10rem;text-align:center;padding:calc(var(--x-gutter)/2) var(--x-gutter);margin:calc(var(--x-gutter)*1.5) auto;word-break:normal}.src-Components-Footer-components-styles-module__main--zdKev a{color:var(--x-footer-fg)}.src-Components-Footer-components-styles-module__main--zdKev a:hover{color:var(--x-footer-fg)}
.src-Components-Forkme-components-styles-module__link--MuvAU{display:flex;justify-content:center;align-items:center;position:fixed;top:0;left:0;background:var(--x-star-me-bg);color:var(--x-star-me-fg);font-family:"Arial Black",sans-serif;padding:calc(var(--x-gutter)/3) calc(var(--x-gutter)*3);box-shadow:0 3px 5px var(--x-star-me-bg);z-index:2;transform:rotate(-45deg) translate3d(-28%, -70%, 0);opacity:.95}@media(min-width: 425px){.src-Components-Forkme-components-styles-module__link--MuvAU{transform:rotate(-45deg) translate3d(-28%, -50%, 0);top:calc(var(--x-gutter)/2);left:calc(var(--x-gutter)/2)}}.src-Components-Forkme-components-styles-module__link--MuvAU:hover{color:var(--x-star-me-hover-fg);background:var(--x-star-me-hover-bg);text-decoration:none;opacity:1}.src-Components-Forkme-components-styles-module__link--MuvAU::after,.src-Components-Forkme-components-styles-module__link--MuvAU::before{position:absolute;left:0;top:1px;height:.5px;width:100%;background:var(--x-star-me-border-color);content:""}.src-Components-Forkme-components-styles-module__link--MuvAU::after{top:auto;bottom:1px}.src-Components-Forkme-components-styles-module__text--Fk_hI{margin-left:.5em;text-transform:uppercase;font-weight:bold}
.src-Components-Nav-components-styles-module__main--gMYNN{position:fixed;bottom:0;background:var(--x-nav-bg);padding:0 var(--x-gutter);left:0;right:0;z-index:10;display:flex;align-items:center;justify-content:flex-start;height:3rem;line-height:3rem;overflow-x:auto}@media(min-width: 375px){.src-Components-Nav-components-styles-module__main--gMYNN{overflow-x:unset;justify-content:center}}.src-Components-Nav-components-styles-module__link--kVaBO{position:relative;white-space:nowrap;color:var(--x-nav-fg);padding:0 .5rem;border-right:1px solid var(--x-nav-border-color)}@media(min-width: 425px){.src-Components-Nav-components-styles-module__link--kVaBO{padding:0 var(--x-gutter)}}.src-Components-Nav-components-styles-module__link--kVaBO:hover{background:var(--x-nav-bg-hover);color:var(--x-nav-fg-hover);text-decoration:none}.src-Components-Nav-components-styles-module__link--kVaBO:active,.src-Components-Nav-components-styles-module__link--kVaBO[data-active]{background:var(--x-nav-bg-active);color:var(--x-nav-fg-active);text-decoration:none}.src-Components-Nav-components-styles-module__link--kVaBO:last-child{border-right:0}.src-Components-Nav-components-styles-module__linkTitle--qqTdU{display:none}@media(min-width: 768px){.src-Components-Nav-components-styles-module__linkTitle--qqTdU{display:block}}.src-Components-Nav-components-styles-module__linkTitleTiny--pkoLr{display:block}@media(min-width: 768px){.src-Components-Nav-components-styles-module__linkTitleTiny--pkoLr{display:none}}
.src-Components-NetworkStats-components-styles-module__id--eJf_G{text-decoration:underline;text-align:center}.src-Components-NetworkStats-components-styles-module__idRow--ACOSC{align-items:center}.src-Components-NetworkStats-components-styles-module__dataContainer--bPvUe{display:flex;align-items:center;justify-content:center;text-align:center}.src-Components-NetworkStats-components-styles-module__data--Fo38e{flex:0 0 50%}.src-Components-NetworkStats-components-styles-module__data--Fo38e[data-rx]{color:var(--x-network-stats-rx-fg)}.src-Components-NetworkStats-components-styles-module__data--Fo38e[data-tx]{color:var(--x-network-stats-tx-fg)}.src-Components-NetworkStats-components-styles-module__rate--eoXaN{font-family:"Arial Black",sans-serif}.src-Components-NetworkStats-components-styles-module__rate--eoXaN::before{margin-right:.5rem}.src-Components-NetworkStats-components-styles-module__rateRx--IuEZe::before{content:"▼"}.src-Components-NetworkStats-components-styles-module__rateTx--gWEgj::before{content:"▲"}
.src-Components-ServerStatus-components-styles-module__loadGroup--WzXPX{display:flex;align-items:center}.src-Components-ServerStatus-components-styles-module__loadGroup--WzXPX[data-center]{justify-content:center}@media(min-width: 768px){.src-Components-ServerStatus-components-styles-module__loadGroup--WzXPX[data-center]{justify-content:center}}.src-Components-ServerStatus-components-styles-module__loadGroupItem--ZSsqk{background:var(--x-sys-load-bg);color:var(--x-sys-load-fg);padding:calc(var(--x-gutter)*.1) calc(var(--x-gutter)/1.5);border-radius:10rem;font-family:"Arial Black",sans-serif,monospace;font-weight:700}@media(min-width: 768px){.src-Components-ServerStatus-components-styles-module__loadGroupItem--ZSsqk{padding:calc(var(--x-gutter)*.1) var(--x-gutter)}}.src-Components-ServerStatus-components-styles-module__loadGroupItem--ZSsqk+.src-Components-ServerStatus-components-styles-module__loadGroupItem--ZSsqk{margin-left:.5rem}
.src-Components-Utils-components-loading-styles-module__main--jnV53{display:flex;align-items:center;margin-bottom:var(--x-gutter)}.src-Components-Utils-components-loading-styles-module__text--opKiN{margin-left:.5em}
.src-Components-Nodes-components-styles-module__groupId--PmHBP{display:block;text-decoration:underline;text-align:center;margin-bottom:calc(var(--x-gutter)*.5)}.src-Components-Nodes-components-styles-module__groupId--PmHBP:hover{text-decoration:none}.src-Components-Nodes-components-styles-module__group--cvxdK{margin-bottom:calc(var(--x-gutter)*.5)}.src-Components-Nodes-components-styles-module__groupMsg--wNqQl{display:flex;justify-content:center}.src-Components-Nodes-components-styles-module__groupNetworks--h1HMf{border-radius:var(--x-radius);background:var(--x-network-node-bg);color:var(--x-network-node-fg);padding:var(--x-gutter);margin-bottom:var(--x-gutter)}.src-Components-Nodes-components-styles-module__groupNetwork--rvydY{border-bottom:1px dashed var(--x-network-node-border-color);margin-bottom:calc(var(--x-gutter)*.5);padding-bottom:calc(var(--x-gutter)*.5)}.src-Components-Nodes-components-styles-module__groupNetwork--rvydY:last-child{margin-bottom:0;border-bottom:0;padding-bottom:0}.src-Components-Nodes-components-styles-module__groupNetwork--rvydY:hover{background:var(--x-network-node-row-bg)}
.src-Components-Utils-components-search-link-styles-module__main--kwUcX{margin:0 calc(var(--x-gutter)*.2) calc(var(--x-gutter)*.2) 0;background:var(--x-search-bg);color:var(--x-search-fg);padding:0 calc(var(--x-gutter)*.5);border-radius:var(--x-radius);font-family:consolas,monospace}.src-Components-Utils-components-search-link-styles-module__main--kwUcX:hover{text-decoration:underline;background:var(--x-search-bg-hover)}
.src-Components-Ping-components-style-module__btn--o_4YN{display:block;text-align:center;color:var(--x-ping-btn-fg);background:var(--x-ping-btn-bg);border-radius:var(--x-radius);padding:calc(var(--x-gutter)*.5) var(--x-gutter);margin-right:var(--x-gutter)}.src-Components-Ping-components-style-module__btn--o_4YN:hover,.src-Components-Ping-components-style-module__btn--o_4YN:active{text-decoration:none;color:var(--x-ping-btn-fg);opacity:.9}.src-Components-Ping-components-style-module__btn--o_4YN:active{opacity:1;transform:scale3d(0.95, 0.95, 1)}.src-Components-Ping-components-style-module__itemContainer--GLMRY{display:flex;flex-wrap:wrap;margin:0 0 calc(var(--x-gutter)*.5);padding:0;height:8rem;overflow-y:auto;list-style-type:none}.src-Components-Ping-components-style-module__item--kR0WD{flex:0 0 50%}@media(min-width: 768px){.src-Components-Ping-components-style-module__item--kR0WD{flex:0 0 33.333%}}@media(min-width: 1024px){.src-Components-Ping-components-style-module__item--kR0WD{flex:0 0 25%}}@media(min-width: 1440px){.src-Components-Ping-components-style-module__item--kR0WD{flex:0 0 20%}}.src-Components-Ping-components-style-module__itemNumber--KiUxL{opacity:.5;display:none}@media(min-width: 768px){.src-Components-Ping-components-style-module__itemNumber--KiUxL{display:inline}}.src-Components-Ping-components-style-module__itemLine--OVM7p{opacity:.3;display:none}@media(min-width: 768px){.src-Components-Ping-components-style-module__itemLine--OVM7p{display:inline}}.src-Components-Ping-components-style-module__itemTime--WiXML{font-weight:bold}.src-Components-Ping-components-style-module__resultContainer--xJz3t{background:var(--x-ping-result-bg);color:var(--x-ping-result-fg);border-radius:calc(var(--x-radius)*.5);padding:calc(var(--x-gutter)*.5) var(--x-gutter)}.src-Components-Ping-components-style-module__result--qEqSo{display:flex;align-items:center;flex-wrap:wrap;border-top:1px dashed var(--x-ping-result-fg);padding-top:calc(var(--x-gutter)*.5);justify-content:space-between}
.src-Components-ServerBenchmark-components-styles-module__btn--DR6pA{display:block}.src-Components-ServerBenchmark-components-styles-module__aff--U6apK{word-break:normal}
.src-Components-Title-components-styles-module__h1--z5lLy{background:var(--x-title-bg);position:fixed;top:0;left:50%;justify-content:center;text-align:center;margin:0;min-width:60vw;width:50vw;font-size:var(--x-gutter);line-height:1;border-radius:0 0 var(--x-radius) var(--x-radius);z-index:10;box-shadow:var(--x-title-box-shadow);transform:translateX(-50%)}.src-Components-Title-components-styles-module__link--_O32A{display:block;padding:var(--x-gutter);color:var(--x-title-fg)}.src-Components-Title-components-styles-module__link--_O32A:hover{color:var(--x-title-fg)}
.src-Components-Toast-components-styles-module__main--yKV4Y{position:fixed;bottom:4rem;width:20rem;max-width:80vw;left:50%;transform:translateX(-50%);background:var(--x-toast-bg);color:var(--x-toast-fg);border-radius:var(--x-gutter);padding:calc(var(--x-gutter)*.5) var(--x-gutter);cursor:pointer;word-break:normal;text-align:center;backdrop-filter:blur(5px)}
@media(min-width: 1024px){::-webkit-scrollbar-track{background-color:rgba(0,0,0,0)}::-webkit-scrollbar{width:var(--x-gutter);background-color:rgba(0,0,0,0)}::-webkit-scrollbar-thumb{border-radius:var(--x-gutter) 0 0 var(--x-gutter);background-color:#ccc}::-webkit-scrollbar-thumb:hover{background-color:#fff}}*{box-sizing:border-box;word-break:break-all;padding:0;margin:0}html{font-size:75%;background:var(--x-html-bg);scroll-behavior:smooth}body{background:var(--x-body-bg);color:var(--x-body-fg);font-family:"Noto Sans CJK SC","Helvetica Neue",Helvetica,Arial,Verdana,Geneva,sans-serif;padding:var(--x-gutter);margin:0;line-height:1.5}a{cursor:pointer;color:var(--x-fg);text-decoration:none}a:hover,a:active{color:var(--x-fg);text-decoration:underline}
.src-Components-Bootstrap-components-styles-module__app--llWF8{padding:calc(var(--x-gutter)*3.5) 0 calc(var(--x-gutter)*2);background:var(--x-app-bg)}.src-Components-Bootstrap-components-styles-module__app--llWF8::before,.src-Components-Bootstrap-components-styles-module__app--llWF8::after{position:fixed;left:0;top:0;right:0;bottom:calc(var(--x-gutter)*2);border:var(--x-gutter) solid var(--x-app-border-color);pointer-events:none;z-index:1;content:""}.src-Components-Bootstrap-components-styles-module__app--llWF8::after{border-radius:calc(var(--x-gutter)*3)}

HTML;
        exit;
    }
}
namespace InnStudio\Prober\Components\Timezone;
use InnStudio\Prober\Components\Events\EventsApi;
final class Timezone
{
    public function __construct()
    {
        EventsApi::on('init', function ($action) {
            if ( ! \ini_get('date.timezone')) {
                date_default_timezone_set('GMT');
            }
            return $action;
        }, 1);
    }
}
namespace InnStudio\Prober\Components\Script;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Utils\UtilsApi;
final class Script
{
    public function __construct()
    {
        EventsApi::on('init', function ($action) {
            if ('script' !== $action) {
                return $action;
            }
            $this->output();
        });
    }
    private function output()
    {
        UtilsApi::setFileCacheHeader();
        header('Content-type: application/javascript');
        echo <<<'HTML'
!function(){var e={874:function(e,t,n){"use strict";var r=n(935),i={"text/plain":"Text","text/html":"Url",default:"Text"};e.exports=function(e,t){var n,a,o,l,s,u,c=!1;t||(t={}),n=t.debug||!1;try{if(o=r(),l=document.createRange(),s=document.getSelection(),(u=document.createElement("span")).textContent=e,u.ariaHidden="true",u.style.all="unset",u.style.position="fixed",u.style.top=0,u.style.clip="rect(0, 0, 0, 0)",u.style.whiteSpace="pre",u.style.webkitUserSelect="text",u.style.MozUserSelect="text",u.style.msUserSelect="text",u.style.userSelect="text",u.addEventListener("copy",(function(r){if(r.stopPropagation(),t.format)if(r.preventDefault(),void 0===r.clipboardData){n&&console.warn("unable to use e.clipboardData"),n&&console.warn("trying IE specific stuff"),window.clipboardData.clearData();var a=i[t.format]||i.default;window.clipboardData.setData(a,e)}else r.clipboardData.clearData(),r.clipboardData.setData(t.format,e);t.onCopy&&(r.preventDefault(),t.onCopy(r.clipboardData))})),document.body.appendChild(u),l.selectNodeContents(u),s.addRange(l),!document.execCommand("copy"))throw new Error("copy command was unsuccessful");c=!0}catch(r){n&&console.error("unable to copy using execCommand: ",r),n&&console.warn("trying IE specific stuff");try{window.clipboardData.setData(t.format||"text",e),t.onCopy&&t.onCopy(window.clipboardData),c=!0}catch(r){n&&console.error("unable to copy using clipboardData: ",r),n&&console.error("falling back to prompt"),a=function(e){var t=(/mac os x/i.test(navigator.userAgent)?"\u2318":"Ctrl")+"+C";return e.replace(/#{\s*key\s*}/g,t)}("message"in t?t.message:"Copy to clipboard: #{key}, Enter"),window.prompt(a,e)}}finally{s&&("function"==typeof s.removeRange?s.removeRange(l):s.removeAllRanges()),u&&document.body.removeChild(u),o()}return c}},746:function(e,t,n){"use strict";var r=n(959),i=n(962);function a(e){for(var t="https://reactjs.org/docs/error-decoder.html?invariant="+e,n=1;n<arguments.length;n++)t+="&args[]="+encodeURIComponent(arguments[n]);return"Minified React error #"+e+"; visit "+t+" for the full message or use the non-minified dev environment for full errors and additional helpful warnings."}var o=new Set,l={};function s(e,t){u(e,t),u(e+"Capture",t)}function u(e,t){for(l[e]=t,e=0;e<t.length;e++)o.add(t[e])}var c=!("undefined"==typeof window||void 0===window.document||void 0===window.document.createElement),d=Object.prototype.hasOwnProperty,f=/^[:A-Z_a-z\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u02FF\u0370-\u037D\u037F-\u1FFF\u200C-\u200D\u2070-\u218F\u2C00-\u2FEF\u3001-\uD7FF\uF900-\uFDCF\uFDF0-\uFFFD][:A-Z_a-z\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u02FF\u0370-\u037D\u037F-\u1FFF\u200C-\u200D\u2070-\u218F\u2C00-\u2FEF\u3001-\uD7FF\uF900-\uFDCF\uFDF0-\uFFFD\-.0-9\u00B7\u0300-\u036F\u203F-\u2040]*$/,h={},p={};function v(e,t,n,r,i,a,o){this.acceptsBooleans=2===t||3===t||4===t,this.attributeName=r,this.attributeNamespace=i,this.mustUseProperty=n,this.propertyName=e,this.type=t,this.sanitizeURL=a,this.removeEmptyString=o}var m={};"children dangerouslySetInnerHTML defaultValue defaultChecked innerHTML suppressContentEditableWarning suppressHydrationWarning style".split(" ").forEach((function(e){m[e]=new v(e,0,!1,e,null,!1,!1)})),[["acceptCharset","accept-charset"],["className","class"],["htmlFor","for"],["httpEquiv","http-equiv"]].forEach((function(e){var t=e[0];m[t]=new v(t,1,!1,e[1],null,!1,!1)})),["contentEditable","draggable","spellCheck","value"].forEach((function(e){m[e]=new v(e,2,!1,e.toLowerCase(),null,!1,!1)})),["autoReverse","externalResourcesRequired","focusable","preserveAlpha"].forEach((function(e){m[e]=new v(e,2,!1,e,null,!1,!1)})),"allowFullScreen async autoFocus autoPlay controls default defer disabled disablePictureInPicture disableRemotePlayback formNoValidate hidden loop noModule noValidate open playsInline readOnly required reversed scoped seamless itemScope".split(" ").forEach((function(e){m[e]=new v(e,3,!1,e.toLowerCase(),null,!1,!1)})),["checked","multiple","muted","selected"].forEach((function(e){m[e]=new v(e,3,!0,e,null,!1,!1)})),["capture","download"].forEach((function(e){m[e]=new v(e,4,!1,e,null,!1,!1)})),["cols","rows","size","span"].forEach((function(e){m[e]=new v(e,6,!1,e,null,!1,!1)})),["rowSpan","start"].forEach((function(e){m[e]=new v(e,5,!1,e.toLowerCase(),null,!1,!1)}));var g=/[\-:]([a-z])/g;function y(e){return e[1].toUpperCase()}function b(e,t,n,r){var i=m.hasOwnProperty(t)?m[t]:null;(null!==i?0!==i.type:r||!(2<t.length)||"o"!==t[0]&&"O"!==t[0]||"n"!==t[1]&&"N"!==t[1])&&(function(e,t,n,r){if(null==t||function(e,t,n,r){if(null!==n&&0===n.type)return!1;switch(typeof t){case"function":case"symbol":return!0;case"boolean":return!r&&(null!==n?!n.acceptsBooleans:"data-"!==(e=e.toLowerCase().slice(0,5))&&"aria-"!==e);default:return!1}}(e,t,n,r))return!0;if(r)return!1;if(null!==n)switch(n.type){case 3:return!t;case 4:return!1===t;case 5:return isNaN(t);case 6:return isNaN(t)||1>t}return!1}(t,n,i,r)&&(n=null),r||null===i?function(e){return!!d.call(p,e)||!d.call(h,e)&&(f.test(e)?p[e]=!0:(h[e]=!0,!1))}(t)&&(null===n?e.removeAttribute(t):e.setAttribute(t,""+n)):i.mustUseProperty?e[i.propertyName]=null===n?3!==i.type&&"":n:(t=i.attributeName,r=i.attributeNamespace,null===n?e.removeAttribute(t):(n=3===(i=i.type)||4===i&&!0===n?"":""+n,r?e.setAttributeNS(r,t,n):e.setAttribute(t,n))))}"accent-height alignment-baseline arabic-form baseline-shift cap-height clip-path clip-rule color-interpolation color-interpolation-filters color-profile color-rendering dominant-baseline enable-background fill-opacity fill-rule flood-color flood-opacity font-family font-size font-size-adjust font-stretch font-style font-variant font-weight glyph-name glyph-orientation-horizontal glyph-orientation-vertical horiz-adv-x horiz-origin-x image-rendering letter-spacing lighting-color marker-end marker-mid marker-start overline-position overline-thickness paint-order panose-1 pointer-events rendering-intent shape-rendering stop-color stop-opacity strikethrough-position strikethrough-thickness stroke-dasharray stroke-dashoffset stroke-linecap stroke-linejoin stroke-miterlimit stroke-opacity stroke-width text-anchor text-decoration text-rendering underline-position underline-thickness unicode-bidi unicode-range units-per-em v-alphabetic v-hanging v-ideographic v-mathematical vector-effect vert-adv-y vert-origin-x vert-origin-y word-spacing writing-mode xmlns:xlink x-height".split(" ").forEach((function(e){var t=e.replace(g,y);m[t]=new v(t,1,!1,e,null,!1,!1)})),"xlink:actuate xlink:arcrole xlink:role xlink:show xlink:title xlink:type".split(" ").forEach((function(e){var t=e.replace(g,y);m[t]=new v(t,1,!1,e,"http://www.w3.org/1999/xlink",!1,!1)})),["xml:base","xml:lang","xml:space"].forEach((function(e){var t=e.replace(g,y);m[t]=new v(t,1,!1,e,"http://www.w3.org/XML/1998/namespace",!1,!1)})),["tabIndex","crossOrigin"].forEach((function(e){m[e]=new v(e,1,!1,e.toLowerCase(),null,!1,!1)})),m.xlinkHref=new v("xlinkHref",1,!1,"xlink:href","http://www.w3.org/1999/xlink",!0,!1),["src","href","action","formAction"].forEach((function(e){m[e]=new v(e,1,!1,e.toLowerCase(),null,!0,!0)}));var _=r.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED,w=Symbol.for("react.element"),k=Symbol.for("react.portal"),x=Symbol.for("react.fragment"),S=Symbol.for("react.strict_mode"),z=Symbol.for("react.profiler"),P=Symbol.for("react.provider"),C=Symbol.for("react.context"),j=Symbol.for("react.forward_ref"),O=Symbol.for("react.suspense"),E=Symbol.for("react.suspense_list"),N=Symbol.for("react.memo"),T=Symbol.for("react.lazy");Symbol.for("react.scope"),Symbol.for("react.debug_trace_mode");var L=Symbol.for("react.offscreen");Symbol.for("react.legacy_hidden"),Symbol.for("react.cache"),Symbol.for("react.tracing_marker");var I=Symbol.iterator;function A(e){return null===e||"object"!=typeof e?null:"function"==typeof(e=I&&e[I]||e["@@iterator"])?e:null}var M,R=Object.assign;function D(e){if(void 0===M)try{throw Error()}catch(e){var t=e.stack.trim().match(/\n( *(at )?)/);M=t&&t[1]||""}return"\n"+M+e}var U=!1;function V(e,t){if(!e||U)return"";U=!0;var n=Error.prepareStackTrace;Error.prepareStackTrace=void 0;try{if(t)if(t=function(){throw Error()},Object.defineProperty(t.prototype,"props",{set:function(){throw Error()}}),"object"==typeof Reflect&&Reflect.construct){try{Reflect.construct(t,[])}catch(e){var r=e}Reflect.construct(e,[],t)}else{try{t.call()}catch(e){r=e}e.call(t.prototype)}else{try{throw Error()}catch(e){r=e}e()}}catch(t){if(t&&r&&"string"==typeof t.stack){for(var i=t.stack.split("\n"),a=r.stack.split("\n"),o=i.length-1,l=a.length-1;1<=o&&0<=l&&i[o]!==a[l];)l--;for(;1<=o&&0<=l;o--,l--)if(i[o]!==a[l]){if(1!==o||1!==l)do{if(o--,0>--l||i[o]!==a[l]){var s="\n"+i[o].replace(" at new "," at ");return e.displayName&&s.includes("<anonymous>")&&(s=s.replace("<anonymous>",e.displayName)),s}}while(1<=o&&0<=l);break}}}finally{U=!1,Error.prepareStackTrace=n}return(e=e?e.displayName||e.name:"")?D(e):""}function B(e){switch(e.tag){case 5:return D(e.type);case 16:return D("Lazy");case 13:return D("Suspense");case 19:return D("SuspenseList");case 0:case 2:case 15:return e=V(e.type,!1);case 11:return e=V(e.type.render,!1);case 1:return e=V(e.type,!0);default:return""}}function F(e){if(null==e)return null;if("function"==typeof e)return e.displayName||e.name||null;if("string"==typeof e)return e;switch(e){case x:return"Fragment";case k:return"Portal";case z:return"Profiler";case S:return"StrictMode";case O:return"Suspense";case E:return"SuspenseList"}if("object"==typeof e)switch(e.$$typeof){case C:return(e.displayName||"Context")+".Consumer";case P:return(e._context.displayName||"Context")+".Provider";case j:var t=e.render;return(e=e.displayName)||(e=""!==(e=t.displayName||t.name||"")?"ForwardRef("+e+")":"ForwardRef"),e;case N:return null!==(t=e.displayName||null)?t:F(e.type)||"Memo";case T:t=e._payload,e=e._init;try{return F(e(t))}catch(e){}}return null}function H(e){var t=e.type;switch(e.tag){case 24:return"Cache";case 9:return(t.displayName||"Context")+".Consumer";case 10:return(t._context.displayName||"Context")+".Provider";case 18:return"DehydratedFragment";case 11:return e=(e=t.render).displayName||e.name||"",t.displayName||(""!==e?"ForwardRef("+e+")":"ForwardRef");case 7:return"Fragment";case 5:return t;case 4:return"Portal";case 3:return"Root";case 6:return"Text";case 16:return F(t);case 8:return t===S?"StrictMode":"Mode";case 22:return"Offscreen";case 12:return"Profiler";case 21:return"Scope";case 13:return"Suspense";case 19:return"SuspenseList";case 25:return"TracingMarker";case 1:case 0:case 17:case 2:case 14:case 15:if("function"==typeof t)return t.displayName||t.name||null;if("string"==typeof t)return t}return null}function $(e){switch(typeof e){case"boolean":case"number":case"string":case"undefined":case"object":return e;default:return""}}function W(e){var t=e.type;return(e=e.nodeName)&&"input"===e.toLowerCase()&&("checkbox"===t||"radio"===t)}function K(e){e._valueTracker||(e._valueTracker=function(e){var t=W(e)?"checked":"value",n=Object.getOwnPropertyDescriptor(e.constructor.prototype,t),r=""+e[t];if(!e.hasOwnProperty(t)&&void 0!==n&&"function"==typeof n.get&&"function"==typeof n.set){var i=n.get,a=n.set;return Object.defineProperty(e,t,{configurable:!0,get:function(){return i.call(this)},set:function(e){r=""+e,a.call(this,e)}}),Object.defineProperty(e,t,{enumerable:n.enumerable}),{getValue:function(){return r},setValue:function(e){r=""+e},stopTracking:function(){e._valueTracker=null,delete e[t]}}}}(e))}function q(e){if(!e)return!1;var t=e._valueTracker;if(!t)return!0;var n=t.getValue(),r="";return e&&(r=W(e)?e.checked?"true":"false":e.value),(e=r)!==n&&(t.setValue(e),!0)}function Q(e){if(void 0===(e=e||("undefined"!=typeof document?document:void 0)))return null;try{return e.activeElement||e.body}catch(t){return e.body}}function G(e,t){var n=t.checked;return R({},t,{defaultChecked:void 0,defaultValue:void 0,value:void 0,checked:null!=n?n:e._wrapperState.initialChecked})}function X(e,t){var n=null==t.defaultValue?"":t.defaultValue,r=null!=t.checked?t.checked:t.defaultChecked;n=$(null!=t.value?t.value:n),e._wrapperState={initialChecked:r,initialValue:n,controlled:"checkbox"===t.type||"radio"===t.type?null!=t.checked:null!=t.value}}function Y(e,t){null!=(t=t.checked)&&b(e,"checked",t,!1)}function J(e,t){Y(e,t);var n=$(t.value),r=t.type;if(null!=n)"number"===r?(0===n&&""===e.value||e.value!=n)&&(e.value=""+n):e.value!==""+n&&(e.value=""+n);else if("submit"===r||"reset"===r)return void e.removeAttribute("value");t.hasOwnProperty("value")?ee(e,t.type,n):t.hasOwnProperty("defaultValue")&&ee(e,t.type,$(t.defaultValue)),null==t.checked&&null!=t.defaultChecked&&(e.defaultChecked=!!t.defaultChecked)}function Z(e,t,n){if(t.hasOwnProperty("value")||t.hasOwnProperty("defaultValue")){var r=t.type;if(!("submit"!==r&&"reset"!==r||void 0!==t.value&&null!==t.value))return;t=""+e._wrapperState.initialValue,n||t===e.value||(e.value=t),e.defaultValue=t}""!==(n=e.name)&&(e.name=""),e.defaultChecked=!!e._wrapperState.initialChecked,""!==n&&(e.name=n)}function ee(e,t,n){"number"===t&&Q(e.ownerDocument)===e||(null==n?e.defaultValue=""+e._wrapperState.initialValue:e.defaultValue!==""+n&&(e.defaultValue=""+n))}var te=Array.isArray;function ne(e,t,n,r){if(e=e.options,t){t={};for(var i=0;i<n.length;i++)t["$"+n[i]]=!0;for(n=0;n<e.length;n++)i=t.hasOwnProperty("$"+e[n].value),e[n].selected!==i&&(e[n].selected=i),i&&r&&(e[n].defaultSelected=!0)}else{for(n=""+$(n),t=null,i=0;i<e.length;i++){if(e[i].value===n)return e[i].selected=!0,void(r&&(e[i].defaultSelected=!0));null!==t||e[i].disabled||(t=e[i])}null!==t&&(t.selected=!0)}}function re(e,t){if(null!=t.dangerouslySetInnerHTML)throw Error(a(91));return R({},t,{value:void 0,defaultValue:void 0,children:""+e._wrapperState.initialValue})}function ie(e,t){var n=t.value;if(null==n){if(n=t.children,t=t.defaultValue,null!=n){if(null!=t)throw Error(a(92));if(te(n)){if(1<n.length)throw Error(a(93));n=n[0]}t=n}null==t&&(t=""),n=t}e._wrapperState={initialValue:$(n)}}function ae(e,t){var n=$(t.value),r=$(t.defaultValue);null!=n&&((n=""+n)!==e.value&&(e.value=n),null==t.defaultValue&&e.defaultValue!==n&&(e.defaultValue=n)),null!=r&&(e.defaultValue=""+r)}function oe(e){var t=e.textContent;t===e._wrapperState.initialValue&&""!==t&&null!==t&&(e.value=t)}function le(e){switch(e){case"svg":return"http://www.w3.org/2000/svg";case"math":return"http://www.w3.org/1998/Math/MathML";default:return"http://www.w3.org/1999/xhtml"}}function se(e,t){return null==e||"http://www.w3.org/1999/xhtml"===e?le(t):"http://www.w3.org/2000/svg"===e&&"foreignObject"===t?"http://www.w3.org/1999/xhtml":e}var ue,ce,de=(ce=function(e,t){if("http://www.w3.org/2000/svg"!==e.namespaceURI||"innerHTML"in e)e.innerHTML=t;else{for((ue=ue||document.createElement("div")).innerHTML="<svg>"+t.valueOf().toString()+"</svg>",t=ue.firstChild;e.firstChild;)e.removeChild(e.firstChild);for(;t.firstChild;)e.appendChild(t.firstChild)}},"undefined"!=typeof MSApp&&MSApp.execUnsafeLocalFunction?function(e,t,n,r){MSApp.execUnsafeLocalFunction((function(){return ce(e,t)}))}:ce);function fe(e,t){if(t){var n=e.firstChild;if(n&&n===e.lastChild&&3===n.nodeType)return void(n.nodeValue=t)}e.textContent=t}var he={animationIterationCount:!0,aspectRatio:!0,borderImageOutset:!0,borderImageSlice:!0,borderImageWidth:!0,boxFlex:!0,boxFlexGroup:!0,boxOrdinalGroup:!0,columnCount:!0,columns:!0,flex:!0,flexGrow:!0,flexPositive:!0,flexShrink:!0,flexNegative:!0,flexOrder:!0,gridArea:!0,gridRow:!0,gridRowEnd:!0,gridRowSpan:!0,gridRowStart:!0,gridColumn:!0,gridColumnEnd:!0,gridColumnSpan:!0,gridColumnStart:!0,fontWeight:!0,lineClamp:!0,lineHeight:!0,opacity:!0,order:!0,orphans:!0,tabSize:!0,widows:!0,zIndex:!0,zoom:!0,fillOpacity:!0,floodOpacity:!0,stopOpacity:!0,strokeDasharray:!0,strokeDashoffset:!0,strokeMiterlimit:!0,strokeOpacity:!0,strokeWidth:!0},pe=["Webkit","ms","Moz","O"];function ve(e,t,n){return null==t||"boolean"==typeof t||""===t?"":n||"number"!=typeof t||0===t||he.hasOwnProperty(e)&&he[e]?(""+t).trim():t+"px"}function me(e,t){for(var n in e=e.style,t)if(t.hasOwnProperty(n)){var r=0===n.indexOf("--"),i=ve(n,t[n],r);"float"===n&&(n="cssFloat"),r?e.setProperty(n,i):e[n]=i}}Object.keys(he).forEach((function(e){pe.forEach((function(t){t=t+e.charAt(0).toUpperCase()+e.substring(1),he[t]=he[e]}))}));var ge=R({menuitem:!0},{area:!0,base:!0,br:!0,col:!0,embed:!0,hr:!0,img:!0,input:!0,keygen:!0,link:!0,meta:!0,param:!0,source:!0,track:!0,wbr:!0});function ye(e,t){if(t){if(ge[e]&&(null!=t.children||null!=t.dangerouslySetInnerHTML))throw Error(a(137,e));if(null!=t.dangerouslySetInnerHTML){if(null!=t.children)throw Error(a(60));if("object"!=typeof t.dangerouslySetInnerHTML||!("__html"in t.dangerouslySetInnerHTML))throw Error(a(61))}if(null!=t.style&&"object"!=typeof t.style)throw Error(a(62))}}function be(e,t){if(-1===e.indexOf("-"))return"string"==typeof t.is;switch(e){case"annotation-xml":case"color-profile":case"font-face":case"font-face-src":case"font-face-uri":case"font-face-format":case"font-face-name":case"missing-glyph":return!1;default:return!0}}var _e=null;function we(e){return(e=e.target||e.srcElement||window).correspondingUseElement&&(e=e.correspondingUseElement),3===e.nodeType?e.parentNode:e}var ke=null,xe=null,Se=null;function ze(e){if(e=bi(e)){if("function"!=typeof ke)throw Error(a(280));var t=e.stateNode;t&&(t=wi(t),ke(e.stateNode,e.type,t))}}function Pe(e){xe?Se?Se.push(e):Se=[e]:xe=e}function Ce(){if(xe){var e=xe,t=Se;if(Se=xe=null,ze(e),t)for(e=0;e<t.length;e++)ze(t[e])}}function je(e,t){return e(t)}function Oe(){}var Ee=!1;function Ne(e,t,n){if(Ee)return e(t,n);Ee=!0;try{return je(e,t,n)}finally{Ee=!1,(null!==xe||null!==Se)&&(Oe(),Ce())}}function Te(e,t){var n=e.stateNode;if(null===n)return null;var r=wi(n);if(null===r)return null;n=r[t];e:switch(t){case"onClick":case"onClickCapture":case"onDoubleClick":case"onDoubleClickCapture":case"onMouseDown":case"onMouseDownCapture":case"onMouseMove":case"onMouseMoveCapture":case"onMouseUp":case"onMouseUpCapture":case"onMouseEnter":(r=!r.disabled)||(r=!("button"===(e=e.type)||"input"===e||"select"===e||"textarea"===e)),e=!r;break e;default:e=!1}if(e)return null;if(n&&"function"!=typeof n)throw Error(a(231,t,typeof n));return n}var Le=!1;if(c)try{var Ie={};Object.defineProperty(Ie,"passive",{get:function(){Le=!0}}),window.addEventListener("test",Ie,Ie),window.removeEventListener("test",Ie,Ie)}catch(ce){Le=!1}function Ae(e,t,n,r,i,a,o,l,s){var u=Array.prototype.slice.call(arguments,3);try{t.apply(n,u)}catch(e){this.onError(e)}}var Me=!1,Re=null,De=!1,Ue=null,Ve={onError:function(e){Me=!0,Re=e}};function Be(e,t,n,r,i,a,o,l,s){Me=!1,Re=null,Ae.apply(Ve,arguments)}function Fe(e){var t=e,n=e;if(e.alternate)for(;t.return;)t=t.return;else{e=t;do{0!=(4098&(t=e).flags)&&(n=t.return),e=t.return}while(e)}return 3===t.tag?n:null}function He(e){if(13===e.tag){var t=e.memoizedState;if(null===t&&(null!==(e=e.alternate)&&(t=e.memoizedState)),null!==t)return t.dehydrated}return null}function $e(e){if(Fe(e)!==e)throw Error(a(188))}function We(e){return null!==(e=function(e){var t=e.alternate;if(!t){if(null===(t=Fe(e)))throw Error(a(188));return t!==e?null:e}for(var n=e,r=t;;){var i=n.return;if(null===i)break;var o=i.alternate;if(null===o){if(null!==(r=i.return)){n=r;continue}break}if(i.child===o.child){for(o=i.child;o;){if(o===n)return $e(i),e;if(o===r)return $e(i),t;o=o.sibling}throw Error(a(188))}if(n.return!==r.return)n=i,r=o;else{for(var l=!1,s=i.child;s;){if(s===n){l=!0,n=i,r=o;break}if(s===r){l=!0,r=i,n=o;break}s=s.sibling}if(!l){for(s=o.child;s;){if(s===n){l=!0,n=o,r=i;break}if(s===r){l=!0,r=o,n=i;break}s=s.sibling}if(!l)throw Error(a(189))}}if(n.alternate!==r)throw Error(a(190))}if(3!==n.tag)throw Error(a(188));return n.stateNode.current===n?e:t}(e))?Ke(e):null}function Ke(e){if(5===e.tag||6===e.tag)return e;for(e=e.child;null!==e;){var t=Ke(e);if(null!==t)return t;e=e.sibling}return null}var qe=i.unstable_scheduleCallback,Qe=i.unstable_cancelCallback,Ge=i.unstable_shouldYield,Xe=i.unstable_requestPaint,Ye=i.unstable_now,Je=i.unstable_getCurrentPriorityLevel,Ze=i.unstable_ImmediatePriority,et=i.unstable_UserBlockingPriority,tt=i.unstable_NormalPriority,nt=i.unstable_LowPriority,rt=i.unstable_IdlePriority,it=null,at=null;var ot=Math.clz32?Math.clz32:function(e){return e>>>=0,0===e?32:31-(lt(e)/st|0)|0},lt=Math.log,st=Math.LN2;var ut=64,ct=4194304;function dt(e){switch(e&-e){case 1:return 1;case 2:return 2;case 4:return 4;case 8:return 8;case 16:return 16;case 32:return 32;case 64:case 128:case 256:case 512:case 1024:case 2048:case 4096:case 8192:case 16384:case 32768:case 65536:case 131072:case 262144:case 524288:case 1048576:case 2097152:return 4194240&e;case 4194304:case 8388608:case 16777216:case 33554432:case 67108864:return 130023424&e;case 134217728:return 134217728;case 268435456:return 268435456;case 536870912:return 536870912;case 1073741824:return 1073741824;default:return e}}function ft(e,t){var n=e.pendingLanes;if(0===n)return 0;var r=0,i=e.suspendedLanes,a=e.pingedLanes,o=268435455&n;if(0!==o){var l=o&~i;0!==l?r=dt(l):0!==(a&=o)&&(r=dt(a))}else 0!==(o=n&~i)?r=dt(o):0!==a&&(r=dt(a));if(0===r)return 0;if(0!==t&&t!==r&&0==(t&i)&&((i=r&-r)>=(a=t&-t)||16===i&&0!=(4194240&a)))return t;if(0!=(4&r)&&(r|=16&n),0!==(t=e.entangledLanes))for(e=e.entanglements,t&=r;0<t;)i=1<<(n=31-ot(t)),r|=e[n],t&=~i;return r}function ht(e,t){switch(e){case 1:case 2:case 4:return t+250;case 8:case 16:case 32:case 64:case 128:case 256:case 512:case 1024:case 2048:case 4096:case 8192:case 16384:case 32768:case 65536:case 131072:case 262144:case 524288:case 1048576:case 2097152:return t+5e3;default:return-1}}function pt(e){return 0!==(e=-1073741825&e.pendingLanes)?e:1073741824&e?1073741824:0}function vt(){var e=ut;return 0==(4194240&(ut<<=1))&&(ut=64),e}function mt(e){for(var t=[],n=0;31>n;n++)t.push(e);return t}function gt(e,t,n){e.pendingLanes|=t,536870912!==t&&(e.suspendedLanes=0,e.pingedLanes=0),(e=e.eventTimes)[t=31-ot(t)]=n}function yt(e,t){var n=e.entangledLanes|=t;for(e=e.entanglements;n;){var r=31-ot(n),i=1<<r;i&t|e[r]&t&&(e[r]|=t),n&=~i}}var bt=0;function _t(e){return 1<(e&=-e)?4<e?0!=(268435455&e)?16:536870912:4:1}var wt,kt,xt,St,zt,Pt=!1,Ct=[],jt=null,Ot=null,Et=null,Nt=new Map,Tt=new Map,Lt=[],It="mousedown mouseup touchcancel touchend touchstart auxclick dblclick pointercancel pointerdown pointerup dragend dragstart drop compositionend compositionstart keydown keypress keyup input textInput copy cut paste click change contextmenu reset submit".split(" ");function At(e,t){switch(e){case"focusin":case"focusout":jt=null;break;case"dragenter":case"dragleave":Ot=null;break;case"mouseover":case"mouseout":Et=null;break;case"pointerover":case"pointerout":Nt.delete(t.pointerId);break;case"gotpointercapture":case"lostpointercapture":Tt.delete(t.pointerId)}}function Mt(e,t,n,r,i,a){return null===e||e.nativeEvent!==a?(e={blockedOn:t,domEventName:n,eventSystemFlags:r,nativeEvent:a,targetContainers:[i]},null!==t&&(null!==(t=bi(t))&&kt(t)),e):(e.eventSystemFlags|=r,t=e.targetContainers,null!==i&&-1===t.indexOf(i)&&t.push(i),e)}function Rt(e){var t=yi(e.target);if(null!==t){var n=Fe(t);if(null!==n)if(13===(t=n.tag)){if(null!==(t=He(n)))return e.blockedOn=t,void zt(e.priority,(function(){xt(n)}))}else if(3===t&&n.stateNode.current.memoizedState.isDehydrated)return void(e.blockedOn=3===n.tag?n.stateNode.containerInfo:null)}e.blockedOn=null}function Dt(e){if(null!==e.blockedOn)return!1;for(var t=e.targetContainers;0<t.length;){var n=Gt(e.domEventName,e.eventSystemFlags,t[0],e.nativeEvent);if(null!==n)return null!==(t=bi(n))&&kt(t),e.blockedOn=n,!1;var r=new(n=e.nativeEvent).constructor(n.type,n);_e=r,n.target.dispatchEvent(r),_e=null,t.shift()}return!0}function Ut(e,t,n){Dt(e)&&n.delete(t)}function Vt(){Pt=!1,null!==jt&&Dt(jt)&&(jt=null),null!==Ot&&Dt(Ot)&&(Ot=null),null!==Et&&Dt(Et)&&(Et=null),Nt.forEach(Ut),Tt.forEach(Ut)}function Bt(e,t){e.blockedOn===t&&(e.blockedOn=null,Pt||(Pt=!0,i.unstable_scheduleCallback(i.unstable_NormalPriority,Vt)))}function Ft(e){function t(t){return Bt(t,e)}if(0<Ct.length){Bt(Ct[0],e);for(var n=1;n<Ct.length;n++){var r=Ct[n];r.blockedOn===e&&(r.blockedOn=null)}}for(null!==jt&&Bt(jt,e),null!==Ot&&Bt(Ot,e),null!==Et&&Bt(Et,e),Nt.forEach(t),Tt.forEach(t),n=0;n<Lt.length;n++)(r=Lt[n]).blockedOn===e&&(r.blockedOn=null);for(;0<Lt.length&&null===(n=Lt[0]).blockedOn;)Rt(n),null===n.blockedOn&&Lt.shift()}var Ht=_.ReactCurrentBatchConfig,$t=!0;function Wt(e,t,n,r){var i=bt,a=Ht.transition;Ht.transition=null;try{bt=1,qt(e,t,n,r)}finally{bt=i,Ht.transition=a}}function Kt(e,t,n,r){var i=bt,a=Ht.transition;Ht.transition=null;try{bt=4,qt(e,t,n,r)}finally{bt=i,Ht.transition=a}}function qt(e,t,n,r){if($t){var i=Gt(e,t,n,r);if(null===i)$r(e,t,r,Qt,n),At(e,r);else if(function(e,t,n,r,i){switch(t){case"focusin":return jt=Mt(jt,e,t,n,r,i),!0;case"dragenter":return Ot=Mt(Ot,e,t,n,r,i),!0;case"mouseover":return Et=Mt(Et,e,t,n,r,i),!0;case"pointerover":var a=i.pointerId;return Nt.set(a,Mt(Nt.get(a)||null,e,t,n,r,i)),!0;case"gotpointercapture":return a=i.pointerId,Tt.set(a,Mt(Tt.get(a)||null,e,t,n,r,i)),!0}return!1}(i,e,t,n,r))r.stopPropagation();else if(At(e,r),4&t&&-1<It.indexOf(e)){for(;null!==i;){var a=bi(i);if(null!==a&&wt(a),null===(a=Gt(e,t,n,r))&&$r(e,t,r,Qt,n),a===i)break;i=a}null!==i&&r.stopPropagation()}else $r(e,t,r,null,n)}}var Qt=null;function Gt(e,t,n,r){if(Qt=null,null!==(e=yi(e=we(r))))if(null===(t=Fe(e)))e=null;else if(13===(n=t.tag)){if(null!==(e=He(t)))return e;e=null}else if(3===n){if(t.stateNode.current.memoizedState.isDehydrated)return 3===t.tag?t.stateNode.containerInfo:null;e=null}else t!==e&&(e=null);return Qt=e,null}function Xt(e){switch(e){case"cancel":case"click":case"close":case"contextmenu":case"copy":case"cut":case"auxclick":case"dblclick":case"dragend":case"dragstart":case"drop":case"focusin":case"focusout":case"input":case"invalid":case"keydown":case"keypress":case"keyup":case"mousedown":case"mouseup":case"paste":case"pause":case"play":case"pointercancel":case"pointerdown":case"pointerup":case"ratechange":case"reset":case"resize":case"seeked":case"submit":case"touchcancel":case"touchend":case"touchstart":case"volumechange":case"change":case"selectionchange":case"textInput":case"compositionstart":case"compositionend":case"compositionupdate":case"beforeblur":case"afterblur":case"beforeinput":case"blur":case"fullscreenchange":case"focus":case"hashchange":case"popstate":case"select":case"selectstart":return 1;case"drag":case"dragenter":case"dragexit":case"dragleave":case"dragover":case"mousemove":case"mouseout":case"mouseover":case"pointermove":case"pointerout":case"pointerover":case"scroll":case"toggle":case"touchmove":case"wheel":case"mouseenter":case"mouseleave":case"pointerenter":case"pointerleave":return 4;case"message":switch(Je()){case Ze:return 1;case et:return 4;case tt:case nt:return 16;case rt:return 536870912;default:return 16}default:return 16}}var Yt=null,Jt=null,Zt=null;function en(){if(Zt)return Zt;var e,t,n=Jt,r=n.length,i="value"in Yt?Yt.value:Yt.textContent,a=i.length;for(e=0;e<r&&n[e]===i[e];e++);var o=r-e;for(t=1;t<=o&&n[r-t]===i[a-t];t++);return Zt=i.slice(e,1<t?1-t:void 0)}function tn(e){var t=e.keyCode;return"charCode"in e?0===(e=e.charCode)&&13===t&&(e=13):e=t,10===e&&(e=13),32<=e||13===e?e:0}function nn(){return!0}function rn(){return!1}function an(e){function t(t,n,r,i,a){for(var o in this._reactName=t,this._targetInst=r,this.type=n,this.nativeEvent=i,this.target=a,this.currentTarget=null,e)e.hasOwnProperty(o)&&(t=e[o],this[o]=t?t(i):i[o]);return this.isDefaultPrevented=(null!=i.defaultPrevented?i.defaultPrevented:!1===i.returnValue)?nn:rn,this.isPropagationStopped=rn,this}return R(t.prototype,{preventDefault:function(){this.defaultPrevented=!0;var e=this.nativeEvent;e&&(e.preventDefault?e.preventDefault():"unknown"!=typeof e.returnValue&&(e.returnValue=!1),this.isDefaultPrevented=nn)},stopPropagation:function(){var e=this.nativeEvent;e&&(e.stopPropagation?e.stopPropagation():"unknown"!=typeof e.cancelBubble&&(e.cancelBubble=!0),this.isPropagationStopped=nn)},persist:function(){},isPersistent:nn}),t}var on,ln,sn,un={eventPhase:0,bubbles:0,cancelable:0,timeStamp:function(e){return e.timeStamp||Date.now()},defaultPrevented:0,isTrusted:0},cn=an(un),dn=R({},un,{view:0,detail:0}),fn=an(dn),hn=R({},dn,{screenX:0,screenY:0,clientX:0,clientY:0,pageX:0,pageY:0,ctrlKey:0,shiftKey:0,altKey:0,metaKey:0,getModifierState:zn,button:0,buttons:0,relatedTarget:function(e){return void 0===e.relatedTarget?e.fromElement===e.srcElement?e.toElement:e.fromElement:e.relatedTarget},movementX:function(e){return"movementX"in e?e.movementX:(e!==sn&&(sn&&"mousemove"===e.type?(on=e.screenX-sn.screenX,ln=e.screenY-sn.screenY):ln=on=0,sn=e),on)},movementY:function(e){return"movementY"in e?e.movementY:ln}}),pn=an(hn),vn=an(R({},hn,{dataTransfer:0})),mn=an(R({},dn,{relatedTarget:0})),gn=an(R({},un,{animationName:0,elapsedTime:0,pseudoElement:0})),yn=R({},un,{clipboardData:function(e){return"clipboardData"in e?e.clipboardData:window.clipboardData}}),bn=an(yn),_n=an(R({},un,{data:0})),wn={Esc:"Escape",Spacebar:" ",Left:"ArrowLeft",Up:"ArrowUp",Right:"ArrowRight",Down:"ArrowDown",Del:"Delete",Win:"OS",Menu:"ContextMenu",Apps:"ContextMenu",Scroll:"ScrollLock",MozPrintableKey:"Unidentified"},kn={8:"Backspace",9:"Tab",12:"Clear",13:"Enter",16:"Shift",17:"Control",18:"Alt",19:"Pause",20:"CapsLock",27:"Escape",32:" ",33:"PageUp",34:"PageDown",35:"End",36:"Home",37:"ArrowLeft",38:"ArrowUp",39:"ArrowRight",40:"ArrowDown",45:"Insert",46:"Delete",112:"F1",113:"F2",114:"F3",115:"F4",116:"F5",117:"F6",118:"F7",119:"F8",120:"F9",121:"F10",122:"F11",123:"F12",144:"NumLock",145:"ScrollLock",224:"Meta"},xn={Alt:"altKey",Control:"ctrlKey",Meta:"metaKey",Shift:"shiftKey"};function Sn(e){var t=this.nativeEvent;return t.getModifierState?t.getModifierState(e):!!(e=xn[e])&&!!t[e]}function zn(){return Sn}var Pn=R({},dn,{key:function(e){if(e.key){var t=wn[e.key]||e.key;if("Unidentified"!==t)return t}return"keypress"===e.type?13===(e=tn(e))?"Enter":String.fromCharCode(e):"keydown"===e.type||"keyup"===e.type?kn[e.keyCode]||"Unidentified":""},code:0,location:0,ctrlKey:0,shiftKey:0,altKey:0,metaKey:0,repeat:0,locale:0,getModifierState:zn,charCode:function(e){return"keypress"===e.type?tn(e):0},keyCode:function(e){return"keydown"===e.type||"keyup"===e.type?e.keyCode:0},which:function(e){return"keypress"===e.type?tn(e):"keydown"===e.type||"keyup"===e.type?e.keyCode:0}}),Cn=an(Pn),jn=an(R({},hn,{pointerId:0,width:0,height:0,pressure:0,tangentialPressure:0,tiltX:0,tiltY:0,twist:0,pointerType:0,isPrimary:0})),On=an(R({},dn,{touches:0,targetTouches:0,changedTouches:0,altKey:0,metaKey:0,ctrlKey:0,shiftKey:0,getModifierState:zn})),En=an(R({},un,{propertyName:0,elapsedTime:0,pseudoElement:0})),Nn=R({},hn,{deltaX:function(e){return"deltaX"in e?e.deltaX:"wheelDeltaX"in e?-e.wheelDeltaX:0},deltaY:function(e){return"deltaY"in e?e.deltaY:"wheelDeltaY"in e?-e.wheelDeltaY:"wheelDelta"in e?-e.wheelDelta:0},deltaZ:0,deltaMode:0}),Tn=an(Nn),Ln=[9,13,27,32],In=c&&"CompositionEvent"in window,An=null;c&&"documentMode"in document&&(An=document.documentMode);var Mn=c&&"TextEvent"in window&&!An,Rn=c&&(!In||An&&8<An&&11>=An),Dn=String.fromCharCode(32),Un=!1;function Vn(e,t){switch(e){case"keyup":return-1!==Ln.indexOf(t.keyCode);case"keydown":return 229!==t.keyCode;case"keypress":case"mousedown":case"focusout":return!0;default:return!1}}function Bn(e){return"object"==typeof(e=e.detail)&&"data"in e?e.data:null}var Fn=!1;var Hn={color:!0,date:!0,datetime:!0,"datetime-local":!0,email:!0,month:!0,number:!0,password:!0,range:!0,search:!0,tel:!0,text:!0,time:!0,url:!0,week:!0};function $n(e){var t=e&&e.nodeName&&e.nodeName.toLowerCase();return"input"===t?!!Hn[e.type]:"textarea"===t}function Wn(e,t,n,r){Pe(r),0<(t=Kr(t,"onChange")).length&&(n=new cn("onChange","change",null,n,r),e.push({event:n,listeners:t}))}var Kn=null,qn=null;function Qn(e){Dr(e,0)}function Gn(e){if(q(_i(e)))return e}function Xn(e,t){if("change"===e)return t}var Yn=!1;if(c){var Jn;if(c){var Zn="oninput"in document;if(!Zn){var er=document.createElement("div");er.setAttribute("oninput","return;"),Zn="function"==typeof er.oninput}Jn=Zn}else Jn=!1;Yn=Jn&&(!document.documentMode||9<document.documentMode)}function tr(){Kn&&(Kn.detachEvent("onpropertychange",nr),qn=Kn=null)}function nr(e){if("value"===e.propertyName&&Gn(qn)){var t=[];Wn(t,qn,e,we(e)),Ne(Qn,t)}}function rr(e,t,n){"focusin"===e?(tr(),qn=n,(Kn=t).attachEvent("onpropertychange",nr)):"focusout"===e&&tr()}function ir(e){if("selectionchange"===e||"keyup"===e||"keydown"===e)return Gn(qn)}function ar(e,t){if("click"===e)return Gn(t)}function or(e,t){if("input"===e||"change"===e)return Gn(t)}var lr="function"==typeof Object.is?Object.is:function(e,t){return e===t&&(0!==e||1/e==1/t)||e!=e&&t!=t};function sr(e,t){if(lr(e,t))return!0;if("object"!=typeof e||null===e||"object"!=typeof t||null===t)return!1;var n=Object.keys(e),r=Object.keys(t);if(n.length!==r.length)return!1;for(r=0;r<n.length;r++){var i=n[r];if(!d.call(t,i)||!lr(e[i],t[i]))return!1}return!0}function ur(e){for(;e&&e.firstChild;)e=e.firstChild;return e}function cr(e,t){var n,r=ur(e);for(e=0;r;){if(3===r.nodeType){if(n=e+r.textContent.length,e<=t&&n>=t)return{node:r,offset:t-e};e=n}e:{for(;r;){if(r.nextSibling){r=r.nextSibling;break e}r=r.parentNode}r=void 0}r=ur(r)}}function dr(e,t){return!(!e||!t)&&(e===t||(!e||3!==e.nodeType)&&(t&&3===t.nodeType?dr(e,t.parentNode):"contains"in e?e.contains(t):!!e.compareDocumentPosition&&!!(16&e.compareDocumentPosition(t))))}function fr(){for(var e=window,t=Q();t instanceof e.HTMLIFrameElement;){try{var n="string"==typeof t.contentWindow.location.href}catch(e){n=!1}if(!n)break;t=Q((e=t.contentWindow).document)}return t}function hr(e){var t=e&&e.nodeName&&e.nodeName.toLowerCase();return t&&("input"===t&&("text"===e.type||"search"===e.type||"tel"===e.type||"url"===e.type||"password"===e.type)||"textarea"===t||"true"===e.contentEditable)}function pr(e){var t=fr(),n=e.focusedElem,r=e.selectionRange;if(t!==n&&n&&n.ownerDocument&&dr(n.ownerDocument.documentElement,n)){if(null!==r&&hr(n))if(t=r.start,void 0===(e=r.end)&&(e=t),"selectionStart"in n)n.selectionStart=t,n.selectionEnd=Math.min(e,n.value.length);else if((e=(t=n.ownerDocument||document)&&t.defaultView||window).getSelection){e=e.getSelection();var i=n.textContent.length,a=Math.min(r.start,i);r=void 0===r.end?a:Math.min(r.end,i),!e.extend&&a>r&&(i=r,r=a,a=i),i=cr(n,a);var o=cr(n,r);i&&o&&(1!==e.rangeCount||e.anchorNode!==i.node||e.anchorOffset!==i.offset||e.focusNode!==o.node||e.focusOffset!==o.offset)&&((t=t.createRange()).setStart(i.node,i.offset),e.removeAllRanges(),a>r?(e.addRange(t),e.extend(o.node,o.offset)):(t.setEnd(o.node,o.offset),e.addRange(t)))}for(t=[],e=n;e=e.parentNode;)1===e.nodeType&&t.push({element:e,left:e.scrollLeft,top:e.scrollTop});for("function"==typeof n.focus&&n.focus(),n=0;n<t.length;n++)(e=t[n]).element.scrollLeft=e.left,e.element.scrollTop=e.top}}var vr=c&&"documentMode"in document&&11>=document.documentMode,mr=null,gr=null,yr=null,br=!1;function _r(e,t,n){var r=n.window===n?n.document:9===n.nodeType?n:n.ownerDocument;br||null==mr||mr!==Q(r)||("selectionStart"in(r=mr)&&hr(r)?r={start:r.selectionStart,end:r.selectionEnd}:r={anchorNode:(r=(r.ownerDocument&&r.ownerDocument.defaultView||window).getSelection()).anchorNode,anchorOffset:r.anchorOffset,focusNode:r.focusNode,focusOffset:r.focusOffset},yr&&sr(yr,r)||(yr=r,0<(r=Kr(gr,"onSelect")).length&&(t=new cn("onSelect","select",null,t,n),e.push({event:t,listeners:r}),t.target=mr)))}function wr(e,t){var n={};return n[e.toLowerCase()]=t.toLowerCase(),n["Webkit"+e]="webkit"+t,n["Moz"+e]="moz"+t,n}var kr={animationend:wr("Animation","AnimationEnd"),animationiteration:wr("Animation","AnimationIteration"),animationstart:wr("Animation","AnimationStart"),transitionend:wr("Transition","TransitionEnd")},xr={},Sr={};function zr(e){if(xr[e])return xr[e];if(!kr[e])return e;var t,n=kr[e];for(t in n)if(n.hasOwnProperty(t)&&t in Sr)return xr[e]=n[t];return e}c&&(Sr=document.createElement("div").style,"AnimationEvent"in window||(delete kr.animationend.animation,delete kr.animationiteration.animation,delete kr.animationstart.animation),"TransitionEvent"in window||delete kr.transitionend.transition);var Pr=zr("animationend"),Cr=zr("animationiteration"),jr=zr("animationstart"),Or=zr("transitionend"),Er=new Map,Nr="abort auxClick cancel canPlay canPlayThrough click close contextMenu copy cut drag dragEnd dragEnter dragExit dragLeave dragOver dragStart drop durationChange emptied encrypted ended error gotPointerCapture input invalid keyDown keyPress keyUp load loadedData loadedMetadata loadStart lostPointerCapture mouseDown mouseMove mouseOut mouseOver mouseUp paste pause play playing pointerCancel pointerDown pointerMove pointerOut pointerOver pointerUp progress rateChange reset resize seeked seeking stalled submit suspend timeUpdate touchCancel touchEnd touchStart volumeChange scroll toggle touchMove waiting wheel".split(" ");function Tr(e,t){Er.set(e,t),s(t,[e])}for(var Lr=0;Lr<Nr.length;Lr++){var Ir=Nr[Lr];Tr(Ir.toLowerCase(),"on"+(Ir[0].toUpperCase()+Ir.slice(1)))}Tr(Pr,"onAnimationEnd"),Tr(Cr,"onAnimationIteration"),Tr(jr,"onAnimationStart"),Tr("dblclick","onDoubleClick"),Tr("focusin","onFocus"),Tr("focusout","onBlur"),Tr(Or,"onTransitionEnd"),u("onMouseEnter",["mouseout","mouseover"]),u("onMouseLeave",["mouseout","mouseover"]),u("onPointerEnter",["pointerout","pointerover"]),u("onPointerLeave",["pointerout","pointerover"]),s("onChange","change click focusin focusout input keydown keyup selectionchange".split(" ")),s("onSelect","focusout contextmenu dragend focusin keydown keyup mousedown mouseup selectionchange".split(" ")),s("onBeforeInput",["compositionend","keypress","textInput","paste"]),s("onCompositionEnd","compositionend focusout keydown keypress keyup mousedown".split(" ")),s("onCompositionStart","compositionstart focusout keydown keypress keyup mousedown".split(" ")),s("onCompositionUpdate","compositionupdate focusout keydown keypress keyup mousedown".split(" "));var Ar="abort canplay canplaythrough durationchange emptied encrypted ended error loadeddata loadedmetadata loadstart pause play playing progress ratechange resize seeked seeking stalled suspend timeupdate volumechange waiting".split(" "),Mr=new Set("cancel close invalid load scroll toggle".split(" ").concat(Ar));function Rr(e,t,n){var r=e.type||"unknown-event";e.currentTarget=n,function(e,t,n,r,i,o,l,s,u){if(Be.apply(this,arguments),Me){if(!Me)throw Error(a(198));var c=Re;Me=!1,Re=null,De||(De=!0,Ue=c)}}(r,t,void 0,e),e.currentTarget=null}function Dr(e,t){t=0!=(4&t);for(var n=0;n<e.length;n++){var r=e[n],i=r.event;r=r.listeners;e:{var a=void 0;if(t)for(var o=r.length-1;0<=o;o--){var l=r[o],s=l.instance,u=l.currentTarget;if(l=l.listener,s!==a&&i.isPropagationStopped())break e;Rr(i,l,u),a=s}else for(o=0;o<r.length;o++){if(s=(l=r[o]).instance,u=l.currentTarget,l=l.listener,s!==a&&i.isPropagationStopped())break e;Rr(i,l,u),a=s}}}if(De)throw e=Ue,De=!1,Ue=null,e}function Ur(e,t){var n=t[vi];void 0===n&&(n=t[vi]=new Set);var r=e+"__bubble";n.has(r)||(Hr(t,e,2,!1),n.add(r))}function Vr(e,t,n){var r=0;t&&(r|=4),Hr(n,e,r,t)}var Br="_reactListening"+Math.random().toString(36).slice(2);function Fr(e){if(!e[Br]){e[Br]=!0,o.forEach((function(t){"selectionchange"!==t&&(Mr.has(t)||Vr(t,!1,e),Vr(t,!0,e))}));var t=9===e.nodeType?e:e.ownerDocument;null===t||t[Br]||(t[Br]=!0,Vr("selectionchange",!1,t))}}function Hr(e,t,n,r){switch(Xt(t)){case 1:var i=Wt;break;case 4:i=Kt;break;default:i=qt}n=i.bind(null,t,n,e),i=void 0,!Le||"touchstart"!==t&&"touchmove"!==t&&"wheel"!==t||(i=!0),r?void 0!==i?e.addEventListener(t,n,{capture:!0,passive:i}):e.addEventListener(t,n,!0):void 0!==i?e.addEventListener(t,n,{passive:i}):e.addEventListener(t,n,!1)}function $r(e,t,n,r,i){var a=r;if(0==(1&t)&&0==(2&t)&&null!==r)e:for(;;){if(null===r)return;var o=r.tag;if(3===o||4===o){var l=r.stateNode.containerInfo;if(l===i||8===l.nodeType&&l.parentNode===i)break;if(4===o)for(o=r.return;null!==o;){var s=o.tag;if((3===s||4===s)&&((s=o.stateNode.containerInfo)===i||8===s.nodeType&&s.parentNode===i))return;o=o.return}for(;null!==l;){if(null===(o=yi(l)))return;if(5===(s=o.tag)||6===s){r=a=o;continue e}l=l.parentNode}}r=r.return}Ne((function(){var r=a,i=we(n),o=[];e:{var l=Er.get(e);if(void 0!==l){var s=cn,u=e;switch(e){case"keypress":if(0===tn(n))break e;case"keydown":case"keyup":s=Cn;break;case"focusin":u="focus",s=mn;break;case"focusout":u="blur",s=mn;break;case"beforeblur":case"afterblur":s=mn;break;case"click":if(2===n.button)break e;case"auxclick":case"dblclick":case"mousedown":case"mousemove":case"mouseup":case"mouseout":case"mouseover":case"contextmenu":s=pn;break;case"drag":case"dragend":case"dragenter":case"dragexit":case"dragleave":case"dragover":case"dragstart":case"drop":s=vn;break;case"touchcancel":case"touchend":case"touchmove":case"touchstart":s=On;break;case Pr:case Cr:case jr:s=gn;break;case Or:s=En;break;case"scroll":s=fn;break;case"wheel":s=Tn;break;case"copy":case"cut":case"paste":s=bn;break;case"gotpointercapture":case"lostpointercapture":case"pointercancel":case"pointerdown":case"pointermove":case"pointerout":case"pointerover":case"pointerup":s=jn}var c=0!=(4&t),d=!c&&"scroll"===e,f=c?null!==l?l+"Capture":null:l;c=[];for(var h,p=r;null!==p;){var v=(h=p).stateNode;if(5===h.tag&&null!==v&&(h=v,null!==f&&(null!=(v=Te(p,f))&&c.push(Wr(p,v,h)))),d)break;p=p.return}0<c.length&&(l=new s(l,u,null,n,i),o.push({event:l,listeners:c}))}}if(0==(7&t)){if(s="mouseout"===e||"pointerout"===e,(!(l="mouseover"===e||"pointerover"===e)||n===_e||!(u=n.relatedTarget||n.fromElement)||!yi(u)&&!u[pi])&&(s||l)&&(l=i.window===i?i:(l=i.ownerDocument)?l.defaultView||l.parentWindow:window,s?(s=r,null!==(u=(u=n.relatedTarget||n.toElement)?yi(u):null)&&(u!==(d=Fe(u))||5!==u.tag&&6!==u.tag)&&(u=null)):(s=null,u=r),s!==u)){if(c=pn,v="onMouseLeave",f="onMouseEnter",p="mouse","pointerout"!==e&&"pointerover"!==e||(c=jn,v="onPointerLeave",f="onPointerEnter",p="pointer"),d=null==s?l:_i(s),h=null==u?l:_i(u),(l=new c(v,p+"leave",s,n,i)).target=d,l.relatedTarget=h,v=null,yi(i)===r&&((c=new c(f,p+"enter",u,n,i)).target=h,c.relatedTarget=d,v=c),d=v,s&&u)e:{for(f=u,p=0,h=c=s;h;h=qr(h))p++;for(h=0,v=f;v;v=qr(v))h++;for(;0<p-h;)c=qr(c),p--;for(;0<h-p;)f=qr(f),h--;for(;p--;){if(c===f||null!==f&&c===f.alternate)break e;c=qr(c),f=qr(f)}c=null}else c=null;null!==s&&Qr(o,l,s,c,!1),null!==u&&null!==d&&Qr(o,d,u,c,!0)}if("select"===(s=(l=r?_i(r):window).nodeName&&l.nodeName.toLowerCase())||"input"===s&&"file"===l.type)var m=Xn;else if($n(l))if(Yn)m=or;else{m=ir;var g=rr}else(s=l.nodeName)&&"input"===s.toLowerCase()&&("checkbox"===l.type||"radio"===l.type)&&(m=ar);switch(m&&(m=m(e,r))?Wn(o,m,n,i):(g&&g(e,l,r),"focusout"===e&&(g=l._wrapperState)&&g.controlled&&"number"===l.type&&ee(l,"number",l.value)),g=r?_i(r):window,e){case"focusin":($n(g)||"true"===g.contentEditable)&&(mr=g,gr=r,yr=null);break;case"focusout":yr=gr=mr=null;break;case"mousedown":br=!0;break;case"contextmenu":case"mouseup":case"dragend":br=!1,_r(o,n,i);break;case"selectionchange":if(vr)break;case"keydown":case"keyup":_r(o,n,i)}var y;if(In)e:{switch(e){case"compositionstart":var b="onCompositionStart";break e;case"compositionend":b="onCompositionEnd";break e;case"compositionupdate":b="onCompositionUpdate";break e}b=void 0}else Fn?Vn(e,n)&&(b="onCompositionEnd"):"keydown"===e&&229===n.keyCode&&(b="onCompositionStart");b&&(Rn&&"ko"!==n.locale&&(Fn||"onCompositionStart"!==b?"onCompositionEnd"===b&&Fn&&(y=en()):(Jt="value"in(Yt=i)?Yt.value:Yt.textContent,Fn=!0)),0<(g=Kr(r,b)).length&&(b=new _n(b,e,null,n,i),o.push({event:b,listeners:g}),y?b.data=y:null!==(y=Bn(n))&&(b.data=y))),(y=Mn?function(e,t){switch(e){case"compositionend":return Bn(t);case"keypress":return 32!==t.which?null:(Un=!0,Dn);case"textInput":return(e=t.data)===Dn&&Un?null:e;default:return null}}(e,n):function(e,t){if(Fn)return"compositionend"===e||!In&&Vn(e,t)?(e=en(),Zt=Jt=Yt=null,Fn=!1,e):null;switch(e){case"paste":default:return null;case"keypress":if(!(t.ctrlKey||t.altKey||t.metaKey)||t.ctrlKey&&t.altKey){if(t.char&&1<t.char.length)return t.char;if(t.which)return String.fromCharCode(t.which)}return null;case"compositionend":return Rn&&"ko"!==t.locale?null:t.data}}(e,n))&&(0<(r=Kr(r,"onBeforeInput")).length&&(i=new _n("onBeforeInput","beforeinput",null,n,i),o.push({event:i,listeners:r}),i.data=y))}Dr(o,t)}))}function Wr(e,t,n){return{instance:e,listener:t,currentTarget:n}}function Kr(e,t){for(var n=t+"Capture",r=[];null!==e;){var i=e,a=i.stateNode;5===i.tag&&null!==a&&(i=a,null!=(a=Te(e,n))&&r.unshift(Wr(e,a,i)),null!=(a=Te(e,t))&&r.push(Wr(e,a,i))),e=e.return}return r}function qr(e){if(null===e)return null;do{e=e.return}while(e&&5!==e.tag);return e||null}function Qr(e,t,n,r,i){for(var a=t._reactName,o=[];null!==n&&n!==r;){var l=n,s=l.alternate,u=l.stateNode;if(null!==s&&s===r)break;5===l.tag&&null!==u&&(l=u,i?null!=(s=Te(n,a))&&o.unshift(Wr(n,s,l)):i||null!=(s=Te(n,a))&&o.push(Wr(n,s,l))),n=n.return}0!==o.length&&e.push({event:t,listeners:o})}var Gr=/\r\n?/g,Xr=/\u0000|\uFFFD/g;function Yr(e){return("string"==typeof e?e:""+e).replace(Gr,"\n").replace(Xr,"")}function Jr(e,t,n){if(t=Yr(t),Yr(e)!==t&&n)throw Error(a(425))}function Zr(){}var ei=null,ti=null;function ni(e,t){return"textarea"===e||"noscript"===e||"string"==typeof t.children||"number"==typeof t.children||"object"==typeof t.dangerouslySetInnerHTML&&null!==t.dangerouslySetInnerHTML&&null!=t.dangerouslySetInnerHTML.__html}var ri="function"==typeof setTimeout?setTimeout:void 0,ii="function"==typeof clearTimeout?clearTimeout:void 0,ai="function"==typeof Promise?Promise:void 0,oi="function"==typeof queueMicrotask?queueMicrotask:void 0!==ai?function(e){return ai.resolve(null).then(e).catch(li)}:ri;function li(e){setTimeout((function(){throw e}))}function si(e,t){var n=t,r=0;do{var i=n.nextSibling;if(e.removeChild(n),i&&8===i.nodeType)if("/$"===(n=i.data)){if(0===r)return e.removeChild(i),void Ft(t);r--}else"$"!==n&&"$?"!==n&&"$!"!==n||r++;n=i}while(n);Ft(t)}function ui(e){for(;null!=e;e=e.nextSibling){var t=e.nodeType;if(1===t||3===t)break;if(8===t){if("$"===(t=e.data)||"$!"===t||"$?"===t)break;if("/$"===t)return null}}return e}function ci(e){e=e.previousSibling;for(var t=0;e;){if(8===e.nodeType){var n=e.data;if("$"===n||"$!"===n||"$?"===n){if(0===t)return e;t--}else"/$"===n&&t++}e=e.previousSibling}return null}var di=Math.random().toString(36).slice(2),fi="__reactFiber$"+di,hi="__reactProps$"+di,pi="__reactContainer$"+di,vi="__reactEvents$"+di,mi="__reactListeners$"+di,gi="__reactHandles$"+di;function yi(e){var t=e[fi];if(t)return t;for(var n=e.parentNode;n;){if(t=n[pi]||n[fi]){if(n=t.alternate,null!==t.child||null!==n&&null!==n.child)for(e=ci(e);null!==e;){if(n=e[fi])return n;e=ci(e)}return t}n=(e=n).parentNode}return null}function bi(e){return!(e=e[fi]||e[pi])||5!==e.tag&&6!==e.tag&&13!==e.tag&&3!==e.tag?null:e}function _i(e){if(5===e.tag||6===e.tag)return e.stateNode;throw Error(a(33))}function wi(e){return e[hi]||null}var ki=[],xi=-1;function Si(e){return{current:e}}function zi(e){0>xi||(e.current=ki[xi],ki[xi]=null,xi--)}function Pi(e,t){xi++,ki[xi]=e.current,e.current=t}var Ci={},ji=Si(Ci),Oi=Si(!1),Ei=Ci;function Ni(e,t){var n=e.type.contextTypes;if(!n)return Ci;var r=e.stateNode;if(r&&r.__reactInternalMemoizedUnmaskedChildContext===t)return r.__reactInternalMemoizedMaskedChildContext;var i,a={};for(i in n)a[i]=t[i];return r&&((e=e.stateNode).__reactInternalMemoizedUnmaskedChildContext=t,e.__reactInternalMemoizedMaskedChildContext=a),a}function Ti(e){return null!=(e=e.childContextTypes)}function Li(){zi(Oi),zi(ji)}function Ii(e,t,n){if(ji.current!==Ci)throw Error(a(168));Pi(ji,t),Pi(Oi,n)}function Ai(e,t,n){var r=e.stateNode;if(t=t.childContextTypes,"function"!=typeof r.getChildContext)return n;for(var i in r=r.getChildContext())if(!(i in t))throw Error(a(108,H(e)||"Unknown",i));return R({},n,r)}function Mi(e){return e=(e=e.stateNode)&&e.__reactInternalMemoizedMergedChildContext||Ci,Ei=ji.current,Pi(ji,e),Pi(Oi,Oi.current),!0}function Ri(e,t,n){var r=e.stateNode;if(!r)throw Error(a(169));n?(e=Ai(e,t,Ei),r.__reactInternalMemoizedMergedChildContext=e,zi(Oi),zi(ji),Pi(ji,e)):zi(Oi),Pi(Oi,n)}var Di=null,Ui=!1,Vi=!1;function Bi(e){null===Di?Di=[e]:Di.push(e)}function Fi(){if(!Vi&&null!==Di){Vi=!0;var e=0,t=bt;try{var n=Di;for(bt=1;e<n.length;e++){var r=n[e];do{r=r(!0)}while(null!==r)}Di=null,Ui=!1}catch(t){throw null!==Di&&(Di=Di.slice(e+1)),qe(Ze,Fi),t}finally{bt=t,Vi=!1}}return null}var Hi=[],$i=0,Wi=null,Ki=0,qi=[],Qi=0,Gi=null,Xi=1,Yi="";function Ji(e,t){Hi[$i++]=Ki,Hi[$i++]=Wi,Wi=e,Ki=t}function Zi(e,t,n){qi[Qi++]=Xi,qi[Qi++]=Yi,qi[Qi++]=Gi,Gi=e;var r=Xi;e=Yi;var i=32-ot(r)-1;r&=~(1<<i),n+=1;var a=32-ot(t)+i;if(30<a){var o=i-i%5;a=(r&(1<<o)-1).toString(32),r>>=o,i-=o,Xi=1<<32-ot(t)+i|n<<i|r,Yi=a+e}else Xi=1<<a|n<<i|r,Yi=e}function ea(e){null!==e.return&&(Ji(e,1),Zi(e,1,0))}function ta(e){for(;e===Wi;)Wi=Hi[--$i],Hi[$i]=null,Ki=Hi[--$i],Hi[$i]=null;for(;e===Gi;)Gi=qi[--Qi],qi[Qi]=null,Yi=qi[--Qi],qi[Qi]=null,Xi=qi[--Qi],qi[Qi]=null}var na=null,ra=null,ia=!1,aa=null;function oa(e,t){var n=Tu(5,null,null,0);n.elementType="DELETED",n.stateNode=t,n.return=e,null===(t=e.deletions)?(e.deletions=[n],e.flags|=16):t.push(n)}function la(e,t){switch(e.tag){case 5:var n=e.type;return null!==(t=1!==t.nodeType||n.toLowerCase()!==t.nodeName.toLowerCase()?null:t)&&(e.stateNode=t,na=e,ra=ui(t.firstChild),!0);case 6:return null!==(t=""===e.pendingProps||3!==t.nodeType?null:t)&&(e.stateNode=t,na=e,ra=null,!0);case 13:return null!==(t=8!==t.nodeType?null:t)&&(n=null!==Gi?{id:Xi,overflow:Yi}:null,e.memoizedState={dehydrated:t,treeContext:n,retryLane:1073741824},(n=Tu(18,null,null,0)).stateNode=t,n.return=e,e.child=n,na=e,ra=null,!0);default:return!1}}function sa(e){return 0!=(1&e.mode)&&0==(128&e.flags)}function ua(e){if(ia){var t=ra;if(t){var n=t;if(!la(e,t)){if(sa(e))throw Error(a(418));t=ui(n.nextSibling);var r=na;t&&la(e,t)?oa(r,n):(e.flags=-4097&e.flags|2,ia=!1,na=e)}}else{if(sa(e))throw Error(a(418));e.flags=-4097&e.flags|2,ia=!1,na=e}}}function ca(e){for(e=e.return;null!==e&&5!==e.tag&&3!==e.tag&&13!==e.tag;)e=e.return;na=e}function da(e){if(e!==na)return!1;if(!ia)return ca(e),ia=!0,!1;var t;if((t=3!==e.tag)&&!(t=5!==e.tag)&&(t="head"!==(t=e.type)&&"body"!==t&&!ni(e.type,e.memoizedProps)),t&&(t=ra)){if(sa(e))throw fa(),Error(a(418));for(;t;)oa(e,t),t=ui(t.nextSibling)}if(ca(e),13===e.tag){if(!(e=null!==(e=e.memoizedState)?e.dehydrated:null))throw Error(a(317));e:{for(e=e.nextSibling,t=0;e;){if(8===e.nodeType){var n=e.data;if("/$"===n){if(0===t){ra=ui(e.nextSibling);break e}t--}else"$"!==n&&"$!"!==n&&"$?"!==n||t++}e=e.nextSibling}ra=null}}else ra=na?ui(e.stateNode.nextSibling):null;return!0}function fa(){for(var e=ra;e;)e=ui(e.nextSibling)}function ha(){ra=na=null,ia=!1}function pa(e){null===aa?aa=[e]:aa.push(e)}var va=_.ReactCurrentBatchConfig;function ma(e,t){if(e&&e.defaultProps){for(var n in t=R({},t),e=e.defaultProps)void 0===t[n]&&(t[n]=e[n]);return t}return t}var ga=Si(null),ya=null,ba=null,_a=null;function wa(){_a=ba=ya=null}function ka(e){var t=ga.current;zi(ga),e._currentValue=t}function xa(e,t,n){for(;null!==e;){var r=e.alternate;if((e.childLanes&t)!==t?(e.childLanes|=t,null!==r&&(r.childLanes|=t)):null!==r&&(r.childLanes&t)!==t&&(r.childLanes|=t),e===n)break;e=e.return}}function Sa(e,t){ya=e,_a=ba=null,null!==(e=e.dependencies)&&null!==e.firstContext&&(0!=(e.lanes&t)&&(_l=!0),e.firstContext=null)}function za(e){var t=e._currentValue;if(_a!==e)if(e={context:e,memoizedValue:t,next:null},null===ba){if(null===ya)throw Error(a(308));ba=e,ya.dependencies={lanes:0,firstContext:e}}else ba=ba.next=e;return t}var Pa=null;function Ca(e){null===Pa?Pa=[e]:Pa.push(e)}function ja(e,t,n,r){var i=t.interleaved;return null===i?(n.next=n,Ca(t)):(n.next=i.next,i.next=n),t.interleaved=n,Oa(e,r)}function Oa(e,t){e.lanes|=t;var n=e.alternate;for(null!==n&&(n.lanes|=t),n=e,e=e.return;null!==e;)e.childLanes|=t,null!==(n=e.alternate)&&(n.childLanes|=t),n=e,e=e.return;return 3===n.tag?n.stateNode:null}var Ea=!1;function Na(e){e.updateQueue={baseState:e.memoizedState,firstBaseUpdate:null,lastBaseUpdate:null,shared:{pending:null,interleaved:null,lanes:0},effects:null}}function Ta(e,t){e=e.updateQueue,t.updateQueue===e&&(t.updateQueue={baseState:e.baseState,firstBaseUpdate:e.firstBaseUpdate,lastBaseUpdate:e.lastBaseUpdate,shared:e.shared,effects:e.effects})}function La(e,t){return{eventTime:e,lane:t,tag:0,payload:null,callback:null,next:null}}function Ia(e,t,n){var r=e.updateQueue;if(null===r)return null;if(r=r.shared,0!=(2&Os)){var i=r.pending;return null===i?t.next=t:(t.next=i.next,i.next=t),r.pending=t,Oa(e,n)}return null===(i=r.interleaved)?(t.next=t,Ca(r)):(t.next=i.next,i.next=t),r.interleaved=t,Oa(e,n)}function Aa(e,t,n){if(null!==(t=t.updateQueue)&&(t=t.shared,0!=(4194240&n))){var r=t.lanes;n|=r&=e.pendingLanes,t.lanes=n,yt(e,n)}}function Ma(e,t){var n=e.updateQueue,r=e.alternate;if(null!==r&&n===(r=r.updateQueue)){var i=null,a=null;if(null!==(n=n.firstBaseUpdate)){do{var o={eventTime:n.eventTime,lane:n.lane,tag:n.tag,payload:n.payload,callback:n.callback,next:null};null===a?i=a=o:a=a.next=o,n=n.next}while(null!==n);null===a?i=a=t:a=a.next=t}else i=a=t;return n={baseState:r.baseState,firstBaseUpdate:i,lastBaseUpdate:a,shared:r.shared,effects:r.effects},void(e.updateQueue=n)}null===(e=n.lastBaseUpdate)?n.firstBaseUpdate=t:e.next=t,n.lastBaseUpdate=t}function Ra(e,t,n,r){var i=e.updateQueue;Ea=!1;var a=i.firstBaseUpdate,o=i.lastBaseUpdate,l=i.shared.pending;if(null!==l){i.shared.pending=null;var s=l,u=s.next;s.next=null,null===o?a=u:o.next=u,o=s;var c=e.alternate;null!==c&&((l=(c=c.updateQueue).lastBaseUpdate)!==o&&(null===l?c.firstBaseUpdate=u:l.next=u,c.lastBaseUpdate=s))}if(null!==a){var d=i.baseState;for(o=0,c=u=s=null,l=a;;){var f=l.lane,h=l.eventTime;if((r&f)===f){null!==c&&(c=c.next={eventTime:h,lane:0,tag:l.tag,payload:l.payload,callback:l.callback,next:null});e:{var p=e,v=l;switch(f=t,h=n,v.tag){case 1:if("function"==typeof(p=v.payload)){d=p.call(h,d,f);break e}d=p;break e;case 3:p.flags=-65537&p.flags|128;case 0:if(null==(f="function"==typeof(p=v.payload)?p.call(h,d,f):p))break e;d=R({},d,f);break e;case 2:Ea=!0}}null!==l.callback&&0!==l.lane&&(e.flags|=64,null===(f=i.effects)?i.effects=[l]:f.push(l))}else h={eventTime:h,lane:f,tag:l.tag,payload:l.payload,callback:l.callback,next:null},null===c?(u=c=h,s=d):c=c.next=h,o|=f;if(null===(l=l.next)){if(null===(l=i.shared.pending))break;l=(f=l).next,f.next=null,i.lastBaseUpdate=f,i.shared.pending=null}}if(null===c&&(s=d),i.baseState=s,i.firstBaseUpdate=u,i.lastBaseUpdate=c,null!==(t=i.shared.interleaved)){i=t;do{o|=i.lane,i=i.next}while(i!==t)}else null===a&&(i.shared.lanes=0);Rs|=o,e.lanes=o,e.memoizedState=d}}function Da(e,t,n){if(e=t.effects,t.effects=null,null!==e)for(t=0;t<e.length;t++){var r=e[t],i=r.callback;if(null!==i){if(r.callback=null,r=n,"function"!=typeof i)throw Error(a(191,i));i.call(r)}}}var Ua=(new r.Component).refs;function Va(e,t,n,r){n=null==(n=n(r,t=e.memoizedState))?t:R({},t,n),e.memoizedState=n,0===e.lanes&&(e.updateQueue.baseState=n)}var Ba={isMounted:function(e){return!!(e=e._reactInternals)&&Fe(e)===e},enqueueSetState:function(e,t,n){e=e._reactInternals;var r=tu(),i=nu(e),a=La(r,i);a.payload=t,null!=n&&(a.callback=n),null!==(t=Ia(e,a,i))&&(ru(t,e,i,r),Aa(t,e,i))},enqueueReplaceState:function(e,t,n){e=e._reactInternals;var r=tu(),i=nu(e),a=La(r,i);a.tag=1,a.payload=t,null!=n&&(a.callback=n),null!==(t=Ia(e,a,i))&&(ru(t,e,i,r),Aa(t,e,i))},enqueueForceUpdate:function(e,t){e=e._reactInternals;var n=tu(),r=nu(e),i=La(n,r);i.tag=2,null!=t&&(i.callback=t),null!==(t=Ia(e,i,r))&&(ru(t,e,r,n),Aa(t,e,r))}};function Fa(e,t,n,r,i,a,o){return"function"==typeof(e=e.stateNode).shouldComponentUpdate?e.shouldComponentUpdate(r,a,o):!t.prototype||!t.prototype.isPureReactComponent||(!sr(n,r)||!sr(i,a))}function Ha(e,t,n){var r=!1,i=Ci,a=t.contextType;return"object"==typeof a&&null!==a?a=za(a):(i=Ti(t)?Ei:ji.current,a=(r=null!=(r=t.contextTypes))?Ni(e,i):Ci),t=new t(n,a),e.memoizedState=null!==t.state&&void 0!==t.state?t.state:null,t.updater=Ba,e.stateNode=t,t._reactInternals=e,r&&((e=e.stateNode).__reactInternalMemoizedUnmaskedChildContext=i,e.__reactInternalMemoizedMaskedChildContext=a),t}function $a(e,t,n,r){e=t.state,"function"==typeof t.componentWillReceiveProps&&t.componentWillReceiveProps(n,r),"function"==typeof t.UNSAFE_componentWillReceiveProps&&t.UNSAFE_componentWillReceiveProps(n,r),t.state!==e&&Ba.enqueueReplaceState(t,t.state,null)}function Wa(e,t,n,r){var i=e.stateNode;i.props=n,i.state=e.memoizedState,i.refs=Ua,Na(e);var a=t.contextType;"object"==typeof a&&null!==a?i.context=za(a):(a=Ti(t)?Ei:ji.current,i.context=Ni(e,a)),i.state=e.memoizedState,"function"==typeof(a=t.getDerivedStateFromProps)&&(Va(e,t,a,n),i.state=e.memoizedState),"function"==typeof t.getDerivedStateFromProps||"function"==typeof i.getSnapshotBeforeUpdate||"function"!=typeof i.UNSAFE_componentWillMount&&"function"!=typeof i.componentWillMount||(t=i.state,"function"==typeof i.componentWillMount&&i.componentWillMount(),"function"==typeof i.UNSAFE_componentWillMount&&i.UNSAFE_componentWillMount(),t!==i.state&&Ba.enqueueReplaceState(i,i.state,null),Ra(e,n,i,r),i.state=e.memoizedState),"function"==typeof i.componentDidMount&&(e.flags|=4194308)}function Ka(e,t,n){if(null!==(e=n.ref)&&"function"!=typeof e&&"object"!=typeof e){if(n._owner){if(n=n._owner){if(1!==n.tag)throw Error(a(309));var r=n.stateNode}if(!r)throw Error(a(147,e));var i=r,o=""+e;return null!==t&&null!==t.ref&&"function"==typeof t.ref&&t.ref._stringRef===o?t.ref:(t=function(e){var t=i.refs;t===Ua&&(t=i.refs={}),null===e?delete t[o]:t[o]=e},t._stringRef=o,t)}if("string"!=typeof e)throw Error(a(284));if(!n._owner)throw Error(a(290,e))}return e}function qa(e,t){throw e=Object.prototype.toString.call(t),Error(a(31,"[object Object]"===e?"object with keys {"+Object.keys(t).join(", ")+"}":e))}function Qa(e){return(0,e._init)(e._payload)}function Ga(e){function t(t,n){if(e){var r=t.deletions;null===r?(t.deletions=[n],t.flags|=16):r.push(n)}}function n(n,r){if(!e)return null;for(;null!==r;)t(n,r),r=r.sibling;return null}function r(e,t){for(e=new Map;null!==t;)null!==t.key?e.set(t.key,t):e.set(t.index,t),t=t.sibling;return e}function i(e,t){return(e=Iu(e,t)).index=0,e.sibling=null,e}function o(t,n,r){return t.index=r,e?null!==(r=t.alternate)?(r=r.index)<n?(t.flags|=2,n):r:(t.flags|=2,n):(t.flags|=1048576,n)}function l(t){return e&&null===t.alternate&&(t.flags|=2),t}function s(e,t,n,r){return null===t||6!==t.tag?((t=Du(n,e.mode,r)).return=e,t):((t=i(t,n)).return=e,t)}function u(e,t,n,r){var a=n.type;return a===x?d(e,t,n.props.children,r,n.key):null!==t&&(t.elementType===a||"object"==typeof a&&null!==a&&a.$$typeof===T&&Qa(a)===t.type)?((r=i(t,n.props)).ref=Ka(e,t,n),r.return=e,r):((r=Au(n.type,n.key,n.props,null,e.mode,r)).ref=Ka(e,t,n),r.return=e,r)}function c(e,t,n,r){return null===t||4!==t.tag||t.stateNode.containerInfo!==n.containerInfo||t.stateNode.implementation!==n.implementation?((t=Uu(n,e.mode,r)).return=e,t):((t=i(t,n.children||[])).return=e,t)}function d(e,t,n,r,a){return null===t||7!==t.tag?((t=Mu(n,e.mode,r,a)).return=e,t):((t=i(t,n)).return=e,t)}function f(e,t,n){if("string"==typeof t&&""!==t||"number"==typeof t)return(t=Du(""+t,e.mode,n)).return=e,t;if("object"==typeof t&&null!==t){switch(t.$$typeof){case w:return(n=Au(t.type,t.key,t.props,null,e.mode,n)).ref=Ka(e,null,t),n.return=e,n;case k:return(t=Uu(t,e.mode,n)).return=e,t;case T:return f(e,(0,t._init)(t._payload),n)}if(te(t)||A(t))return(t=Mu(t,e.mode,n,null)).return=e,t;qa(e,t)}return null}function h(e,t,n,r){var i=null!==t?t.key:null;if("string"==typeof n&&""!==n||"number"==typeof n)return null!==i?null:s(e,t,""+n,r);if("object"==typeof n&&null!==n){switch(n.$$typeof){case w:return n.key===i?u(e,t,n,r):null;case k:return n.key===i?c(e,t,n,r):null;case T:return h(e,t,(i=n._init)(n._payload),r)}if(te(n)||A(n))return null!==i?null:d(e,t,n,r,null);qa(e,n)}return null}function p(e,t,n,r,i){if("string"==typeof r&&""!==r||"number"==typeof r)return s(t,e=e.get(n)||null,""+r,i);if("object"==typeof r&&null!==r){switch(r.$$typeof){case w:return u(t,e=e.get(null===r.key?n:r.key)||null,r,i);case k:return c(t,e=e.get(null===r.key?n:r.key)||null,r,i);case T:return p(e,t,n,(0,r._init)(r._payload),i)}if(te(r)||A(r))return d(t,e=e.get(n)||null,r,i,null);qa(t,r)}return null}function v(i,a,l,s){for(var u=null,c=null,d=a,v=a=0,m=null;null!==d&&v<l.length;v++){d.index>v?(m=d,d=null):m=d.sibling;var g=h(i,d,l[v],s);if(null===g){null===d&&(d=m);break}e&&d&&null===g.alternate&&t(i,d),a=o(g,a,v),null===c?u=g:c.sibling=g,c=g,d=m}if(v===l.length)return n(i,d),ia&&Ji(i,v),u;if(null===d){for(;v<l.length;v++)null!==(d=f(i,l[v],s))&&(a=o(d,a,v),null===c?u=d:c.sibling=d,c=d);return ia&&Ji(i,v),u}for(d=r(i,d);v<l.length;v++)null!==(m=p(d,i,v,l[v],s))&&(e&&null!==m.alternate&&d.delete(null===m.key?v:m.key),a=o(m,a,v),null===c?u=m:c.sibling=m,c=m);return e&&d.forEach((function(e){return t(i,e)})),ia&&Ji(i,v),u}function m(i,l,s,u){var c=A(s);if("function"!=typeof c)throw Error(a(150));if(null==(s=c.call(s)))throw Error(a(151));for(var d=c=null,v=l,m=l=0,g=null,y=s.next();null!==v&&!y.done;m++,y=s.next()){v.index>m?(g=v,v=null):g=v.sibling;var b=h(i,v,y.value,u);if(null===b){null===v&&(v=g);break}e&&v&&null===b.alternate&&t(i,v),l=o(b,l,m),null===d?c=b:d.sibling=b,d=b,v=g}if(y.done)return n(i,v),ia&&Ji(i,m),c;if(null===v){for(;!y.done;m++,y=s.next())null!==(y=f(i,y.value,u))&&(l=o(y,l,m),null===d?c=y:d.sibling=y,d=y);return ia&&Ji(i,m),c}for(v=r(i,v);!y.done;m++,y=s.next())null!==(y=p(v,i,m,y.value,u))&&(e&&null!==y.alternate&&v.delete(null===y.key?m:y.key),l=o(y,l,m),null===d?c=y:d.sibling=y,d=y);return e&&v.forEach((function(e){return t(i,e)})),ia&&Ji(i,m),c}return function e(r,a,o,s){if("object"==typeof o&&null!==o&&o.type===x&&null===o.key&&(o=o.props.children),"object"==typeof o&&null!==o){switch(o.$$typeof){case w:e:{for(var u=o.key,c=a;null!==c;){if(c.key===u){if((u=o.type)===x){if(7===c.tag){n(r,c.sibling),(a=i(c,o.props.children)).return=r,r=a;break e}}else if(c.elementType===u||"object"==typeof u&&null!==u&&u.$$typeof===T&&Qa(u)===c.type){n(r,c.sibling),(a=i(c,o.props)).ref=Ka(r,c,o),a.return=r,r=a;break e}n(r,c);break}t(r,c),c=c.sibling}o.type===x?((a=Mu(o.props.children,r.mode,s,o.key)).return=r,r=a):((s=Au(o.type,o.key,o.props,null,r.mode,s)).ref=Ka(r,a,o),s.return=r,r=s)}return l(r);case k:e:{for(c=o.key;null!==a;){if(a.key===c){if(4===a.tag&&a.stateNode.containerInfo===o.containerInfo&&a.stateNode.implementation===o.implementation){n(r,a.sibling),(a=i(a,o.children||[])).return=r,r=a;break e}n(r,a);break}t(r,a),a=a.sibling}(a=Uu(o,r.mode,s)).return=r,r=a}return l(r);case T:return e(r,a,(c=o._init)(o._payload),s)}if(te(o))return v(r,a,o,s);if(A(o))return m(r,a,o,s);qa(r,o)}return"string"==typeof o&&""!==o||"number"==typeof o?(o=""+o,null!==a&&6===a.tag?(n(r,a.sibling),(a=i(a,o)).return=r,r=a):(n(r,a),(a=Du(o,r.mode,s)).return=r,r=a),l(r)):n(r,a)}}var Xa=Ga(!0),Ya=Ga(!1),Ja={},Za=Si(Ja),eo=Si(Ja),to=Si(Ja);function no(e){if(e===Ja)throw Error(a(174));return e}function ro(e,t){switch(Pi(to,t),Pi(eo,e),Pi(Za,Ja),e=t.nodeType){case 9:case 11:t=(t=t.documentElement)?t.namespaceURI:se(null,"");break;default:t=se(t=(e=8===e?t.parentNode:t).namespaceURI||null,e=e.tagName)}zi(Za),Pi(Za,t)}function io(){zi(Za),zi(eo),zi(to)}function ao(e){no(to.current);var t=no(Za.current),n=se(t,e.type);t!==n&&(Pi(eo,e),Pi(Za,n))}function oo(e){eo.current===e&&(zi(Za),zi(eo))}var lo=Si(0);function so(e){for(var t=e;null!==t;){if(13===t.tag){var n=t.memoizedState;if(null!==n&&(null===(n=n.dehydrated)||"$?"===n.data||"$!"===n.data))return t}else if(19===t.tag&&void 0!==t.memoizedProps.revealOrder){if(0!=(128&t.flags))return t}else if(null!==t.child){t.child.return=t,t=t.child;continue}if(t===e)break;for(;null===t.sibling;){if(null===t.return||t.return===e)return null;t=t.return}t.sibling.return=t.return,t=t.sibling}return null}var uo=[];function co(){for(var e=0;e<uo.length;e++)uo[e]._workInProgressVersionPrimary=null;uo.length=0}var fo=_.ReactCurrentDispatcher,ho=_.ReactCurrentBatchConfig,po=0,vo=null,mo=null,go=null,yo=!1,bo=!1,_o=0,wo=0;function ko(){throw Error(a(321))}function xo(e,t){if(null===t)return!1;for(var n=0;n<t.length&&n<e.length;n++)if(!lr(e[n],t[n]))return!1;return!0}function So(e,t,n,r,i,o){if(po=o,vo=t,t.memoizedState=null,t.updateQueue=null,t.lanes=0,fo.current=null===e||null===e.memoizedState?ll:sl,e=n(r,i),bo){o=0;do{if(bo=!1,_o=0,25<=o)throw Error(a(301));o+=1,go=mo=null,t.updateQueue=null,fo.current=ul,e=n(r,i)}while(bo)}if(fo.current=ol,t=null!==mo&&null!==mo.next,po=0,go=mo=vo=null,yo=!1,t)throw Error(a(300));return e}function zo(){var e=0!==_o;return _o=0,e}function Po(){var e={memoizedState:null,baseState:null,baseQueue:null,queue:null,next:null};return null===go?vo.memoizedState=go=e:go=go.next=e,go}function Co(){if(null===mo){var e=vo.alternate;e=null!==e?e.memoizedState:null}else e=mo.next;var t=null===go?vo.memoizedState:go.next;if(null!==t)go=t,mo=e;else{if(null===e)throw Error(a(310));e={memoizedState:(mo=e).memoizedState,baseState:mo.baseState,baseQueue:mo.baseQueue,queue:mo.queue,next:null},null===go?vo.memoizedState=go=e:go=go.next=e}return go}function jo(e,t){return"function"==typeof t?t(e):t}function Oo(e){var t=Co(),n=t.queue;if(null===n)throw Error(a(311));n.lastRenderedReducer=e;var r=mo,i=r.baseQueue,o=n.pending;if(null!==o){if(null!==i){var l=i.next;i.next=o.next,o.next=l}r.baseQueue=i=o,n.pending=null}if(null!==i){o=i.next,r=r.baseState;var s=l=null,u=null,c=o;do{var d=c.lane;if((po&d)===d)null!==u&&(u=u.next={lane:0,action:c.action,hasEagerState:c.hasEagerState,eagerState:c.eagerState,next:null}),r=c.hasEagerState?c.eagerState:e(r,c.action);else{var f={lane:d,action:c.action,hasEagerState:c.hasEagerState,eagerState:c.eagerState,next:null};null===u?(s=u=f,l=r):u=u.next=f,vo.lanes|=d,Rs|=d}c=c.next}while(null!==c&&c!==o);null===u?l=r:u.next=s,lr(r,t.memoizedState)||(_l=!0),t.memoizedState=r,t.baseState=l,t.baseQueue=u,n.lastRenderedState=r}if(null!==(e=n.interleaved)){i=e;do{o=i.lane,vo.lanes|=o,Rs|=o,i=i.next}while(i!==e)}else null===i&&(n.lanes=0);return[t.memoizedState,n.dispatch]}function Eo(e){var t=Co(),n=t.queue;if(null===n)throw Error(a(311));n.lastRenderedReducer=e;var r=n.dispatch,i=n.pending,o=t.memoizedState;if(null!==i){n.pending=null;var l=i=i.next;do{o=e(o,l.action),l=l.next}while(l!==i);lr(o,t.memoizedState)||(_l=!0),t.memoizedState=o,null===t.baseQueue&&(t.baseState=o),n.lastRenderedState=o}return[o,r]}function No(){}function To(e,t){var n=vo,r=Co(),i=t(),o=!lr(r.memoizedState,i);if(o&&(r.memoizedState=i,_l=!0),r=r.queue,$o(Ao.bind(null,n,r,e),[e]),r.getSnapshot!==t||o||null!==go&&1&go.memoizedState.tag){if(n.flags|=2048,Uo(9,Io.bind(null,n,r,i,t),void 0,null),null===Es)throw Error(a(349));0!=(30&po)||Lo(n,t,i)}return i}function Lo(e,t,n){e.flags|=16384,e={getSnapshot:t,value:n},null===(t=vo.updateQueue)?(t={lastEffect:null,stores:null},vo.updateQueue=t,t.stores=[e]):null===(n=t.stores)?t.stores=[e]:n.push(e)}function Io(e,t,n,r){t.value=n,t.getSnapshot=r,Mo(t)&&Ro(e)}function Ao(e,t,n){return n((function(){Mo(t)&&Ro(e)}))}function Mo(e){var t=e.getSnapshot;e=e.value;try{var n=t();return!lr(e,n)}catch(e){return!0}}function Ro(e){var t=Oa(e,1);null!==t&&ru(t,e,1,-1)}function Do(e){var t=Po();return"function"==typeof e&&(e=e()),t.memoizedState=t.baseState=e,e={pending:null,interleaved:null,lanes:0,dispatch:null,lastRenderedReducer:jo,lastRenderedState:e},t.queue=e,e=e.dispatch=nl.bind(null,vo,e),[t.memoizedState,e]}function Uo(e,t,n,r){return e={tag:e,create:t,destroy:n,deps:r,next:null},null===(t=vo.updateQueue)?(t={lastEffect:null,stores:null},vo.updateQueue=t,t.lastEffect=e.next=e):null===(n=t.lastEffect)?t.lastEffect=e.next=e:(r=n.next,n.next=e,e.next=r,t.lastEffect=e),e}function Vo(){return Co().memoizedState}function Bo(e,t,n,r){var i=Po();vo.flags|=e,i.memoizedState=Uo(1|t,n,void 0,void 0===r?null:r)}function Fo(e,t,n,r){var i=Co();r=void 0===r?null:r;var a=void 0;if(null!==mo){var o=mo.memoizedState;if(a=o.destroy,null!==r&&xo(r,o.deps))return void(i.memoizedState=Uo(t,n,a,r))}vo.flags|=e,i.memoizedState=Uo(1|t,n,a,r)}function Ho(e,t){return Bo(8390656,8,e,t)}function $o(e,t){return Fo(2048,8,e,t)}function Wo(e,t){return Fo(4,2,e,t)}function Ko(e,t){return Fo(4,4,e,t)}function qo(e,t){return"function"==typeof t?(e=e(),t(e),function(){t(null)}):null!=t?(e=e(),t.current=e,function(){t.current=null}):void 0}function Qo(e,t,n){return n=null!=n?n.concat([e]):null,Fo(4,4,qo.bind(null,t,e),n)}function Go(){}function Xo(e,t){var n=Co();t=void 0===t?null:t;var r=n.memoizedState;return null!==r&&null!==t&&xo(t,r[1])?r[0]:(n.memoizedState=[e,t],e)}function Yo(e,t){var n=Co();t=void 0===t?null:t;var r=n.memoizedState;return null!==r&&null!==t&&xo(t,r[1])?r[0]:(e=e(),n.memoizedState=[e,t],e)}function Jo(e,t,n){return 0==(21&po)?(e.baseState&&(e.baseState=!1,_l=!0),e.memoizedState=n):(lr(n,t)||(n=vt(),vo.lanes|=n,Rs|=n,e.baseState=!0),t)}function Zo(e,t){var n=bt;bt=0!==n&&4>n?n:4,e(!0);var r=ho.transition;ho.transition={};try{e(!1),t()}finally{bt=n,ho.transition=r}}function el(){return Co().memoizedState}function tl(e,t,n){var r=nu(e);if(n={lane:r,action:n,hasEagerState:!1,eagerState:null,next:null},rl(e))il(t,n);else if(null!==(n=ja(e,t,n,r))){ru(n,e,r,tu()),al(n,t,r)}}function nl(e,t,n){var r=nu(e),i={lane:r,action:n,hasEagerState:!1,eagerState:null,next:null};if(rl(e))il(t,i);else{var a=e.alternate;if(0===e.lanes&&(null===a||0===a.lanes)&&null!==(a=t.lastRenderedReducer))try{var o=t.lastRenderedState,l=a(o,n);if(i.hasEagerState=!0,i.eagerState=l,lr(l,o)){var s=t.interleaved;return null===s?(i.next=i,Ca(t)):(i.next=s.next,s.next=i),void(t.interleaved=i)}}catch(e){}null!==(n=ja(e,t,i,r))&&(ru(n,e,r,i=tu()),al(n,t,r))}}function rl(e){var t=e.alternate;return e===vo||null!==t&&t===vo}function il(e,t){bo=yo=!0;var n=e.pending;null===n?t.next=t:(t.next=n.next,n.next=t),e.pending=t}function al(e,t,n){if(0!=(4194240&n)){var r=t.lanes;n|=r&=e.pendingLanes,t.lanes=n,yt(e,n)}}var ol={readContext:za,useCallback:ko,useContext:ko,useEffect:ko,useImperativeHandle:ko,useInsertionEffect:ko,useLayoutEffect:ko,useMemo:ko,useReducer:ko,useRef:ko,useState:ko,useDebugValue:ko,useDeferredValue:ko,useTransition:ko,useMutableSource:ko,useSyncExternalStore:ko,useId:ko,unstable_isNewReconciler:!1},ll={readContext:za,useCallback:function(e,t){return Po().memoizedState=[e,void 0===t?null:t],e},useContext:za,useEffect:Ho,useImperativeHandle:function(e,t,n){return n=null!=n?n.concat([e]):null,Bo(4194308,4,qo.bind(null,t,e),n)},useLayoutEffect:function(e,t){return Bo(4194308,4,e,t)},useInsertionEffect:function(e,t){return Bo(4,2,e,t)},useMemo:function(e,t){var n=Po();return t=void 0===t?null:t,e=e(),n.memoizedState=[e,t],e},useReducer:function(e,t,n){var r=Po();return t=void 0!==n?n(t):t,r.memoizedState=r.baseState=t,e={pending:null,interleaved:null,lanes:0,dispatch:null,lastRenderedReducer:e,lastRenderedState:t},r.queue=e,e=e.dispatch=tl.bind(null,vo,e),[r.memoizedState,e]},useRef:function(e){return e={current:e},Po().memoizedState=e},useState:Do,useDebugValue:Go,useDeferredValue:function(e){return Po().memoizedState=e},useTransition:function(){var e=Do(!1),t=e[0];return e=Zo.bind(null,e[1]),Po().memoizedState=e,[t,e]},useMutableSource:function(){},useSyncExternalStore:function(e,t,n){var r=vo,i=Po();if(ia){if(void 0===n)throw Error(a(407));n=n()}else{if(n=t(),null===Es)throw Error(a(349));0!=(30&po)||Lo(r,t,n)}i.memoizedState=n;var o={value:n,getSnapshot:t};return i.queue=o,Ho(Ao.bind(null,r,o,e),[e]),r.flags|=2048,Uo(9,Io.bind(null,r,o,n,t),void 0,null),n},useId:function(){var e=Po(),t=Es.identifierPrefix;if(ia){var n=Yi;t=":"+t+"R"+(n=(Xi&~(1<<32-ot(Xi)-1)).toString(32)+n),0<(n=_o++)&&(t+="H"+n.toString(32)),t+=":"}else t=":"+t+"r"+(n=wo++).toString(32)+":";return e.memoizedState=t},unstable_isNewReconciler:!1},sl={readContext:za,useCallback:Xo,useContext:za,useEffect:$o,useImperativeHandle:Qo,useInsertionEffect:Wo,useLayoutEffect:Ko,useMemo:Yo,useReducer:Oo,useRef:Vo,useState:function(){return Oo(jo)},useDebugValue:Go,useDeferredValue:function(e){return Jo(Co(),mo.memoizedState,e)},useTransition:function(){return[Oo(jo)[0],Co().memoizedState]},useMutableSource:No,useSyncExternalStore:To,useId:el,unstable_isNewReconciler:!1},ul={readContext:za,useCallback:Xo,useContext:za,useEffect:$o,useImperativeHandle:Qo,useInsertionEffect:Wo,useLayoutEffect:Ko,useMemo:Yo,useReducer:Eo,useRef:Vo,useState:function(){return Eo(jo)},useDebugValue:Go,useDeferredValue:function(e){var t=Co();return null===mo?t.memoizedState=e:Jo(t,mo.memoizedState,e)},useTransition:function(){return[Eo(jo)[0],Co().memoizedState]},useMutableSource:No,useSyncExternalStore:To,useId:el,unstable_isNewReconciler:!1};function cl(e,t){try{var n="",r=t;do{n+=B(r),r=r.return}while(r);var i=n}catch(e){i="\nError generating stack: "+e.message+"\n"+e.stack}return{value:e,source:t,stack:i,digest:null}}function dl(e,t,n){return{value:e,source:null,stack:null!=n?n:null,digest:null!=t?t:null}}function fl(e,t){try{console.error(t.value)}catch(e){setTimeout((function(){throw e}))}}var hl="function"==typeof WeakMap?WeakMap:Map;function pl(e,t,n){(n=La(-1,n)).tag=3,n.payload={element:null};var r=t.value;return n.callback=function(){Ws||(Ws=!0,Ks=r),fl(0,t)},n}function vl(e,t,n){(n=La(-1,n)).tag=3;var r=e.type.getDerivedStateFromError;if("function"==typeof r){var i=t.value;n.payload=function(){return r(i)},n.callback=function(){fl(0,t)}}var a=e.stateNode;return null!==a&&"function"==typeof a.componentDidCatch&&(n.callback=function(){fl(0,t),"function"!=typeof r&&(null===qs?qs=new Set([this]):qs.add(this));var e=t.stack;this.componentDidCatch(t.value,{componentStack:null!==e?e:""})}),n}function ml(e,t,n){var r=e.pingCache;if(null===r){r=e.pingCache=new hl;var i=new Set;r.set(t,i)}else void 0===(i=r.get(t))&&(i=new Set,r.set(t,i));i.has(n)||(i.add(n),e=Pu.bind(null,e,t,n),t.then(e,e))}function gl(e){do{var t;if((t=13===e.tag)&&(t=null===(t=e.memoizedState)||null!==t.dehydrated),t)return e;e=e.return}while(null!==e);return null}function yl(e,t,n,r,i){return 0==(1&e.mode)?(e===t?e.flags|=65536:(e.flags|=128,n.flags|=131072,n.flags&=-52805,1===n.tag&&(null===n.alternate?n.tag=17:((t=La(-1,1)).tag=2,Ia(n,t,1))),n.lanes|=1),e):(e.flags|=65536,e.lanes=i,e)}var bl=_.ReactCurrentOwner,_l=!1;function wl(e,t,n,r){t.child=null===e?Ya(t,null,n,r):Xa(t,e.child,n,r)}function kl(e,t,n,r,i){n=n.render;var a=t.ref;return Sa(t,i),r=So(e,t,n,r,a,i),n=zo(),null===e||_l?(ia&&n&&ea(t),t.flags|=1,wl(e,t,r,i),t.child):(t.updateQueue=e.updateQueue,t.flags&=-2053,e.lanes&=~i,Wl(e,t,i))}function xl(e,t,n,r,i){if(null===e){var a=n.type;return"function"!=typeof a||Lu(a)||void 0!==a.defaultProps||null!==n.compare||void 0!==n.defaultProps?((e=Au(n.type,null,r,t,t.mode,i)).ref=t.ref,e.return=t,t.child=e):(t.tag=15,t.type=a,Sl(e,t,a,r,i))}if(a=e.child,0==(e.lanes&i)){var o=a.memoizedProps;if((n=null!==(n=n.compare)?n:sr)(o,r)&&e.ref===t.ref)return Wl(e,t,i)}return t.flags|=1,(e=Iu(a,r)).ref=t.ref,e.return=t,t.child=e}function Sl(e,t,n,r,i){if(null!==e){var a=e.memoizedProps;if(sr(a,r)&&e.ref===t.ref){if(_l=!1,t.pendingProps=r=a,0==(e.lanes&i))return t.lanes=e.lanes,Wl(e,t,i);0!=(131072&e.flags)&&(_l=!0)}}return Cl(e,t,n,r,i)}function zl(e,t,n){var r=t.pendingProps,i=r.children,a=null!==e?e.memoizedState:null;if("hidden"===r.mode)if(0==(1&t.mode))t.memoizedState={baseLanes:0,cachePool:null,transitions:null},Pi(Is,Ls),Ls|=n;else{if(0==(1073741824&n))return e=null!==a?a.baseLanes|n:n,t.lanes=t.childLanes=1073741824,t.memoizedState={baseLanes:e,cachePool:null,transitions:null},t.updateQueue=null,Pi(Is,Ls),Ls|=e,null;t.memoizedState={baseLanes:0,cachePool:null,transitions:null},r=null!==a?a.baseLanes:n,Pi(Is,Ls),Ls|=r}else null!==a?(r=a.baseLanes|n,t.memoizedState=null):r=n,Pi(Is,Ls),Ls|=r;return wl(e,t,i,n),t.child}function Pl(e,t){var n=t.ref;(null===e&&null!==n||null!==e&&e.ref!==n)&&(t.flags|=512,t.flags|=2097152)}function Cl(e,t,n,r,i){var a=Ti(n)?Ei:ji.current;return a=Ni(t,a),Sa(t,i),n=So(e,t,n,r,a,i),r=zo(),null===e||_l?(ia&&r&&ea(t),t.flags|=1,wl(e,t,n,i),t.child):(t.updateQueue=e.updateQueue,t.flags&=-2053,e.lanes&=~i,Wl(e,t,i))}function jl(e,t,n,r,i){if(Ti(n)){var a=!0;Mi(t)}else a=!1;if(Sa(t,i),null===t.stateNode)$l(e,t),Ha(t,n,r),Wa(t,n,r,i),r=!0;else if(null===e){var o=t.stateNode,l=t.memoizedProps;o.props=l;var s=o.context,u=n.contextType;"object"==typeof u&&null!==u?u=za(u):u=Ni(t,u=Ti(n)?Ei:ji.current);var c=n.getDerivedStateFromProps,d="function"==typeof c||"function"==typeof o.getSnapshotBeforeUpdate;d||"function"!=typeof o.UNSAFE_componentWillReceiveProps&&"function"!=typeof o.componentWillReceiveProps||(l!==r||s!==u)&&$a(t,o,r,u),Ea=!1;var f=t.memoizedState;o.state=f,Ra(t,r,o,i),s=t.memoizedState,l!==r||f!==s||Oi.current||Ea?("function"==typeof c&&(Va(t,n,c,r),s=t.memoizedState),(l=Ea||Fa(t,n,l,r,f,s,u))?(d||"function"!=typeof o.UNSAFE_componentWillMount&&"function"!=typeof o.componentWillMount||("function"==typeof o.componentWillMount&&o.componentWillMount(),"function"==typeof o.UNSAFE_componentWillMount&&o.UNSAFE_componentWillMount()),"function"==typeof o.componentDidMount&&(t.flags|=4194308)):("function"==typeof o.componentDidMount&&(t.flags|=4194308),t.memoizedProps=r,t.memoizedState=s),o.props=r,o.state=s,o.context=u,r=l):("function"==typeof o.componentDidMount&&(t.flags|=4194308),r=!1)}else{o=t.stateNode,Ta(e,t),l=t.memoizedProps,u=t.type===t.elementType?l:ma(t.type,l),o.props=u,d=t.pendingProps,f=o.context,"object"==typeof(s=n.contextType)&&null!==s?s=za(s):s=Ni(t,s=Ti(n)?Ei:ji.current);var h=n.getDerivedStateFromProps;(c="function"==typeof h||"function"==typeof o.getSnapshotBeforeUpdate)||"function"!=typeof o.UNSAFE_componentWillReceiveProps&&"function"!=typeof o.componentWillReceiveProps||(l!==d||f!==s)&&$a(t,o,r,s),Ea=!1,f=t.memoizedState,o.state=f,Ra(t,r,o,i);var p=t.memoizedState;l!==d||f!==p||Oi.current||Ea?("function"==typeof h&&(Va(t,n,h,r),p=t.memoizedState),(u=Ea||Fa(t,n,u,r,f,p,s)||!1)?(c||"function"!=typeof o.UNSAFE_componentWillUpdate&&"function"!=typeof o.componentWillUpdate||("function"==typeof o.componentWillUpdate&&o.componentWillUpdate(r,p,s),"function"==typeof o.UNSAFE_componentWillUpdate&&o.UNSAFE_componentWillUpdate(r,p,s)),"function"==typeof o.componentDidUpdate&&(t.flags|=4),"function"==typeof o.getSnapshotBeforeUpdate&&(t.flags|=1024)):("function"!=typeof o.componentDidUpdate||l===e.memoizedProps&&f===e.memoizedState||(t.flags|=4),"function"!=typeof o.getSnapshotBeforeUpdate||l===e.memoizedProps&&f===e.memoizedState||(t.flags|=1024),t.memoizedProps=r,t.memoizedState=p),o.props=r,o.state=p,o.context=s,r=u):("function"!=typeof o.componentDidUpdate||l===e.memoizedProps&&f===e.memoizedState||(t.flags|=4),"function"!=typeof o.getSnapshotBeforeUpdate||l===e.memoizedProps&&f===e.memoizedState||(t.flags|=1024),r=!1)}return Ol(e,t,n,r,a,i)}function Ol(e,t,n,r,i,a){Pl(e,t);var o=0!=(128&t.flags);if(!r&&!o)return i&&Ri(t,n,!1),Wl(e,t,a);r=t.stateNode,bl.current=t;var l=o&&"function"!=typeof n.getDerivedStateFromError?null:r.render();return t.flags|=1,null!==e&&o?(t.child=Xa(t,e.child,null,a),t.child=Xa(t,null,l,a)):wl(e,t,l,a),t.memoizedState=r.state,i&&Ri(t,n,!0),t.child}function El(e){var t=e.stateNode;t.pendingContext?Ii(0,t.pendingContext,t.pendingContext!==t.context):t.context&&Ii(0,t.context,!1),ro(e,t.containerInfo)}function Nl(e,t,n,r,i){return ha(),pa(i),t.flags|=256,wl(e,t,n,r),t.child}var Tl,Ll,Il,Al,Ml={dehydrated:null,treeContext:null,retryLane:0};function Rl(e){return{baseLanes:e,cachePool:null,transitions:null}}function Dl(e,t,n){var r,i=t.pendingProps,o=lo.current,l=!1,s=0!=(128&t.flags);if((r=s)||(r=(null===e||null!==e.memoizedState)&&0!=(2&o)),r?(l=!0,t.flags&=-129):null!==e&&null===e.memoizedState||(o|=1),Pi(lo,1&o),null===e)return ua(t),null!==(e=t.memoizedState)&&null!==(e=e.dehydrated)?(0==(1&t.mode)?t.lanes=1:"$!"===e.data?t.lanes=8:t.lanes=1073741824,null):(s=i.children,e=i.fallback,l?(i=t.mode,l=t.child,s={mode:"hidden",children:s},0==(1&i)&&null!==l?(l.childLanes=0,l.pendingProps=s):l=Ru(s,i,0,null),e=Mu(e,i,n,null),l.return=t,e.return=t,l.sibling=e,t.child=l,t.child.memoizedState=Rl(n),t.memoizedState=Ml,e):Ul(t,s));if(null!==(o=e.memoizedState)&&null!==(r=o.dehydrated))return function(e,t,n,r,i,o,l){if(n)return 256&t.flags?(t.flags&=-257,Vl(e,t,l,r=dl(Error(a(422))))):null!==t.memoizedState?(t.child=e.child,t.flags|=128,null):(o=r.fallback,i=t.mode,r=Ru({mode:"visible",children:r.children},i,0,null),(o=Mu(o,i,l,null)).flags|=2,r.return=t,o.return=t,r.sibling=o,t.child=r,0!=(1&t.mode)&&Xa(t,e.child,null,l),t.child.memoizedState=Rl(l),t.memoizedState=Ml,o);if(0==(1&t.mode))return Vl(e,t,l,null);if("$!"===i.data){if(r=i.nextSibling&&i.nextSibling.dataset)var s=r.dgst;return r=s,Vl(e,t,l,r=dl(o=Error(a(419)),r,void 0))}if(s=0!=(l&e.childLanes),_l||s){if(null!==(r=Es)){switch(l&-l){case 4:i=2;break;case 16:i=8;break;case 64:case 128:case 256:case 512:case 1024:case 2048:case 4096:case 8192:case 16384:case 32768:case 65536:case 131072:case 262144:case 524288:case 1048576:case 2097152:case 4194304:case 8388608:case 16777216:case 33554432:case 67108864:i=32;break;case 536870912:i=268435456;break;default:i=0}0!==(i=0!=(i&(r.suspendedLanes|l))?0:i)&&i!==o.retryLane&&(o.retryLane=i,Oa(e,i),ru(r,e,i,-1))}return mu(),Vl(e,t,l,r=dl(Error(a(421))))}return"$?"===i.data?(t.flags|=128,t.child=e.child,t=ju.bind(null,e),i._reactRetry=t,null):(e=o.treeContext,ra=ui(i.nextSibling),na=t,ia=!0,aa=null,null!==e&&(qi[Qi++]=Xi,qi[Qi++]=Yi,qi[Qi++]=Gi,Xi=e.id,Yi=e.overflow,Gi=t),t=Ul(t,r.children),t.flags|=4096,t)}(e,t,s,i,r,o,n);if(l){l=i.fallback,s=t.mode,r=(o=e.child).sibling;var u={mode:"hidden",children:i.children};return 0==(1&s)&&t.child!==o?((i=t.child).childLanes=0,i.pendingProps=u,t.deletions=null):(i=Iu(o,u)).subtreeFlags=14680064&o.subtreeFlags,null!==r?l=Iu(r,l):(l=Mu(l,s,n,null)).flags|=2,l.return=t,i.return=t,i.sibling=l,t.child=i,i=l,l=t.child,s=null===(s=e.child.memoizedState)?Rl(n):{baseLanes:s.baseLanes|n,cachePool:null,transitions:s.transitions},l.memoizedState=s,l.childLanes=e.childLanes&~n,t.memoizedState=Ml,i}return e=(l=e.child).sibling,i=Iu(l,{mode:"visible",children:i.children}),0==(1&t.mode)&&(i.lanes=n),i.return=t,i.sibling=null,null!==e&&(null===(n=t.deletions)?(t.deletions=[e],t.flags|=16):n.push(e)),t.child=i,t.memoizedState=null,i}function Ul(e,t){return(t=Ru({mode:"visible",children:t},e.mode,0,null)).return=e,e.child=t}function Vl(e,t,n,r){return null!==r&&pa(r),Xa(t,e.child,null,n),(e=Ul(t,t.pendingProps.children)).flags|=2,t.memoizedState=null,e}function Bl(e,t,n){e.lanes|=t;var r=e.alternate;null!==r&&(r.lanes|=t),xa(e.return,t,n)}function Fl(e,t,n,r,i){var a=e.memoizedState;null===a?e.memoizedState={isBackwards:t,rendering:null,renderingStartTime:0,last:r,tail:n,tailMode:i}:(a.isBackwards=t,a.rendering=null,a.renderingStartTime=0,a.last=r,a.tail=n,a.tailMode=i)}function Hl(e,t,n){var r=t.pendingProps,i=r.revealOrder,a=r.tail;if(wl(e,t,r.children,n),0!=(2&(r=lo.current)))r=1&r|2,t.flags|=128;else{if(null!==e&&0!=(128&e.flags))e:for(e=t.child;null!==e;){if(13===e.tag)null!==e.memoizedState&&Bl(e,n,t);else if(19===e.tag)Bl(e,n,t);else if(null!==e.child){e.child.return=e,e=e.child;continue}if(e===t)break e;for(;null===e.sibling;){if(null===e.return||e.return===t)break e;e=e.return}e.sibling.return=e.return,e=e.sibling}r&=1}if(Pi(lo,r),0==(1&t.mode))t.memoizedState=null;else switch(i){case"forwards":for(n=t.child,i=null;null!==n;)null!==(e=n.alternate)&&null===so(e)&&(i=n),n=n.sibling;null===(n=i)?(i=t.child,t.child=null):(i=n.sibling,n.sibling=null),Fl(t,!1,i,n,a);break;case"backwards":for(n=null,i=t.child,t.child=null;null!==i;){if(null!==(e=i.alternate)&&null===so(e)){t.child=i;break}e=i.sibling,i.sibling=n,n=i,i=e}Fl(t,!0,n,null,a);break;case"together":Fl(t,!1,null,null,void 0);break;default:t.memoizedState=null}return t.child}function $l(e,t){0==(1&t.mode)&&null!==e&&(e.alternate=null,t.alternate=null,t.flags|=2)}function Wl(e,t,n){if(null!==e&&(t.dependencies=e.dependencies),Rs|=t.lanes,0==(n&t.childLanes))return null;if(null!==e&&t.child!==e.child)throw Error(a(153));if(null!==t.child){for(n=Iu(e=t.child,e.pendingProps),t.child=n,n.return=t;null!==e.sibling;)e=e.sibling,(n=n.sibling=Iu(e,e.pendingProps)).return=t;n.sibling=null}return t.child}function Kl(e,t){if(!ia)switch(e.tailMode){case"hidden":t=e.tail;for(var n=null;null!==t;)null!==t.alternate&&(n=t),t=t.sibling;null===n?e.tail=null:n.sibling=null;break;case"collapsed":n=e.tail;for(var r=null;null!==n;)null!==n.alternate&&(r=n),n=n.sibling;null===r?t||null===e.tail?e.tail=null:e.tail.sibling=null:r.sibling=null}}function ql(e){var t=null!==e.alternate&&e.alternate.child===e.child,n=0,r=0;if(t)for(var i=e.child;null!==i;)n|=i.lanes|i.childLanes,r|=14680064&i.subtreeFlags,r|=14680064&i.flags,i.return=e,i=i.sibling;else for(i=e.child;null!==i;)n|=i.lanes|i.childLanes,r|=i.subtreeFlags,r|=i.flags,i.return=e,i=i.sibling;return e.subtreeFlags|=r,e.childLanes=n,t}function Ql(e,t,n){var r=t.pendingProps;switch(ta(t),t.tag){case 2:case 16:case 15:case 0:case 11:case 7:case 8:case 12:case 9:case 14:return ql(t),null;case 1:case 17:return Ti(t.type)&&Li(),ql(t),null;case 3:return r=t.stateNode,io(),zi(Oi),zi(ji),co(),r.pendingContext&&(r.context=r.pendingContext,r.pendingContext=null),null!==e&&null!==e.child||(da(t)?t.flags|=4:null===e||e.memoizedState.isDehydrated&&0==(256&t.flags)||(t.flags|=1024,null!==aa&&(lu(aa),aa=null))),Ll(e,t),ql(t),null;case 5:oo(t);var i=no(to.current);if(n=t.type,null!==e&&null!=t.stateNode)Il(e,t,n,r,i),e.ref!==t.ref&&(t.flags|=512,t.flags|=2097152);else{if(!r){if(null===t.stateNode)throw Error(a(166));return ql(t),null}if(e=no(Za.current),da(t)){r=t.stateNode,n=t.type;var o=t.memoizedProps;switch(r[fi]=t,r[hi]=o,e=0!=(1&t.mode),n){case"dialog":Ur("cancel",r),Ur("close",r);break;case"iframe":case"object":case"embed":Ur("load",r);break;case"video":case"audio":for(i=0;i<Ar.length;i++)Ur(Ar[i],r);break;case"source":Ur("error",r);break;case"img":case"image":case"link":Ur("error",r),Ur("load",r);break;case"details":Ur("toggle",r);break;case"input":X(r,o),Ur("invalid",r);break;case"select":r._wrapperState={wasMultiple:!!o.multiple},Ur("invalid",r);break;case"textarea":ie(r,o),Ur("invalid",r)}for(var s in ye(n,o),i=null,o)if(o.hasOwnProperty(s)){var u=o[s];"children"===s?"string"==typeof u?r.textContent!==u&&(!0!==o.suppressHydrationWarning&&Jr(r.textContent,u,e),i=["children",u]):"number"==typeof u&&r.textContent!==""+u&&(!0!==o.suppressHydrationWarning&&Jr(r.textContent,u,e),i=["children",""+u]):l.hasOwnProperty(s)&&null!=u&&"onScroll"===s&&Ur("scroll",r)}switch(n){case"input":K(r),Z(r,o,!0);break;case"textarea":K(r),oe(r);break;case"select":case"option":break;default:"function"==typeof o.onClick&&(r.onclick=Zr)}r=i,t.updateQueue=r,null!==r&&(t.flags|=4)}else{s=9===i.nodeType?i:i.ownerDocument,"http://www.w3.org/1999/xhtml"===e&&(e=le(n)),"http://www.w3.org/1999/xhtml"===e?"script"===n?((e=s.createElement("div")).innerHTML="<script><\/script>",e=e.removeChild(e.firstChild)):"string"==typeof r.is?e=s.createElement(n,{is:r.is}):(e=s.createElement(n),"select"===n&&(s=e,r.multiple?s.multiple=!0:r.size&&(s.size=r.size))):e=s.createElementNS(e,n),e[fi]=t,e[hi]=r,Tl(e,t,!1,!1),t.stateNode=e;e:{switch(s=be(n,r),n){case"dialog":Ur("cancel",e),Ur("close",e),i=r;break;case"iframe":case"object":case"embed":Ur("load",e),i=r;break;case"video":case"audio":for(i=0;i<Ar.length;i++)Ur(Ar[i],e);i=r;break;case"source":Ur("error",e),i=r;break;case"img":case"image":case"link":Ur("error",e),Ur("load",e),i=r;break;case"details":Ur("toggle",e),i=r;break;case"input":X(e,r),i=G(e,r),Ur("invalid",e);break;case"option":default:i=r;break;case"select":e._wrapperState={wasMultiple:!!r.multiple},i=R({},r,{value:void 0}),Ur("invalid",e);break;case"textarea":ie(e,r),i=re(e,r),Ur("invalid",e)}for(o in ye(n,i),u=i)if(u.hasOwnProperty(o)){var c=u[o];"style"===o?me(e,c):"dangerouslySetInnerHTML"===o?null!=(c=c?c.__html:void 0)&&de(e,c):"children"===o?"string"==typeof c?("textarea"!==n||""!==c)&&fe(e,c):"number"==typeof c&&fe(e,""+c):"suppressContentEditableWarning"!==o&&"suppressHydrationWarning"!==o&&"autoFocus"!==o&&(l.hasOwnProperty(o)?null!=c&&"onScroll"===o&&Ur("scroll",e):null!=c&&b(e,o,c,s))}switch(n){case"input":K(e),Z(e,r,!1);break;case"textarea":K(e),oe(e);break;case"option":null!=r.value&&e.setAttribute("value",""+$(r.value));break;case"select":e.multiple=!!r.multiple,null!=(o=r.value)?ne(e,!!r.multiple,o,!1):null!=r.defaultValue&&ne(e,!!r.multiple,r.defaultValue,!0);break;default:"function"==typeof i.onClick&&(e.onclick=Zr)}switch(n){case"button":case"input":case"select":case"textarea":r=!!r.autoFocus;break e;case"img":r=!0;break e;default:r=!1}}r&&(t.flags|=4)}null!==t.ref&&(t.flags|=512,t.flags|=2097152)}return ql(t),null;case 6:if(e&&null!=t.stateNode)Al(e,t,e.memoizedProps,r);else{if("string"!=typeof r&&null===t.stateNode)throw Error(a(166));if(n=no(to.current),no(Za.current),da(t)){if(r=t.stateNode,n=t.memoizedProps,r[fi]=t,(o=r.nodeValue!==n)&&null!==(e=na))switch(e.tag){case 3:Jr(r.nodeValue,n,0!=(1&e.mode));break;case 5:!0!==e.memoizedProps.suppressHydrationWarning&&Jr(r.nodeValue,n,0!=(1&e.mode))}o&&(t.flags|=4)}else(r=(9===n.nodeType?n:n.ownerDocument).createTextNode(r))[fi]=t,t.stateNode=r}return ql(t),null;case 13:if(zi(lo),r=t.memoizedState,null===e||null!==e.memoizedState&&null!==e.memoizedState.dehydrated){if(ia&&null!==ra&&0!=(1&t.mode)&&0==(128&t.flags))fa(),ha(),t.flags|=98560,o=!1;else if(o=da(t),null!==r&&null!==r.dehydrated){if(null===e){if(!o)throw Error(a(318));if(!(o=null!==(o=t.memoizedState)?o.dehydrated:null))throw Error(a(317));o[fi]=t}else ha(),0==(128&t.flags)&&(t.memoizedState=null),t.flags|=4;ql(t),o=!1}else null!==aa&&(lu(aa),aa=null),o=!0;if(!o)return 65536&t.flags?t:null}return 0!=(128&t.flags)?(t.lanes=n,t):((r=null!==r)!==(null!==e&&null!==e.memoizedState)&&r&&(t.child.flags|=8192,0!=(1&t.mode)&&(null===e||0!=(1&lo.current)?0===As&&(As=3):mu())),null!==t.updateQueue&&(t.flags|=4),ql(t),null);case 4:return io(),Ll(e,t),null===e&&Fr(t.stateNode.containerInfo),ql(t),null;case 10:return ka(t.type._context),ql(t),null;case 19:if(zi(lo),null===(o=t.memoizedState))return ql(t),null;if(r=0!=(128&t.flags),null===(s=o.rendering))if(r)Kl(o,!1);else{if(0!==As||null!==e&&0!=(128&e.flags))for(e=t.child;null!==e;){if(null!==(s=so(e))){for(t.flags|=128,Kl(o,!1),null!==(r=s.updateQueue)&&(t.updateQueue=r,t.flags|=4),t.subtreeFlags=0,r=n,n=t.child;null!==n;)e=r,(o=n).flags&=14680066,null===(s=o.alternate)?(o.childLanes=0,o.lanes=e,o.child=null,o.subtreeFlags=0,o.memoizedProps=null,o.memoizedState=null,o.updateQueue=null,o.dependencies=null,o.stateNode=null):(o.childLanes=s.childLanes,o.lanes=s.lanes,o.child=s.child,o.subtreeFlags=0,o.deletions=null,o.memoizedProps=s.memoizedProps,o.memoizedState=s.memoizedState,o.updateQueue=s.updateQueue,o.type=s.type,e=s.dependencies,o.dependencies=null===e?null:{lanes:e.lanes,firstContext:e.firstContext}),n=n.sibling;return Pi(lo,1&lo.current|2),t.child}e=e.sibling}null!==o.tail&&Ye()>Hs&&(t.flags|=128,r=!0,Kl(o,!1),t.lanes=4194304)}else{if(!r)if(null!==(e=so(s))){if(t.flags|=128,r=!0,null!==(n=e.updateQueue)&&(t.updateQueue=n,t.flags|=4),Kl(o,!0),null===o.tail&&"hidden"===o.tailMode&&!s.alternate&&!ia)return ql(t),null}else 2*Ye()-o.renderingStartTime>Hs&&1073741824!==n&&(t.flags|=128,r=!0,Kl(o,!1),t.lanes=4194304);o.isBackwards?(s.sibling=t.child,t.child=s):(null!==(n=o.last)?n.sibling=s:t.child=s,o.last=s)}return null!==o.tail?(t=o.tail,o.rendering=t,o.tail=t.sibling,o.renderingStartTime=Ye(),t.sibling=null,n=lo.current,Pi(lo,r?1&n|2:1&n),t):(ql(t),null);case 22:case 23:return fu(),r=null!==t.memoizedState,null!==e&&null!==e.memoizedState!==r&&(t.flags|=8192),r&&0!=(1&t.mode)?0!=(1073741824&Ls)&&(ql(t),6&t.subtreeFlags&&(t.flags|=8192)):ql(t),null;case 24:case 25:return null}throw Error(a(156,t.tag))}function Gl(e,t){switch(ta(t),t.tag){case 1:return Ti(t.type)&&Li(),65536&(e=t.flags)?(t.flags=-65537&e|128,t):null;case 3:return io(),zi(Oi),zi(ji),co(),0!=(65536&(e=t.flags))&&0==(128&e)?(t.flags=-65537&e|128,t):null;case 5:return oo(t),null;case 13:if(zi(lo),null!==(e=t.memoizedState)&&null!==e.dehydrated){if(null===t.alternate)throw Error(a(340));ha()}return 65536&(e=t.flags)?(t.flags=-65537&e|128,t):null;case 19:return zi(lo),null;case 4:return io(),null;case 10:return ka(t.type._context),null;case 22:case 23:return fu(),null;default:return null}}Tl=function(e,t){for(var n=t.child;null!==n;){if(5===n.tag||6===n.tag)e.appendChild(n.stateNode);else if(4!==n.tag&&null!==n.child){n.child.return=n,n=n.child;continue}if(n===t)break;for(;null===n.sibling;){if(null===n.return||n.return===t)return;n=n.return}n.sibling.return=n.return,n=n.sibling}},Ll=function(){},Il=function(e,t,n,r){var i=e.memoizedProps;if(i!==r){e=t.stateNode,no(Za.current);var a,o=null;switch(n){case"input":i=G(e,i),r=G(e,r),o=[];break;case"select":i=R({},i,{value:void 0}),r=R({},r,{value:void 0}),o=[];break;case"textarea":i=re(e,i),r=re(e,r),o=[];break;default:"function"!=typeof i.onClick&&"function"==typeof r.onClick&&(e.onclick=Zr)}for(c in ye(n,r),n=null,i)if(!r.hasOwnProperty(c)&&i.hasOwnProperty(c)&&null!=i[c])if("style"===c){var s=i[c];for(a in s)s.hasOwnProperty(a)&&(n||(n={}),n[a]="")}else"dangerouslySetInnerHTML"!==c&&"children"!==c&&"suppressContentEditableWarning"!==c&&"suppressHydrationWarning"!==c&&"autoFocus"!==c&&(l.hasOwnProperty(c)?o||(o=[]):(o=o||[]).push(c,null));for(c in r){var u=r[c];if(s=null!=i?i[c]:void 0,r.hasOwnProperty(c)&&u!==s&&(null!=u||null!=s))if("style"===c)if(s){for(a in s)!s.hasOwnProperty(a)||u&&u.hasOwnProperty(a)||(n||(n={}),n[a]="");for(a in u)u.hasOwnProperty(a)&&s[a]!==u[a]&&(n||(n={}),n[a]=u[a])}else n||(o||(o=[]),o.push(c,n)),n=u;else"dangerouslySetInnerHTML"===c?(u=u?u.__html:void 0,s=s?s.__html:void 0,null!=u&&s!==u&&(o=o||[]).push(c,u)):"children"===c?"string"!=typeof u&&"number"!=typeof u||(o=o||[]).push(c,""+u):"suppressContentEditableWarning"!==c&&"suppressHydrationWarning"!==c&&(l.hasOwnProperty(c)?(null!=u&&"onScroll"===c&&Ur("scroll",e),o||s===u||(o=[])):(o=o||[]).push(c,u))}n&&(o=o||[]).push("style",n);var c=o;(t.updateQueue=c)&&(t.flags|=4)}},Al=function(e,t,n,r){n!==r&&(t.flags|=4)};var Xl=!1,Yl=!1,Jl="function"==typeof WeakSet?WeakSet:Set,Zl=null;function es(e,t){var n=e.ref;if(null!==n)if("function"==typeof n)try{n(null)}catch(n){zu(e,t,n)}else n.current=null}function ts(e,t,n){try{n()}catch(n){zu(e,t,n)}}var ns=!1;function rs(e,t,n){var r=t.updateQueue;if(null!==(r=null!==r?r.lastEffect:null)){var i=r=r.next;do{if((i.tag&e)===e){var a=i.destroy;i.destroy=void 0,void 0!==a&&ts(t,n,a)}i=i.next}while(i!==r)}}function is(e,t){if(null!==(t=null!==(t=t.updateQueue)?t.lastEffect:null)){var n=t=t.next;do{if((n.tag&e)===e){var r=n.create;n.destroy=r()}n=n.next}while(n!==t)}}function as(e){var t=e.ref;if(null!==t){var n=e.stateNode;e.tag,e=n,"function"==typeof t?t(e):t.current=e}}function os(e){var t=e.alternate;null!==t&&(e.alternate=null,os(t)),e.child=null,e.deletions=null,e.sibling=null,5===e.tag&&(null!==(t=e.stateNode)&&(delete t[fi],delete t[hi],delete t[vi],delete t[mi],delete t[gi])),e.stateNode=null,e.return=null,e.dependencies=null,e.memoizedProps=null,e.memoizedState=null,e.pendingProps=null,e.stateNode=null,e.updateQueue=null}function ls(e){return 5===e.tag||3===e.tag||4===e.tag}function ss(e){e:for(;;){for(;null===e.sibling;){if(null===e.return||ls(e.return))return null;e=e.return}for(e.sibling.return=e.return,e=e.sibling;5!==e.tag&&6!==e.tag&&18!==e.tag;){if(2&e.flags)continue e;if(null===e.child||4===e.tag)continue e;e.child.return=e,e=e.child}if(!(2&e.flags))return e.stateNode}}function us(e,t,n){var r=e.tag;if(5===r||6===r)e=e.stateNode,t?8===n.nodeType?n.parentNode.insertBefore(e,t):n.insertBefore(e,t):(8===n.nodeType?(t=n.parentNode).insertBefore(e,n):(t=n).appendChild(e),null!=(n=n._reactRootContainer)||null!==t.onclick||(t.onclick=Zr));else if(4!==r&&null!==(e=e.child))for(us(e,t,n),e=e.sibling;null!==e;)us(e,t,n),e=e.sibling}function cs(e,t,n){var r=e.tag;if(5===r||6===r)e=e.stateNode,t?n.insertBefore(e,t):n.appendChild(e);else if(4!==r&&null!==(e=e.child))for(cs(e,t,n),e=e.sibling;null!==e;)cs(e,t,n),e=e.sibling}var ds=null,fs=!1;function hs(e,t,n){for(n=n.child;null!==n;)ps(e,t,n),n=n.sibling}function ps(e,t,n){if(at&&"function"==typeof at.onCommitFiberUnmount)try{at.onCommitFiberUnmount(it,n)}catch(e){}switch(n.tag){case 5:Yl||es(n,t);case 6:var r=ds,i=fs;ds=null,hs(e,t,n),fs=i,null!==(ds=r)&&(fs?(e=ds,n=n.stateNode,8===e.nodeType?e.parentNode.removeChild(n):e.removeChild(n)):ds.removeChild(n.stateNode));break;case 18:null!==ds&&(fs?(e=ds,n=n.stateNode,8===e.nodeType?si(e.parentNode,n):1===e.nodeType&&si(e,n),Ft(e)):si(ds,n.stateNode));break;case 4:r=ds,i=fs,ds=n.stateNode.containerInfo,fs=!0,hs(e,t,n),ds=r,fs=i;break;case 0:case 11:case 14:case 15:if(!Yl&&(null!==(r=n.updateQueue)&&null!==(r=r.lastEffect))){i=r=r.next;do{var a=i,o=a.destroy;a=a.tag,void 0!==o&&(0!=(2&a)||0!=(4&a))&&ts(n,t,o),i=i.next}while(i!==r)}hs(e,t,n);break;case 1:if(!Yl&&(es(n,t),"function"==typeof(r=n.stateNode).componentWillUnmount))try{r.props=n.memoizedProps,r.state=n.memoizedState,r.componentWillUnmount()}catch(e){zu(n,t,e)}hs(e,t,n);break;case 21:hs(e,t,n);break;case 22:1&n.mode?(Yl=(r=Yl)||null!==n.memoizedState,hs(e,t,n),Yl=r):hs(e,t,n);break;default:hs(e,t,n)}}function vs(e){var t=e.updateQueue;if(null!==t){e.updateQueue=null;var n=e.stateNode;null===n&&(n=e.stateNode=new Jl),t.forEach((function(t){var r=Ou.bind(null,e,t);n.has(t)||(n.add(t),t.then(r,r))}))}}function ms(e,t){var n=t.deletions;if(null!==n)for(var r=0;r<n.length;r++){var i=n[r];try{var o=e,l=t,s=l;e:for(;null!==s;){switch(s.tag){case 5:ds=s.stateNode,fs=!1;break e;case 3:case 4:ds=s.stateNode.containerInfo,fs=!0;break e}s=s.return}if(null===ds)throw Error(a(160));ps(o,l,i),ds=null,fs=!1;var u=i.alternate;null!==u&&(u.return=null),i.return=null}catch(e){zu(i,t,e)}}if(12854&t.subtreeFlags)for(t=t.child;null!==t;)gs(t,e),t=t.sibling}function gs(e,t){var n=e.alternate,r=e.flags;switch(e.tag){case 0:case 11:case 14:case 15:if(ms(t,e),ys(e),4&r){try{rs(3,e,e.return),is(3,e)}catch(t){zu(e,e.return,t)}try{rs(5,e,e.return)}catch(t){zu(e,e.return,t)}}break;case 1:ms(t,e),ys(e),512&r&&null!==n&&es(n,n.return);break;case 5:if(ms(t,e),ys(e),512&r&&null!==n&&es(n,n.return),32&e.flags){var i=e.stateNode;try{fe(i,"")}catch(t){zu(e,e.return,t)}}if(4&r&&null!=(i=e.stateNode)){var o=e.memoizedProps,l=null!==n?n.memoizedProps:o,s=e.type,u=e.updateQueue;if(e.updateQueue=null,null!==u)try{"input"===s&&"radio"===o.type&&null!=o.name&&Y(i,o),be(s,l);var c=be(s,o);for(l=0;l<u.length;l+=2){var d=u[l],f=u[l+1];"style"===d?me(i,f):"dangerouslySetInnerHTML"===d?de(i,f):"children"===d?fe(i,f):b(i,d,f,c)}switch(s){case"input":J(i,o);break;case"textarea":ae(i,o);break;case"select":var h=i._wrapperState.wasMultiple;i._wrapperState.wasMultiple=!!o.multiple;var p=o.value;null!=p?ne(i,!!o.multiple,p,!1):h!==!!o.multiple&&(null!=o.defaultValue?ne(i,!!o.multiple,o.defaultValue,!0):ne(i,!!o.multiple,o.multiple?[]:"",!1))}i[hi]=o}catch(t){zu(e,e.return,t)}}break;case 6:if(ms(t,e),ys(e),4&r){if(null===e.stateNode)throw Error(a(162));i=e.stateNode,o=e.memoizedProps;try{i.nodeValue=o}catch(t){zu(e,e.return,t)}}break;case 3:if(ms(t,e),ys(e),4&r&&null!==n&&n.memoizedState.isDehydrated)try{Ft(t.containerInfo)}catch(t){zu(e,e.return,t)}break;case 4:default:ms(t,e),ys(e);break;case 13:ms(t,e),ys(e),8192&(i=e.child).flags&&(o=null!==i.memoizedState,i.stateNode.isHidden=o,!o||null!==i.alternate&&null!==i.alternate.memoizedState||(Fs=Ye())),4&r&&vs(e);break;case 22:if(d=null!==n&&null!==n.memoizedState,1&e.mode?(Yl=(c=Yl)||d,ms(t,e),Yl=c):ms(t,e),ys(e),8192&r){if(c=null!==e.memoizedState,(e.stateNode.isHidden=c)&&!d&&0!=(1&e.mode))for(Zl=e,d=e.child;null!==d;){for(f=Zl=d;null!==Zl;){switch(p=(h=Zl).child,h.tag){case 0:case 11:case 14:case 15:rs(4,h,h.return);break;case 1:es(h,h.return);var v=h.stateNode;if("function"==typeof v.componentWillUnmount){r=h,n=h.return;try{t=r,v.props=t.memoizedProps,v.state=t.memoizedState,v.componentWillUnmount()}catch(e){zu(r,n,e)}}break;case 5:es(h,h.return);break;case 22:if(null!==h.memoizedState){ks(f);continue}}null!==p?(p.return=h,Zl=p):ks(f)}d=d.sibling}e:for(d=null,f=e;;){if(5===f.tag){if(null===d){d=f;try{i=f.stateNode,c?"function"==typeof(o=i.style).setProperty?o.setProperty("display","none","important"):o.display="none":(s=f.stateNode,l=null!=(u=f.memoizedProps.style)&&u.hasOwnProperty("display")?u.display:null,s.style.display=ve("display",l))}catch(t){zu(e,e.return,t)}}}else if(6===f.tag){if(null===d)try{f.stateNode.nodeValue=c?"":f.memoizedProps}catch(t){zu(e,e.return,t)}}else if((22!==f.tag&&23!==f.tag||null===f.memoizedState||f===e)&&null!==f.child){f.child.return=f,f=f.child;continue}if(f===e)break e;for(;null===f.sibling;){if(null===f.return||f.return===e)break e;d===f&&(d=null),f=f.return}d===f&&(d=null),f.sibling.return=f.return,f=f.sibling}}break;case 19:ms(t,e),ys(e),4&r&&vs(e);case 21:}}function ys(e){var t=e.flags;if(2&t){try{e:{for(var n=e.return;null!==n;){if(ls(n)){var r=n;break e}n=n.return}throw Error(a(160))}switch(r.tag){case 5:var i=r.stateNode;32&r.flags&&(fe(i,""),r.flags&=-33),cs(e,ss(e),i);break;case 3:case 4:var o=r.stateNode.containerInfo;us(e,ss(e),o);break;default:throw Error(a(161))}}catch(t){zu(e,e.return,t)}e.flags&=-3}4096&t&&(e.flags&=-4097)}function bs(e,t,n){Zl=e,_s(e,t,n)}function _s(e,t,n){for(var r=0!=(1&e.mode);null!==Zl;){var i=Zl,a=i.child;if(22===i.tag&&r){var o=null!==i.memoizedState||Xl;if(!o){var l=i.alternate,s=null!==l&&null!==l.memoizedState||Yl;l=Xl;var u=Yl;if(Xl=o,(Yl=s)&&!u)for(Zl=i;null!==Zl;)s=(o=Zl).child,22===o.tag&&null!==o.memoizedState?xs(i):null!==s?(s.return=o,Zl=s):xs(i);for(;null!==a;)Zl=a,_s(a,t,n),a=a.sibling;Zl=i,Xl=l,Yl=u}ws(e)}else 0!=(8772&i.subtreeFlags)&&null!==a?(a.return=i,Zl=a):ws(e)}}function ws(e){for(;null!==Zl;){var t=Zl;if(0!=(8772&t.flags)){var n=t.alternate;try{if(0!=(8772&t.flags))switch(t.tag){case 0:case 11:case 15:Yl||is(5,t);break;case 1:var r=t.stateNode;if(4&t.flags&&!Yl)if(null===n)r.componentDidMount();else{var i=t.elementType===t.type?n.memoizedProps:ma(t.type,n.memoizedProps);r.componentDidUpdate(i,n.memoizedState,r.__reactInternalSnapshotBeforeUpdate)}var o=t.updateQueue;null!==o&&Da(t,o,r);break;case 3:var l=t.updateQueue;if(null!==l){if(n=null,null!==t.child)switch(t.child.tag){case 5:case 1:n=t.child.stateNode}Da(t,l,n)}break;case 5:var s=t.stateNode;if(null===n&&4&t.flags){n=s;var u=t.memoizedProps;switch(t.type){case"button":case"input":case"select":case"textarea":u.autoFocus&&n.focus();break;case"img":u.src&&(n.src=u.src)}}break;case 6:case 4:case 12:case 19:case 17:case 21:case 22:case 23:case 25:break;case 13:if(null===t.memoizedState){var c=t.alternate;if(null!==c){var d=c.memoizedState;if(null!==d){var f=d.dehydrated;null!==f&&Ft(f)}}}break;default:throw Error(a(163))}Yl||512&t.flags&&as(t)}catch(e){zu(t,t.return,e)}}if(t===e){Zl=null;break}if(null!==(n=t.sibling)){n.return=t.return,Zl=n;break}Zl=t.return}}function ks(e){for(;null!==Zl;){var t=Zl;if(t===e){Zl=null;break}var n=t.sibling;if(null!==n){n.return=t.return,Zl=n;break}Zl=t.return}}function xs(e){for(;null!==Zl;){var t=Zl;try{switch(t.tag){case 0:case 11:case 15:var n=t.return;try{is(4,t)}catch(e){zu(t,n,e)}break;case 1:var r=t.stateNode;if("function"==typeof r.componentDidMount){var i=t.return;try{r.componentDidMount()}catch(e){zu(t,i,e)}}var a=t.return;try{as(t)}catch(e){zu(t,a,e)}break;case 5:var o=t.return;try{as(t)}catch(e){zu(t,o,e)}}}catch(e){zu(t,t.return,e)}if(t===e){Zl=null;break}var l=t.sibling;if(null!==l){l.return=t.return,Zl=l;break}Zl=t.return}}var Ss,zs=Math.ceil,Ps=_.ReactCurrentDispatcher,Cs=_.ReactCurrentOwner,js=_.ReactCurrentBatchConfig,Os=0,Es=null,Ns=null,Ts=0,Ls=0,Is=Si(0),As=0,Ms=null,Rs=0,Ds=0,Us=0,Vs=null,Bs=null,Fs=0,Hs=1/0,$s=null,Ws=!1,Ks=null,qs=null,Qs=!1,Gs=null,Xs=0,Ys=0,Js=null,Zs=-1,eu=0;function tu(){return 0!=(6&Os)?Ye():-1!==Zs?Zs:Zs=Ye()}function nu(e){return 0==(1&e.mode)?1:0!=(2&Os)&&0!==Ts?Ts&-Ts:null!==va.transition?(0===eu&&(eu=vt()),eu):0!==(e=bt)?e:e=void 0===(e=window.event)?16:Xt(e.type)}function ru(e,t,n,r){if(50<Ys)throw Ys=0,Js=null,Error(a(185));gt(e,n,r),0!=(2&Os)&&e===Es||(e===Es&&(0==(2&Os)&&(Ds|=n),4===As&&su(e,Ts)),iu(e,r),1===n&&0===Os&&0==(1&t.mode)&&(Hs=Ye()+500,Ui&&Fi()))}function iu(e,t){var n=e.callbackNode;!function(e,t){for(var n=e.suspendedLanes,r=e.pingedLanes,i=e.expirationTimes,a=e.pendingLanes;0<a;){var o=31-ot(a),l=1<<o,s=i[o];-1===s?0!=(l&n)&&0==(l&r)||(i[o]=ht(l,t)):s<=t&&(e.expiredLanes|=l),a&=~l}}(e,t);var r=ft(e,e===Es?Ts:0);if(0===r)null!==n&&Qe(n),e.callbackNode=null,e.callbackPriority=0;else if(t=r&-r,e.callbackPriority!==t){if(null!=n&&Qe(n),1===t)0===e.tag?function(e){Ui=!0,Bi(e)}(uu.bind(null,e)):Bi(uu.bind(null,e)),oi((function(){0==(6&Os)&&Fi()})),n=null;else{switch(_t(r)){case 1:n=Ze;break;case 4:n=et;break;case 16:default:n=tt;break;case 536870912:n=rt}n=Eu(n,au.bind(null,e))}e.callbackPriority=t,e.callbackNode=n}}function au(e,t){if(Zs=-1,eu=0,0!=(6&Os))throw Error(a(327));var n=e.callbackNode;if(xu()&&e.callbackNode!==n)return null;var r=ft(e,e===Es?Ts:0);if(0===r)return null;if(0!=(30&r)||0!=(r&e.expiredLanes)||t)t=gu(e,r);else{t=r;var i=Os;Os|=2;var o=vu();for(Es===e&&Ts===t||($s=null,Hs=Ye()+500,hu(e,t));;)try{bu();break}catch(t){pu(e,t)}wa(),Ps.current=o,Os=i,null!==Ns?t=0:(Es=null,Ts=0,t=As)}if(0!==t){if(2===t&&(0!==(i=pt(e))&&(r=i,t=ou(e,i))),1===t)throw n=Ms,hu(e,0),su(e,r),iu(e,Ye()),n;if(6===t)su(e,r);else{if(i=e.current.alternate,0==(30&r)&&!function(e){for(var t=e;;){if(16384&t.flags){var n=t.updateQueue;if(null!==n&&null!==(n=n.stores))for(var r=0;r<n.length;r++){var i=n[r],a=i.getSnapshot;i=i.value;try{if(!lr(a(),i))return!1}catch(e){return!1}}}if(n=t.child,16384&t.subtreeFlags&&null!==n)n.return=t,t=n;else{if(t===e)break;for(;null===t.sibling;){if(null===t.return||t.return===e)return!0;t=t.return}t.sibling.return=t.return,t=t.sibling}}return!0}(i)&&(2===(t=gu(e,r))&&(0!==(o=pt(e))&&(r=o,t=ou(e,o))),1===t))throw n=Ms,hu(e,0),su(e,r),iu(e,Ye()),n;switch(e.finishedWork=i,e.finishedLanes=r,t){case 0:case 1:throw Error(a(345));case 2:case 5:ku(e,Bs,$s);break;case 3:if(su(e,r),(130023424&r)===r&&10<(t=Fs+500-Ye())){if(0!==ft(e,0))break;if(((i=e.suspendedLanes)&r)!==r){tu(),e.pingedLanes|=e.suspendedLanes&i;break}e.timeoutHandle=ri(ku.bind(null,e,Bs,$s),t);break}ku(e,Bs,$s);break;case 4:if(su(e,r),(4194240&r)===r)break;for(t=e.eventTimes,i=-1;0<r;){var l=31-ot(r);o=1<<l,(l=t[l])>i&&(i=l),r&=~o}if(r=i,10<(r=(120>(r=Ye()-r)?120:480>r?480:1080>r?1080:1920>r?1920:3e3>r?3e3:4320>r?4320:1960*zs(r/1960))-r)){e.timeoutHandle=ri(ku.bind(null,e,Bs,$s),r);break}ku(e,Bs,$s);break;default:throw Error(a(329))}}}return iu(e,Ye()),e.callbackNode===n?au.bind(null,e):null}function ou(e,t){var n=Vs;return e.current.memoizedState.isDehydrated&&(hu(e,t).flags|=256),2!==(e=gu(e,t))&&(t=Bs,Bs=n,null!==t&&lu(t)),e}function lu(e){null===Bs?Bs=e:Bs.push.apply(Bs,e)}function su(e,t){for(t&=~Us,t&=~Ds,e.suspendedLanes|=t,e.pingedLanes&=~t,e=e.expirationTimes;0<t;){var n=31-ot(t),r=1<<n;e[n]=-1,t&=~r}}function uu(e){if(0!=(6&Os))throw Error(a(327));xu();var t=ft(e,0);if(0==(1&t))return iu(e,Ye()),null;var n=gu(e,t);if(0!==e.tag&&2===n){var r=pt(e);0!==r&&(t=r,n=ou(e,r))}if(1===n)throw n=Ms,hu(e,0),su(e,t),iu(e,Ye()),n;if(6===n)throw Error(a(345));return e.finishedWork=e.current.alternate,e.finishedLanes=t,ku(e,Bs,$s),iu(e,Ye()),null}function cu(e,t){var n=Os;Os|=1;try{return e(t)}finally{0===(Os=n)&&(Hs=Ye()+500,Ui&&Fi())}}function du(e){null!==Gs&&0===Gs.tag&&0==(6&Os)&&xu();var t=Os;Os|=1;var n=js.transition,r=bt;try{if(js.transition=null,bt=1,e)return e()}finally{bt=r,js.transition=n,0==(6&(Os=t))&&Fi()}}function fu(){Ls=Is.current,zi(Is)}function hu(e,t){e.finishedWork=null,e.finishedLanes=0;var n=e.timeoutHandle;if(-1!==n&&(e.timeoutHandle=-1,ii(n)),null!==Ns)for(n=Ns.return;null!==n;){var r=n;switch(ta(r),r.tag){case 1:null!=(r=r.type.childContextTypes)&&Li();break;case 3:io(),zi(Oi),zi(ji),co();break;case 5:oo(r);break;case 4:io();break;case 13:case 19:zi(lo);break;case 10:ka(r.type._context);break;case 22:case 23:fu()}n=n.return}if(Es=e,Ns=e=Iu(e.current,null),Ts=Ls=t,As=0,Ms=null,Us=Ds=Rs=0,Bs=Vs=null,null!==Pa){for(t=0;t<Pa.length;t++)if(null!==(r=(n=Pa[t]).interleaved)){n.interleaved=null;var i=r.next,a=n.pending;if(null!==a){var o=a.next;a.next=i,r.next=o}n.pending=r}Pa=null}return e}function pu(e,t){for(;;){var n=Ns;try{if(wa(),fo.current=ol,yo){for(var r=vo.memoizedState;null!==r;){var i=r.queue;null!==i&&(i.pending=null),r=r.next}yo=!1}if(po=0,go=mo=vo=null,bo=!1,_o=0,Cs.current=null,null===n||null===n.return){As=1,Ms=t,Ns=null;break}e:{var o=e,l=n.return,s=n,u=t;if(t=Ts,s.flags|=32768,null!==u&&"object"==typeof u&&"function"==typeof u.then){var c=u,d=s,f=d.tag;if(0==(1&d.mode)&&(0===f||11===f||15===f)){var h=d.alternate;h?(d.updateQueue=h.updateQueue,d.memoizedState=h.memoizedState,d.lanes=h.lanes):(d.updateQueue=null,d.memoizedState=null)}var p=gl(l);if(null!==p){p.flags&=-257,yl(p,l,s,0,t),1&p.mode&&ml(o,c,t),u=c;var v=(t=p).updateQueue;if(null===v){var m=new Set;m.add(u),t.updateQueue=m}else v.add(u);break e}if(0==(1&t)){ml(o,c,t),mu();break e}u=Error(a(426))}else if(ia&&1&s.mode){var g=gl(l);if(null!==g){0==(65536&g.flags)&&(g.flags|=256),yl(g,l,s,0,t),pa(cl(u,s));break e}}o=u=cl(u,s),4!==As&&(As=2),null===Vs?Vs=[o]:Vs.push(o),o=l;do{switch(o.tag){case 3:o.flags|=65536,t&=-t,o.lanes|=t,Ma(o,pl(0,u,t));break e;case 1:s=u;var y=o.type,b=o.stateNode;if(0==(128&o.flags)&&("function"==typeof y.getDerivedStateFromError||null!==b&&"function"==typeof b.componentDidCatch&&(null===qs||!qs.has(b)))){o.flags|=65536,t&=-t,o.lanes|=t,Ma(o,vl(o,s,t));break e}}o=o.return}while(null!==o)}wu(n)}catch(e){t=e,Ns===n&&null!==n&&(Ns=n=n.return);continue}break}}function vu(){var e=Ps.current;return Ps.current=ol,null===e?ol:e}function mu(){0!==As&&3!==As&&2!==As||(As=4),null===Es||0==(268435455&Rs)&&0==(268435455&Ds)||su(Es,Ts)}function gu(e,t){var n=Os;Os|=2;var r=vu();for(Es===e&&Ts===t||($s=null,hu(e,t));;)try{yu();break}catch(t){pu(e,t)}if(wa(),Os=n,Ps.current=r,null!==Ns)throw Error(a(261));return Es=null,Ts=0,As}function yu(){for(;null!==Ns;)_u(Ns)}function bu(){for(;null!==Ns&&!Ge();)_u(Ns)}function _u(e){var t=Ss(e.alternate,e,Ls);e.memoizedProps=e.pendingProps,null===t?wu(e):Ns=t,Cs.current=null}function wu(e){var t=e;do{var n=t.alternate;if(e=t.return,0==(32768&t.flags)){if(null!==(n=Ql(n,t,Ls)))return void(Ns=n)}else{if(null!==(n=Gl(n,t)))return n.flags&=32767,void(Ns=n);if(null===e)return As=6,void(Ns=null);e.flags|=32768,e.subtreeFlags=0,e.deletions=null}if(null!==(t=t.sibling))return void(Ns=t);Ns=t=e}while(null!==t);0===As&&(As=5)}function ku(e,t,n){var r=bt,i=js.transition;try{js.transition=null,bt=1,function(e,t,n,r){do{xu()}while(null!==Gs);if(0!=(6&Os))throw Error(a(327));n=e.finishedWork;var i=e.finishedLanes;if(null===n)return null;if(e.finishedWork=null,e.finishedLanes=0,n===e.current)throw Error(a(177));e.callbackNode=null,e.callbackPriority=0;var o=n.lanes|n.childLanes;if(function(e,t){var n=e.pendingLanes&~t;e.pendingLanes=t,e.suspendedLanes=0,e.pingedLanes=0,e.expiredLanes&=t,e.mutableReadLanes&=t,e.entangledLanes&=t,t=e.entanglements;var r=e.eventTimes;for(e=e.expirationTimes;0<n;){var i=31-ot(n),a=1<<i;t[i]=0,r[i]=-1,e[i]=-1,n&=~a}}(e,o),e===Es&&(Ns=Es=null,Ts=0),0==(2064&n.subtreeFlags)&&0==(2064&n.flags)||Qs||(Qs=!0,Eu(tt,(function(){return xu(),null}))),o=0!=(15990&n.flags),0!=(15990&n.subtreeFlags)||o){o=js.transition,js.transition=null;var l=bt;bt=1;var s=Os;Os|=4,Cs.current=null,function(e,t){if(ei=$t,hr(e=fr())){if("selectionStart"in e)var n={start:e.selectionStart,end:e.selectionEnd};else e:{var r=(n=(n=e.ownerDocument)&&n.defaultView||window).getSelection&&n.getSelection();if(r&&0!==r.rangeCount){n=r.anchorNode;var i=r.anchorOffset,o=r.focusNode;r=r.focusOffset;try{n.nodeType,o.nodeType}catch(e){n=null;break e}var l=0,s=-1,u=-1,c=0,d=0,f=e,h=null;t:for(;;){for(var p;f!==n||0!==i&&3!==f.nodeType||(s=l+i),f!==o||0!==r&&3!==f.nodeType||(u=l+r),3===f.nodeType&&(l+=f.nodeValue.length),null!==(p=f.firstChild);)h=f,f=p;for(;;){if(f===e)break t;if(h===n&&++c===i&&(s=l),h===o&&++d===r&&(u=l),null!==(p=f.nextSibling))break;h=(f=h).parentNode}f=p}n=-1===s||-1===u?null:{start:s,end:u}}else n=null}n=n||{start:0,end:0}}else n=null;for(ti={focusedElem:e,selectionRange:n},$t=!1,Zl=t;null!==Zl;)if(e=(t=Zl).child,0!=(1028&t.subtreeFlags)&&null!==e)e.return=t,Zl=e;else for(;null!==Zl;){t=Zl;try{var v=t.alternate;if(0!=(1024&t.flags))switch(t.tag){case 0:case 11:case 15:case 5:case 6:case 4:case 17:break;case 1:if(null!==v){var m=v.memoizedProps,g=v.memoizedState,y=t.stateNode,b=y.getSnapshotBeforeUpdate(t.elementType===t.type?m:ma(t.type,m),g);y.__reactInternalSnapshotBeforeUpdate=b}break;case 3:var _=t.stateNode.containerInfo;1===_.nodeType?_.textContent="":9===_.nodeType&&_.documentElement&&_.removeChild(_.documentElement);break;default:throw Error(a(163))}}catch(e){zu(t,t.return,e)}if(null!==(e=t.sibling)){e.return=t.return,Zl=e;break}Zl=t.return}v=ns,ns=!1}(e,n),gs(n,e),pr(ti),$t=!!ei,ti=ei=null,e.current=n,bs(n,e,i),Xe(),Os=s,bt=l,js.transition=o}else e.current=n;if(Qs&&(Qs=!1,Gs=e,Xs=i),o=e.pendingLanes,0===o&&(qs=null),function(e){if(at&&"function"==typeof at.onCommitFiberRoot)try{at.onCommitFiberRoot(it,e,void 0,128==(128&e.current.flags))}catch(e){}}(n.stateNode),iu(e,Ye()),null!==t)for(r=e.onRecoverableError,n=0;n<t.length;n++)i=t[n],r(i.value,{componentStack:i.stack,digest:i.digest});if(Ws)throw Ws=!1,e=Ks,Ks=null,e;0!=(1&Xs)&&0!==e.tag&&xu(),o=e.pendingLanes,0!=(1&o)?e===Js?Ys++:(Ys=0,Js=e):Ys=0,Fi()}(e,t,n,r)}finally{js.transition=i,bt=r}return null}function xu(){if(null!==Gs){var e=_t(Xs),t=js.transition,n=bt;try{if(js.transition=null,bt=16>e?16:e,null===Gs)var r=!1;else{if(e=Gs,Gs=null,Xs=0,0!=(6&Os))throw Error(a(331));var i=Os;for(Os|=4,Zl=e.current;null!==Zl;){var o=Zl,l=o.child;if(0!=(16&Zl.flags)){var s=o.deletions;if(null!==s){for(var u=0;u<s.length;u++){var c=s[u];for(Zl=c;null!==Zl;){var d=Zl;switch(d.tag){case 0:case 11:case 15:rs(8,d,o)}var f=d.child;if(null!==f)f.return=d,Zl=f;else for(;null!==Zl;){var h=(d=Zl).sibling,p=d.return;if(os(d),d===c){Zl=null;break}if(null!==h){h.return=p,Zl=h;break}Zl=p}}}var v=o.alternate;if(null!==v){var m=v.child;if(null!==m){v.child=null;do{var g=m.sibling;m.sibling=null,m=g}while(null!==m)}}Zl=o}}if(0!=(2064&o.subtreeFlags)&&null!==l)l.return=o,Zl=l;else e:for(;null!==Zl;){if(0!=(2048&(o=Zl).flags))switch(o.tag){case 0:case 11:case 15:rs(9,o,o.return)}var y=o.sibling;if(null!==y){y.return=o.return,Zl=y;break e}Zl=o.return}}var b=e.current;for(Zl=b;null!==Zl;){var _=(l=Zl).child;if(0!=(2064&l.subtreeFlags)&&null!==_)_.return=l,Zl=_;else e:for(l=b;null!==Zl;){if(0!=(2048&(s=Zl).flags))try{switch(s.tag){case 0:case 11:case 15:is(9,s)}}catch(e){zu(s,s.return,e)}if(s===l){Zl=null;break e}var w=s.sibling;if(null!==w){w.return=s.return,Zl=w;break e}Zl=s.return}}if(Os=i,Fi(),at&&"function"==typeof at.onPostCommitFiberRoot)try{at.onPostCommitFiberRoot(it,e)}catch(e){}r=!0}return r}finally{bt=n,js.transition=t}}return!1}function Su(e,t,n){e=Ia(e,t=pl(0,t=cl(n,t),1),1),t=tu(),null!==e&&(gt(e,1,t),iu(e,t))}function zu(e,t,n){if(3===e.tag)Su(e,e,n);else for(;null!==t;){if(3===t.tag){Su(t,e,n);break}if(1===t.tag){var r=t.stateNode;if("function"==typeof t.type.getDerivedStateFromError||"function"==typeof r.componentDidCatch&&(null===qs||!qs.has(r))){t=Ia(t,e=vl(t,e=cl(n,e),1),1),e=tu(),null!==t&&(gt(t,1,e),iu(t,e));break}}t=t.return}}function Pu(e,t,n){var r=e.pingCache;null!==r&&r.delete(t),t=tu(),e.pingedLanes|=e.suspendedLanes&n,Es===e&&(Ts&n)===n&&(4===As||3===As&&(130023424&Ts)===Ts&&500>Ye()-Fs?hu(e,0):Us|=n),iu(e,t)}function Cu(e,t){0===t&&(0==(1&e.mode)?t=1:(t=ct,0==(130023424&(ct<<=1))&&(ct=4194304)));var n=tu();null!==(e=Oa(e,t))&&(gt(e,t,n),iu(e,n))}function ju(e){var t=e.memoizedState,n=0;null!==t&&(n=t.retryLane),Cu(e,n)}function Ou(e,t){var n=0;switch(e.tag){case 13:var r=e.stateNode,i=e.memoizedState;null!==i&&(n=i.retryLane);break;case 19:r=e.stateNode;break;default:throw Error(a(314))}null!==r&&r.delete(t),Cu(e,n)}function Eu(e,t){return qe(e,t)}function Nu(e,t,n,r){this.tag=e,this.key=n,this.sibling=this.child=this.return=this.stateNode=this.type=this.elementType=null,this.index=0,this.ref=null,this.pendingProps=t,this.dependencies=this.memoizedState=this.updateQueue=this.memoizedProps=null,this.mode=r,this.subtreeFlags=this.flags=0,this.deletions=null,this.childLanes=this.lanes=0,this.alternate=null}function Tu(e,t,n,r){return new Nu(e,t,n,r)}function Lu(e){return!(!(e=e.prototype)||!e.isReactComponent)}function Iu(e,t){var n=e.alternate;return null===n?((n=Tu(e.tag,t,e.key,e.mode)).elementType=e.elementType,n.type=e.type,n.stateNode=e.stateNode,n.alternate=e,e.alternate=n):(n.pendingProps=t,n.type=e.type,n.flags=0,n.subtreeFlags=0,n.deletions=null),n.flags=14680064&e.flags,n.childLanes=e.childLanes,n.lanes=e.lanes,n.child=e.child,n.memoizedProps=e.memoizedProps,n.memoizedState=e.memoizedState,n.updateQueue=e.updateQueue,t=e.dependencies,n.dependencies=null===t?null:{lanes:t.lanes,firstContext:t.firstContext},n.sibling=e.sibling,n.index=e.index,n.ref=e.ref,n}function Au(e,t,n,r,i,o){var l=2;if(r=e,"function"==typeof e)Lu(e)&&(l=1);else if("string"==typeof e)l=5;else e:switch(e){case x:return Mu(n.children,i,o,t);case S:l=8,i|=8;break;case z:return(e=Tu(12,n,t,2|i)).elementType=z,e.lanes=o,e;case O:return(e=Tu(13,n,t,i)).elementType=O,e.lanes=o,e;case E:return(e=Tu(19,n,t,i)).elementType=E,e.lanes=o,e;case L:return Ru(n,i,o,t);default:if("object"==typeof e&&null!==e)switch(e.$$typeof){case P:l=10;break e;case C:l=9;break e;case j:l=11;break e;case N:l=14;break e;case T:l=16,r=null;break e}throw Error(a(130,null==e?e:typeof e,""))}return(t=Tu(l,n,t,i)).elementType=e,t.type=r,t.lanes=o,t}function Mu(e,t,n,r){return(e=Tu(7,e,r,t)).lanes=n,e}function Ru(e,t,n,r){return(e=Tu(22,e,r,t)).elementType=L,e.lanes=n,e.stateNode={isHidden:!1},e}function Du(e,t,n){return(e=Tu(6,e,null,t)).lanes=n,e}function Uu(e,t,n){return(t=Tu(4,null!==e.children?e.children:[],e.key,t)).lanes=n,t.stateNode={containerInfo:e.containerInfo,pendingChildren:null,implementation:e.implementation},t}function Vu(e,t,n,r,i){this.tag=t,this.containerInfo=e,this.finishedWork=this.pingCache=this.current=this.pendingChildren=null,this.timeoutHandle=-1,this.callbackNode=this.pendingContext=this.context=null,this.callbackPriority=0,this.eventTimes=mt(0),this.expirationTimes=mt(-1),this.entangledLanes=this.finishedLanes=this.mutableReadLanes=this.expiredLanes=this.pingedLanes=this.suspendedLanes=this.pendingLanes=0,this.entanglements=mt(0),this.identifierPrefix=r,this.onRecoverableError=i,this.mutableSourceEagerHydrationData=null}function Bu(e,t,n,r,i,a,o,l,s){return e=new Vu(e,t,n,l,s),1===t?(t=1,!0===a&&(t|=8)):t=0,a=Tu(3,null,null,t),e.current=a,a.stateNode=e,a.memoizedState={element:r,isDehydrated:n,cache:null,transitions:null,pendingSuspenseBoundaries:null},Na(a),e}function Fu(e){if(!e)return Ci;e:{if(Fe(e=e._reactInternals)!==e||1!==e.tag)throw Error(a(170));var t=e;do{switch(t.tag){case 3:t=t.stateNode.context;break e;case 1:if(Ti(t.type)){t=t.stateNode.__reactInternalMemoizedMergedChildContext;break e}}t=t.return}while(null!==t);throw Error(a(171))}if(1===e.tag){var n=e.type;if(Ti(n))return Ai(e,n,t)}return t}function Hu(e,t,n,r,i,a,o,l,s){return(e=Bu(n,r,!0,e,0,a,0,l,s)).context=Fu(null),n=e.current,(a=La(r=tu(),i=nu(n))).callback=null!=t?t:null,Ia(n,a,i),e.current.lanes=i,gt(e,i,r),iu(e,r),e}function $u(e,t,n,r){var i=t.current,a=tu(),o=nu(i);return n=Fu(n),null===t.context?t.context=n:t.pendingContext=n,(t=La(a,o)).payload={element:e},null!==(r=void 0===r?null:r)&&(t.callback=r),null!==(e=Ia(i,t,o))&&(ru(e,i,o,a),Aa(e,i,o)),o}function Wu(e){return(e=e.current).child?(e.child.tag,e.child.stateNode):null}function Ku(e,t){if(null!==(e=e.memoizedState)&&null!==e.dehydrated){var n=e.retryLane;e.retryLane=0!==n&&n<t?n:t}}function qu(e,t){Ku(e,t),(e=e.alternate)&&Ku(e,t)}Ss=function(e,t,n){if(null!==e)if(e.memoizedProps!==t.pendingProps||Oi.current)_l=!0;else{if(0==(e.lanes&n)&&0==(128&t.flags))return _l=!1,function(e,t,n){switch(t.tag){case 3:El(t),ha();break;case 5:ao(t);break;case 1:Ti(t.type)&&Mi(t);break;case 4:ro(t,t.stateNode.containerInfo);break;case 10:var r=t.type._context,i=t.memoizedProps.value;Pi(ga,r._currentValue),r._currentValue=i;break;case 13:if(null!==(r=t.memoizedState))return null!==r.dehydrated?(Pi(lo,1&lo.current),t.flags|=128,null):0!=(n&t.child.childLanes)?Dl(e,t,n):(Pi(lo,1&lo.current),null!==(e=Wl(e,t,n))?e.sibling:null);Pi(lo,1&lo.current);break;case 19:if(r=0!=(n&t.childLanes),0!=(128&e.flags)){if(r)return Hl(e,t,n);t.flags|=128}if(null!==(i=t.memoizedState)&&(i.rendering=null,i.tail=null,i.lastEffect=null),Pi(lo,lo.current),r)break;return null;case 22:case 23:return t.lanes=0,zl(e,t,n)}return Wl(e,t,n)}(e,t,n);_l=0!=(131072&e.flags)}else _l=!1,ia&&0!=(1048576&t.flags)&&Zi(t,Ki,t.index);switch(t.lanes=0,t.tag){case 2:var r=t.type;$l(e,t),e=t.pendingProps;var i=Ni(t,ji.current);Sa(t,n),i=So(null,t,r,e,i,n);var o=zo();return t.flags|=1,"object"==typeof i&&null!==i&&"function"==typeof i.render&&void 0===i.$$typeof?(t.tag=1,t.memoizedState=null,t.updateQueue=null,Ti(r)?(o=!0,Mi(t)):o=!1,t.memoizedState=null!==i.state&&void 0!==i.state?i.state:null,Na(t),i.updater=Ba,t.stateNode=i,i._reactInternals=t,Wa(t,r,e,n),t=Ol(null,t,r,!0,o,n)):(t.tag=0,ia&&o&&ea(t),wl(null,t,i,n),t=t.child),t;case 16:r=t.elementType;e:{switch($l(e,t),e=t.pendingProps,r=(i=r._init)(r._payload),t.type=r,i=t.tag=function(e){if("function"==typeof e)return Lu(e)?1:0;if(null!=e){if((e=e.$$typeof)===j)return 11;if(e===N)return 14}return 2}(r),e=ma(r,e),i){case 0:t=Cl(null,t,r,e,n);break e;case 1:t=jl(null,t,r,e,n);break e;case 11:t=kl(null,t,r,e,n);break e;case 14:t=xl(null,t,r,ma(r.type,e),n);break e}throw Error(a(306,r,""))}return t;case 0:return r=t.type,i=t.pendingProps,Cl(e,t,r,i=t.elementType===r?i:ma(r,i),n);case 1:return r=t.type,i=t.pendingProps,jl(e,t,r,i=t.elementType===r?i:ma(r,i),n);case 3:e:{if(El(t),null===e)throw Error(a(387));r=t.pendingProps,i=(o=t.memoizedState).element,Ta(e,t),Ra(t,r,null,n);var l=t.memoizedState;if(r=l.element,o.isDehydrated){if(o={element:r,isDehydrated:!1,cache:l.cache,pendingSuspenseBoundaries:l.pendingSuspenseBoundaries,transitions:l.transitions},t.updateQueue.baseState=o,t.memoizedState=o,256&t.flags){t=Nl(e,t,r,n,i=cl(Error(a(423)),t));break e}if(r!==i){t=Nl(e,t,r,n,i=cl(Error(a(424)),t));break e}for(ra=ui(t.stateNode.containerInfo.firstChild),na=t,ia=!0,aa=null,n=Ya(t,null,r,n),t.child=n;n;)n.flags=-3&n.flags|4096,n=n.sibling}else{if(ha(),r===i){t=Wl(e,t,n);break e}wl(e,t,r,n)}t=t.child}return t;case 5:return ao(t),null===e&&ua(t),r=t.type,i=t.pendingProps,o=null!==e?e.memoizedProps:null,l=i.children,ni(r,i)?l=null:null!==o&&ni(r,o)&&(t.flags|=32),Pl(e,t),wl(e,t,l,n),t.child;case 6:return null===e&&ua(t),null;case 13:return Dl(e,t,n);case 4:return ro(t,t.stateNode.containerInfo),r=t.pendingProps,null===e?t.child=Xa(t,null,r,n):wl(e,t,r,n),t.child;case 11:return r=t.type,i=t.pendingProps,kl(e,t,r,i=t.elementType===r?i:ma(r,i),n);case 7:return wl(e,t,t.pendingProps,n),t.child;case 8:case 12:return wl(e,t,t.pendingProps.children,n),t.child;case 10:e:{if(r=t.type._context,i=t.pendingProps,o=t.memoizedProps,l=i.value,Pi(ga,r._currentValue),r._currentValue=l,null!==o)if(lr(o.value,l)){if(o.children===i.children&&!Oi.current){t=Wl(e,t,n);break e}}else for(null!==(o=t.child)&&(o.return=t);null!==o;){var s=o.dependencies;if(null!==s){l=o.child;for(var u=s.firstContext;null!==u;){if(u.context===r){if(1===o.tag){(u=La(-1,n&-n)).tag=2;var c=o.updateQueue;if(null!==c){var d=(c=c.shared).pending;null===d?u.next=u:(u.next=d.next,d.next=u),c.pending=u}}o.lanes|=n,null!==(u=o.alternate)&&(u.lanes|=n),xa(o.return,n,t),s.lanes|=n;break}u=u.next}}else if(10===o.tag)l=o.type===t.type?null:o.child;else if(18===o.tag){if(null===(l=o.return))throw Error(a(341));l.lanes|=n,null!==(s=l.alternate)&&(s.lanes|=n),xa(l,n,t),l=o.sibling}else l=o.child;if(null!==l)l.return=o;else for(l=o;null!==l;){if(l===t){l=null;break}if(null!==(o=l.sibling)){o.return=l.return,l=o;break}l=l.return}o=l}wl(e,t,i.children,n),t=t.child}return t;case 9:return i=t.type,r=t.pendingProps.children,Sa(t,n),r=r(i=za(i)),t.flags|=1,wl(e,t,r,n),t.child;case 14:return i=ma(r=t.type,t.pendingProps),xl(e,t,r,i=ma(r.type,i),n);case 15:return Sl(e,t,t.type,t.pendingProps,n);case 17:return r=t.type,i=t.pendingProps,i=t.elementType===r?i:ma(r,i),$l(e,t),t.tag=1,Ti(r)?(e=!0,Mi(t)):e=!1,Sa(t,n),Ha(t,r,i),Wa(t,r,i,n),Ol(null,t,r,!0,e,n);case 19:return Hl(e,t,n);case 22:return zl(e,t,n)}throw Error(a(156,t.tag))};var Qu="function"==typeof reportError?reportError:function(e){console.error(e)};function Gu(e){this._internalRoot=e}function Xu(e){this._internalRoot=e}function Yu(e){return!(!e||1!==e.nodeType&&9!==e.nodeType&&11!==e.nodeType)}function Ju(e){return!(!e||1!==e.nodeType&&9!==e.nodeType&&11!==e.nodeType&&(8!==e.nodeType||" react-mount-point-unstable "!==e.nodeValue))}function Zu(){}function ec(e,t,n,r,i){var a=n._reactRootContainer;if(a){var o=a;if("function"==typeof i){var l=i;i=function(){var e=Wu(o);l.call(e)}}$u(t,o,e,i)}else o=function(e,t,n,r,i){if(i){if("function"==typeof r){var a=r;r=function(){var e=Wu(o);a.call(e)}}var o=Hu(t,r,e,0,null,!1,0,"",Zu);return e._reactRootContainer=o,e[pi]=o.current,Fr(8===e.nodeType?e.parentNode:e),du(),o}for(;i=e.lastChild;)e.removeChild(i);if("function"==typeof r){var l=r;r=function(){var e=Wu(s);l.call(e)}}var s=Bu(e,0,!1,null,0,!1,0,"",Zu);return e._reactRootContainer=s,e[pi]=s.current,Fr(8===e.nodeType?e.parentNode:e),du((function(){$u(t,s,n,r)})),s}(n,t,e,i,r);return Wu(o)}Xu.prototype.render=Gu.prototype.render=function(e){var t=this._internalRoot;if(null===t)throw Error(a(409));$u(e,t,null,null)},Xu.prototype.unmount=Gu.prototype.unmount=function(){var e=this._internalRoot;if(null!==e){this._internalRoot=null;var t=e.containerInfo;du((function(){$u(null,e,null,null)})),t[pi]=null}},Xu.prototype.unstable_scheduleHydration=function(e){if(e){var t=St();e={blockedOn:null,target:e,priority:t};for(var n=0;n<Lt.length&&0!==t&&t<Lt[n].priority;n++);Lt.splice(n,0,e),0===n&&Rt(e)}},wt=function(e){switch(e.tag){case 3:var t=e.stateNode;if(t.current.memoizedState.isDehydrated){var n=dt(t.pendingLanes);0!==n&&(yt(t,1|n),iu(t,Ye()),0==(6&Os)&&(Hs=Ye()+500,Fi()))}break;case 13:du((function(){var t=Oa(e,1);if(null!==t){var n=tu();ru(t,e,1,n)}})),qu(e,1)}},kt=function(e){if(13===e.tag){var t=Oa(e,134217728);if(null!==t)ru(t,e,134217728,tu());qu(e,134217728)}},xt=function(e){if(13===e.tag){var t=nu(e),n=Oa(e,t);if(null!==n)ru(n,e,t,tu());qu(e,t)}},St=function(){return bt},zt=function(e,t){var n=bt;try{return bt=e,t()}finally{bt=n}},ke=function(e,t,n){switch(t){case"input":if(J(e,n),t=n.name,"radio"===n.type&&null!=t){for(n=e;n.parentNode;)n=n.parentNode;for(n=n.querySelectorAll("input[name="+JSON.stringify(""+t)+'][type="radio"]'),t=0;t<n.length;t++){var r=n[t];if(r!==e&&r.form===e.form){var i=wi(r);if(!i)throw Error(a(90));q(r),J(r,i)}}}break;case"textarea":ae(e,n);break;case"select":null!=(t=n.value)&&ne(e,!!n.multiple,t,!1)}},je=cu,Oe=du;var tc={usingClientEntryPoint:!1,Events:[bi,_i,wi,Pe,Ce,cu]},nc={findFiberByHostInstance:yi,bundleType:0,version:"18.2.0",rendererPackageName:"react-dom"},rc={bundleType:nc.bundleType,version:nc.version,rendererPackageName:nc.rendererPackageName,rendererConfig:nc.rendererConfig,overrideHookState:null,overrideHookStateDeletePath:null,overrideHookStateRenamePath:null,overrideProps:null,overridePropsDeletePath:null,overridePropsRenamePath:null,setErrorHandler:null,setSuspenseHandler:null,scheduleUpdate:null,currentDispatcherRef:_.ReactCurrentDispatcher,findHostInstanceByFiber:function(e){return null===(e=We(e))?null:e.stateNode},findFiberByHostInstance:nc.findFiberByHostInstance||function(){return null},findHostInstancesForRefresh:null,scheduleRefresh:null,scheduleRoot:null,setRefreshHandler:null,getCurrentFiber:null,reconcilerVersion:"18.2.0-next-9e3b772b8-20220608"};if("undefined"!=typeof __REACT_DEVTOOLS_GLOBAL_HOOK__){var ic=__REACT_DEVTOOLS_GLOBAL_HOOK__;if(!ic.isDisabled&&ic.supportsFiber)try{it=ic.inject(rc),at=ic}catch(ce){}}t.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED=tc,t.createPortal=function(e,t){var n=2<arguments.length&&void 0!==arguments[2]?arguments[2]:null;if(!Yu(t))throw Error(a(200));return function(e,t,n){var r=3<arguments.length&&void 0!==arguments[3]?arguments[3]:null;return{$$typeof:k,key:null==r?null:""+r,children:e,containerInfo:t,implementation:n}}(e,t,null,n)},t.createRoot=function(e,t){if(!Yu(e))throw Error(a(299));var n=!1,r="",i=Qu;return null!=t&&(!0===t.unstable_strictMode&&(n=!0),void 0!==t.identifierPrefix&&(r=t.identifierPrefix),void 0!==t.onRecoverableError&&(i=t.onRecoverableError)),t=Bu(e,1,!1,null,0,n,0,r,i),e[pi]=t.current,Fr(8===e.nodeType?e.parentNode:e),new Gu(t)},t.findDOMNode=function(e){if(null==e)return null;if(1===e.nodeType)return e;var t=e._reactInternals;if(void 0===t){if("function"==typeof e.render)throw Error(a(188));throw e=Object.keys(e).join(","),Error(a(268,e))}return e=null===(e=We(t))?null:e.stateNode},t.flushSync=function(e){return du(e)},t.hydrate=function(e,t,n){if(!Ju(t))throw Error(a(200));return ec(null,e,t,!0,n)},t.hydrateRoot=function(e,t,n){if(!Yu(e))throw Error(a(405));var r=null!=n&&n.hydratedSources||null,i=!1,o="",l=Qu;if(null!=n&&(!0===n.unstable_strictMode&&(i=!0),void 0!==n.identifierPrefix&&(o=n.identifierPrefix),void 0!==n.onRecoverableError&&(l=n.onRecoverableError)),t=Hu(t,null,e,1,null!=n?n:null,i,0,o,l),e[pi]=t.current,Fr(e),r)for(e=0;e<r.length;e++)i=(i=(n=r[e])._getVersion)(n._source),null==t.mutableSourceEagerHydrationData?t.mutableSourceEagerHydrationData=[n,i]:t.mutableSourceEagerHydrationData.push(n,i);return new Xu(t)},t.render=function(e,t,n){if(!Ju(t))throw Error(a(200));return ec(null,e,t,!1,n)},t.unmountComponentAtNode=function(e){if(!Ju(e))throw Error(a(40));return!!e._reactRootContainer&&(du((function(){ec(null,null,e,!1,(function(){e._reactRootContainer=null,e[pi]=null}))})),!0)},t.unstable_batchedUpdates=cu,t.unstable_renderSubtreeIntoContainer=function(e,t,n,r){if(!Ju(n))throw Error(a(200));if(null==e||void 0===e._reactInternals)throw Error(a(38));return ec(e,t,n,!1,r)},t.version="18.2.0-next-9e3b772b8-20220608"},478:function(e,t,n){"use strict";var r=n(422);t.s=r.createRoot,r.hydrateRoot},422:function(e,t,n){"use strict";!function e(){if("undefined"!=typeof __REACT_DEVTOOLS_GLOBAL_HOOK__&&"function"==typeof __REACT_DEVTOOLS_GLOBAL_HOOK__.checkDCE)try{__REACT_DEVTOOLS_GLOBAL_HOOK__.checkDCE(e)}catch(e){console.error(e)}}(),e.exports=n(746)},354:function(e,t,n){"use strict";var r=n(959),i=Symbol.for("react.element"),a=Symbol.for("react.fragment"),o=Object.prototype.hasOwnProperty,l=r.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner,s={key:!0,ref:!0,__self:!0,__source:!0};function u(e,t,n){var r,a={},u=null,c=null;for(r in void 0!==n&&(u=""+n),void 0!==t.key&&(u=""+t.key),void 0!==t.ref&&(c=t.ref),t)o.call(t,r)&&!s.hasOwnProperty(r)&&(a[r]=t[r]);if(e&&e.defaultProps)for(r in t=e.defaultProps)void 0===a[r]&&(a[r]=t[r]);return{$$typeof:i,type:e,key:u,ref:c,props:a,_owner:l.current}}t.Fragment=a,t.jsx=u,t.jsxs=u},257:function(e,t){"use strict";var n=Symbol.for("react.element"),r=Symbol.for("react.portal"),i=Symbol.for("react.fragment"),a=Symbol.for("react.strict_mode"),o=Symbol.for("react.profiler"),l=Symbol.for("react.provider"),s=Symbol.for("react.context"),u=Symbol.for("react.forward_ref"),c=Symbol.for("react.suspense"),d=Symbol.for("react.memo"),f=Symbol.for("react.lazy"),h=Symbol.iterator;var p={isMounted:function(){return!1},enqueueForceUpdate:function(){},enqueueReplaceState:function(){},enqueueSetState:function(){}},v=Object.assign,m={};function g(e,t,n){this.props=e,this.context=t,this.refs=m,this.updater=n||p}function y(){}function b(e,t,n){this.props=e,this.context=t,this.refs=m,this.updater=n||p}g.prototype.isReactComponent={},g.prototype.setState=function(e,t){if("object"!=typeof e&&"function"!=typeof e&&null!=e)throw Error("setState(...): takes an object of state variables to update or a function which returns an object of state variables.");this.updater.enqueueSetState(this,e,t,"setState")},g.prototype.forceUpdate=function(e){this.updater.enqueueForceUpdate(this,e,"forceUpdate")},y.prototype=g.prototype;var _=b.prototype=new y;_.constructor=b,v(_,g.prototype),_.isPureReactComponent=!0;var w=Array.isArray,k=Object.prototype.hasOwnProperty,x={current:null},S={key:!0,ref:!0,__self:!0,__source:!0};function z(e,t,r){var i,a={},o=null,l=null;if(null!=t)for(i in void 0!==t.ref&&(l=t.ref),void 0!==t.key&&(o=""+t.key),t)k.call(t,i)&&!S.hasOwnProperty(i)&&(a[i]=t[i]);var s=arguments.length-2;if(1===s)a.children=r;else if(1<s){for(var u=Array(s),c=0;c<s;c++)u[c]=arguments[c+2];a.children=u}if(e&&e.defaultProps)for(i in s=e.defaultProps)void 0===a[i]&&(a[i]=s[i]);return{$$typeof:n,type:e,key:o,ref:l,props:a,_owner:x.current}}function P(e){return"object"==typeof e&&null!==e&&e.$$typeof===n}var C=/\/+/g;function j(e,t){return"object"==typeof e&&null!==e&&null!=e.key?function(e){var t={"=":"=0",":":"=2"};return"$"+e.replace(/[=:]/g,(function(e){return t[e]}))}(""+e.key):t.toString(36)}function O(e,t,i,a,o){var l=typeof e;"undefined"!==l&&"boolean"!==l||(e=null);var s=!1;if(null===e)s=!0;else switch(l){case"string":case"number":s=!0;break;case"object":switch(e.$$typeof){case n:case r:s=!0}}if(s)return o=o(s=e),e=""===a?"."+j(s,0):a,w(o)?(i="",null!=e&&(i=e.replace(C,"$&/")+"/"),O(o,t,i,"",(function(e){return e}))):null!=o&&(P(o)&&(o=function(e,t){return{$$typeof:n,type:e.type,key:t,ref:e.ref,props:e.props,_owner:e._owner}}(o,i+(!o.key||s&&s.key===o.key?"":(""+o.key).replace(C,"$&/")+"/")+e)),t.push(o)),1;if(s=0,a=""===a?".":a+":",w(e))for(var u=0;u<e.length;u++){var c=a+j(l=e[u],u);s+=O(l,t,i,c,o)}else if(c=function(e){return null===e||"object"!=typeof e?null:"function"==typeof(e=h&&e[h]||e["@@iterator"])?e:null}(e),"function"==typeof c)for(e=c.call(e),u=0;!(l=e.next()).done;)s+=O(l=l.value,t,i,c=a+j(l,u++),o);else if("object"===l)throw t=String(e),Error("Objects are not valid as a React child (found: "+("[object Object]"===t?"object with keys {"+Object.keys(e).join(", ")+"}":t)+"). If you meant to render a collection of children, use an array instead.");return s}function E(e,t,n){if(null==e)return e;var r=[],i=0;return O(e,r,"","",(function(e){return t.call(n,e,i++)})),r}function N(e){if(-1===e._status){var t=e._result;(t=t()).then((function(t){0!==e._status&&-1!==e._status||(e._status=1,e._result=t)}),(function(t){0!==e._status&&-1!==e._status||(e._status=2,e._result=t)})),-1===e._status&&(e._status=0,e._result=t)}if(1===e._status)return e._result.default;throw e._result}var T={current:null},L={transition:null},I={ReactCurrentDispatcher:T,ReactCurrentBatchConfig:L,ReactCurrentOwner:x};t.Children={map:E,forEach:function(e,t,n){E(e,(function(){t.apply(this,arguments)}),n)},count:function(e){var t=0;return E(e,(function(){t++})),t},toArray:function(e){return E(e,(function(e){return e}))||[]},only:function(e){if(!P(e))throw Error("React.Children.only expected to receive a single React element child.");return e}},t.Component=g,t.Fragment=i,t.Profiler=o,t.PureComponent=b,t.StrictMode=a,t.Suspense=c,t.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED=I,t.cloneElement=function(e,t,r){if(null==e)throw Error("React.cloneElement(...): The argument must be a React element, but you passed "+e+".");var i=v({},e.props),a=e.key,o=e.ref,l=e._owner;if(null!=t){if(void 0!==t.ref&&(o=t.ref,l=x.current),void 0!==t.key&&(a=""+t.key),e.type&&e.type.defaultProps)var s=e.type.defaultProps;for(u in t)k.call(t,u)&&!S.hasOwnProperty(u)&&(i[u]=void 0===t[u]&&void 0!==s?s[u]:t[u])}var u=arguments.length-2;if(1===u)i.children=r;else if(1<u){s=Array(u);for(var c=0;c<u;c++)s[c]=arguments[c+2];i.children=s}return{$$typeof:n,type:e.type,key:a,ref:o,props:i,_owner:l}},t.createContext=function(e){return(e={$$typeof:s,_currentValue:e,_currentValue2:e,_threadCount:0,Provider:null,Consumer:null,_defaultValue:null,_globalName:null}).Provider={$$typeof:l,_context:e},e.Consumer=e},t.createElement=z,t.createFactory=function(e){var t=z.bind(null,e);return t.type=e,t},t.createRef=function(){return{current:null}},t.forwardRef=function(e){return{$$typeof:u,render:e}},t.isValidElement=P,t.lazy=function(e){return{$$typeof:f,_payload:{_status:-1,_result:e},_init:N}},t.memo=function(e,t){return{$$typeof:d,type:e,compare:void 0===t?null:t}},t.startTransition=function(e){var t=L.transition;L.transition={};try{e()}finally{L.transition=t}},t.unstable_act=function(){throw Error("act(...) is not supported in production builds of React.")},t.useCallback=function(e,t){return T.current.useCallback(e,t)},t.useContext=function(e){return T.current.useContext(e)},t.useDebugValue=function(){},t.useDeferredValue=function(e){return T.current.useDeferredValue(e)},t.useEffect=function(e,t){return T.current.useEffect(e,t)},t.useId=function(){return T.current.useId()},t.useImperativeHandle=function(e,t,n){return T.current.useImperativeHandle(e,t,n)},t.useInsertionEffect=function(e,t){return T.current.useInsertionEffect(e,t)},t.useLayoutEffect=function(e,t){return T.current.useLayoutEffect(e,t)},t.useMemo=function(e,t){return T.current.useMemo(e,t)},t.useReducer=function(e,t,n){return T.current.useReducer(e,t,n)},t.useRef=function(e){return T.current.useRef(e)},t.useState=function(e){return T.current.useState(e)},t.useSyncExternalStore=function(e,t,n){return T.current.useSyncExternalStore(e,t,n)},t.useTransition=function(){return T.current.useTransition()},t.version="18.2.0"},959:function(e,t,n){"use strict";e.exports=n(257)},527:function(e,t,n){"use strict";e.exports=n(354)},568:function(e,t){"use strict";function n(e,t){var n=e.length;e.push(t);e:for(;0<n;){var r=n-1>>>1,i=e[r];if(!(0<a(i,t)))break e;e[r]=t,e[n]=i,n=r}}function r(e){return 0===e.length?null:e[0]}function i(e){if(0===e.length)return null;var t=e[0],n=e.pop();if(n!==t){e[0]=n;e:for(var r=0,i=e.length,o=i>>>1;r<o;){var l=2*(r+1)-1,s=e[l],u=l+1,c=e[u];if(0>a(s,n))u<i&&0>a(c,s)?(e[r]=c,e[u]=n,r=u):(e[r]=s,e[l]=n,r=l);else{if(!(u<i&&0>a(c,n)))break e;e[r]=c,e[u]=n,r=u}}}return t}function a(e,t){var n=e.sortIndex-t.sortIndex;return 0!==n?n:e.id-t.id}if("object"==typeof performance&&"function"==typeof performance.now){var o=performance;t.unstable_now=function(){return o.now()}}else{var l=Date,s=l.now();t.unstable_now=function(){return l.now()-s}}var u=[],c=[],d=1,f=null,h=3,p=!1,v=!1,m=!1,g="function"==typeof setTimeout?setTimeout:null,y="function"==typeof clearTimeout?clearTimeout:null,b="undefined"!=typeof setImmediate?setImmediate:null;function _(e){for(var t=r(c);null!==t;){if(null===t.callback)i(c);else{if(!(t.startTime<=e))break;i(c),t.sortIndex=t.expirationTime,n(u,t)}t=r(c)}}function w(e){if(m=!1,_(e),!v)if(null!==r(u))v=!0,L(k);else{var t=r(c);null!==t&&I(w,t.startTime-e)}}function k(e,n){v=!1,m&&(m=!1,y(P),P=-1),p=!0;var a=h;try{for(_(n),f=r(u);null!==f&&(!(f.expirationTime>n)||e&&!O());){var o=f.callback;if("function"==typeof o){f.callback=null,h=f.priorityLevel;var l=o(f.expirationTime<=n);n=t.unstable_now(),"function"==typeof l?f.callback=l:f===r(u)&&i(u),_(n)}else i(u);f=r(u)}if(null!==f)var s=!0;else{var d=r(c);null!==d&&I(w,d.startTime-n),s=!1}return s}finally{f=null,h=a,p=!1}}"undefined"!=typeof navigator&&void 0!==navigator.scheduling&&void 0!==navigator.scheduling.isInputPending&&navigator.scheduling.isInputPending.bind(navigator.scheduling);var x,S=!1,z=null,P=-1,C=5,j=-1;function O(){return!(t.unstable_now()-j<C)}function E(){if(null!==z){var e=t.unstable_now();j=e;var n=!0;try{n=z(!0,e)}finally{n?x():(S=!1,z=null)}}else S=!1}if("function"==typeof b)x=function(){b(E)};else if("undefined"!=typeof MessageChannel){var N=new MessageChannel,T=N.port2;N.port1.onmessage=E,x=function(){T.postMessage(null)}}else x=function(){g(E,0)};function L(e){z=e,S||(S=!0,x())}function I(e,n){P=g((function(){e(t.unstable_now())}),n)}t.unstable_IdlePriority=5,t.unstable_ImmediatePriority=1,t.unstable_LowPriority=4,t.unstable_NormalPriority=3,t.unstable_Profiling=null,t.unstable_UserBlockingPriority=2,t.unstable_cancelCallback=function(e){e.callback=null},t.unstable_continueExecution=function(){v||p||(v=!0,L(k))},t.unstable_forceFrameRate=function(e){0>e||125<e?console.error("forceFrameRate takes a positive int between 0 and 125, forcing frame rates higher than 125 fps is not supported"):C=0<e?Math.floor(1e3/e):5},t.unstable_getCurrentPriorityLevel=function(){return h},t.unstable_getFirstCallbackNode=function(){return r(u)},t.unstable_next=function(e){switch(h){case 1:case 2:case 3:var t=3;break;default:t=h}var n=h;h=t;try{return e()}finally{h=n}},t.unstable_pauseExecution=function(){},t.unstable_requestPaint=function(){},t.unstable_runWithPriority=function(e,t){switch(e){case 1:case 2:case 3:case 4:case 5:break;default:e=3}var n=h;h=e;try{return t()}finally{h=n}},t.unstable_scheduleCallback=function(e,i,a){var o=t.unstable_now();switch("object"==typeof a&&null!==a?a="number"==typeof(a=a.delay)&&0<a?o+a:o:a=o,e){case 1:var l=-1;break;case 2:l=250;break;case 5:l=1073741823;break;case 4:l=1e4;break;default:l=5e3}return e={id:d++,callback:i,priorityLevel:e,startTime:a,expirationTime:l=a+l,sortIndex:-1},a>o?(e.sortIndex=a,n(c,e),null===r(u)&&e===r(c)&&(m?(y(P),P=-1):m=!0,I(w,a-o))):(e.sortIndex=l,n(u,e),v||p||(v=!0,L(k))),e},t.unstable_shouldYield=O,t.unstable_wrapCallback=function(e){var t=h;return function(){var n=h;h=t;try{return e.apply(this,arguments)}finally{h=n}}}},962:function(e,t,n){"use strict";e.exports=n(568)},935:function(e){e.exports=function(){var e=document.getSelection();if(!e.rangeCount)return function(){};for(var t=document.activeElement,n=[],r=0;r<e.rangeCount;r++)n.push(e.getRangeAt(r));switch(t.tagName.toUpperCase()){case"INPUT":case"TEXTAREA":t.blur();break;default:t=null}return e.removeAllRanges(),function(){"Caret"===e.type&&e.removeAllRanges(),e.rangeCount||n.forEach((function(t){e.addRange(t)})),t&&t.focus()}}},415:function(e,t,n){"use strict";var r=n(959);var i="function"==typeof Object.is?Object.is:function(e,t){return e===t&&(0!==e||1/e==1/t)||e!=e&&t!=t},a=r.useState,o=r.useEffect,l=r.useLayoutEffect,s=r.useDebugValue;function u(e){var t=e.getSnapshot;e=e.value;try{var n=t();return!i(e,n)}catch(e){return!0}}var c="undefined"==typeof window||void 0===window.document||void 0===window.document.createElement?function(e,t){return t()}:function(e,t){var n=t(),r=a({inst:{value:n,getSnapshot:t}}),i=r[0].inst,c=r[1];return l((function(){i.value=n,i.getSnapshot=t,u(i)&&c({inst:i})}),[e,n,t]),o((function(){return u(i)&&c({inst:i}),e((function(){u(i)&&c({inst:i})}))}),[e]),s(n),n};t.useSyncExternalStore=void 0!==r.useSyncExternalStore?r.useSyncExternalStore:c},322:function(e,t,n){"use strict";e.exports=n(415)}},t={};function n(r){var i=t[r];if(void 0!==i)return i.exports;var a=t[r]={exports:{}};return e[r](a,a.exports,n),a.exports}n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,{a:t}),t},n.d=function(e,t){for(var r in t)n.o(t,r)&&!n.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},n.g=function(){if("object"==typeof globalThis)return globalThis;try{return this||new Function("return this")()}catch(e){if("object"==typeof window)return window}}(),n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},function(){"use strict";var e=n(527);function t(e){for(var t=arguments.length,n=new Array(t>1?t-1:0),r=1;r<t;r++)n[r-1]=arguments[r];throw new Error("number"==typeof e?"[MobX] minified error nr: "+e+(n.length?" "+n.map(String).join(","):"")+". Find the full error at: https://github.com/mobxjs/mobx/blob/main/packages/mobx/src/errors.ts":"[MobX] "+e)}var r={};function i(){return"undefined"!=typeof globalThis?globalThis:"undefined"!=typeof window?window:void 0!==n.g?n.g:"undefined"!=typeof self?self:r}var a=Object.assign,o=Object.getOwnPropertyDescriptor,l=Object.defineProperty,s=Object.prototype,u=[];Object.freeze(u);var c={};Object.freeze(c);var d="undefined"!=typeof Proxy,f=Object.toString();function h(){d||t("Proxy not available")}function p(e){var t=!1;return function(){if(!t)return t=!0,e.apply(this,arguments)}}var v=function(){};function m(e){return"function"==typeof e}function g(e){switch(typeof e){case"string":case"symbol":case"number":return!0}return!1}function y(e){return null!==e&&"object"==typeof e}function b(e){if(!y(e))return!1;var t=Object.getPrototypeOf(e);if(null==t)return!0;var n=Object.hasOwnProperty.call(t,"constructor")&&t.constructor;return"function"==typeof n&&n.toString()===f}function _(e){var t=null==e?void 0:e.constructor;return!!t&&("GeneratorFunction"===t.name||"GeneratorFunction"===t.displayName)}function w(e,t,n){l(e,t,{enumerable:!1,writable:!0,configurable:!0,value:n})}function k(e,t,n){l(e,t,{enumerable:!1,writable:!1,configurable:!0,value:n})}function x(e,t){var n="isMobX"+e;return t.prototype[n]=!0,function(e){return y(e)&&!0===e[n]}}function S(e){return e instanceof Map}function z(e){return e instanceof Set}var P=void 0!==Object.getOwnPropertySymbols;var C="undefined"!=typeof Reflect&&Reflect.ownKeys?Reflect.ownKeys:P?function(e){return Object.getOwnPropertyNames(e).concat(Object.getOwnPropertySymbols(e))}:Object.getOwnPropertyNames;function j(e){return null===e?null:"object"==typeof e?""+e:e}function O(e,t){return s.hasOwnProperty.call(e,t)}var E=Object.getOwnPropertyDescriptors||function(e){var t={};return C(e).forEach((function(n){t[n]=o(e,n)})),t};function N(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,(i=r.key,a=void 0,"symbol"==typeof(a=function(e,t){if("object"!=typeof e||null===e)return e;var n=e[Symbol.toPrimitive];if(void 0!==n){var r=n.call(e,t||"default");if("object"!=typeof r)return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===t?String:Number)(e)}(i,"string"))?a:String(a)),r)}var i,a}function T(e,t,n){return t&&N(e.prototype,t),n&&N(e,n),Object.defineProperty(e,"prototype",{writable:!1}),e}function L(){return L=Object.assign?Object.assign.bind():function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var r in n)Object.prototype.hasOwnProperty.call(n,r)&&(e[r]=n[r])}return e},L.apply(this,arguments)}function I(e,t){e.prototype=Object.create(t.prototype),e.prototype.constructor=e,A(e,t)}function A(e,t){return A=Object.setPrototypeOf?Object.setPrototypeOf.bind():function(e,t){return e.__proto__=t,e},A(e,t)}function M(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}function R(e,t){(null==t||t>e.length)&&(t=e.length);for(var n=0,r=new Array(t);n<t;n++)r[n]=e[n];return r}function D(e,t){var n="undefined"!=typeof Symbol&&e[Symbol.iterator]||e["@@iterator"];if(n)return(n=n.call(e)).next.bind(n);if(Array.isArray(e)||(n=function(e,t){if(e){if("string"==typeof e)return R(e,t);var n=Object.prototype.toString.call(e).slice(8,-1);return"Object"===n&&e.constructor&&(n=e.constructor.name),"Map"===n||"Set"===n?Array.from(e):"Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)?R(e,t):void 0}}(e))||t&&e&&"number"==typeof e.length){n&&(e=n);var r=0;return function(){return r>=e.length?{done:!0}:{done:!1,value:e[r++]}}}throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}var U=Symbol("mobx-stored-annotations");function V(e){return Object.assign((function(t,n){if(F(n))return e.decorate_20223_(t,n);B(t,n,e)}),e)}function B(e,t,n){O(e,U)||w(e,U,L({},e[U])),function(e){return e.annotationType_===X}(n)||(e[U][t]=n)}function F(e){return"object"==typeof e&&"string"==typeof e.kind}var H=Symbol("mobx administration"),$=function(){function e(e){void 0===e&&(e="Atom"),this.name_=void 0,this.isPendingUnobservation_=!1,this.isBeingObserved_=!1,this.observers_=new Set,this.diffValue_=0,this.lastAccessedBy_=0,this.lowestObserverState_=Ge.NOT_TRACKING_,this.onBOL=void 0,this.onBUOL=void 0,this.name_=e}var t=e.prototype;return t.onBO=function(){this.onBOL&&this.onBOL.forEach((function(e){return e()}))},t.onBUO=function(){this.onBUOL&&this.onBUOL.forEach((function(e){return e()}))},t.reportObserved=function(){return _t(this)},t.reportChanged=function(){yt(),wt(this),bt()},t.toString=function(){return this.name_},e}(),W=x("Atom",$);function K(e,t,n){void 0===t&&(t=v),void 0===n&&(n=v);var r,i=new $(e);return t!==v&&Wt(Ft,i,t,r),n!==v&&$t(i,n),i}var q={identity:function(e,t){return e===t},structural:function(e,t){return gr(e,t)},default:function(e,t){return Object.is?Object.is(e,t):e===t?0!==e||1/e==1/t:e!=e&&t!=t},shallow:function(e,t){return gr(e,t,1)}};function Q(e,t,n){return sn(e)?e:Array.isArray(e)?Te.array(e,{name:n}):b(e)?Te.object(e,void 0,{name:n}):S(e)?Te.map(e,{name:n}):z(e)?Te.set(e,{name:n}):"function"!=typeof e||Dt(e)||on(e)?e:_(e)?rn(e):Rt(n,e)}function G(e){return e}var X="override";function Y(e,t){return{annotationType_:e,options_:t,make_:J,extend_:Z,decorate_20223_:ee}}function J(e,t,n,r){var i;if(null!=(i=this.options_)&&i.bound)return null===this.extend_(e,t,n,!1)?0:1;if(r===e.target_)return null===this.extend_(e,t,n,!1)?0:2;if(Dt(n.value))return 1;var a=te(e,this,t,n,!1);return l(r,t,a),2}function Z(e,t,n,r){var i=te(e,this,t,n);return e.defineProperty_(t,i,r)}function ee(e,n){var r=n.kind,i=n.name,a=n.addInitializer,o=this;if("field"!=r){var l,s,u,c,d,f;if("method"==r)return Dt(e)||(s=e,e=Fe(null!=(u=null==(c=o.options_)?void 0:c.name)?u:i.toString(),s,null!=(d=null==(f=o.options_)?void 0:f.autoAction)&&d)),null!=(l=this.options_)&&l.bound&&a((function(){var e=this,t=e[i].bind(e);t.isMobxAction=!0,e[i]=t})),e;t("Cannot apply '"+o.annotationType_+"' to '"+String(i)+"' (kind: "+r+"):\n'"+o.annotationType_+"' can only be used on properties with a function value.")}else a((function(){B(this,i,o)}))}function te(e,t,n,r,i){var a,o,l,s,u,c,d,f;void 0===i&&(i=pt.safeDescriptors),f=r,t.annotationType_,f.value;var h,p=r.value;null!=(a=t.options_)&&a.bound&&(p=p.bind(null!=(h=e.proxy_)?h:e.target_));return{value:Fe(null!=(o=null==(l=t.options_)?void 0:l.name)?o:n.toString(),p,null!=(s=null==(u=t.options_)?void 0:u.autoAction)&&s,null!=(c=t.options_)&&c.bound?null!=(d=e.proxy_)?d:e.target_:void 0),configurable:!i||e.isPlainObject_,enumerable:!1,writable:!i}}function ne(e,t){return{annotationType_:e,options_:t,make_:re,extend_:ie,decorate_20223_:ae}}function re(e,t,n,r){var i;if(r===e.target_)return null===this.extend_(e,t,n,!1)?0:2;if(null!=(i=this.options_)&&i.bound&&(!O(e.target_,t)||!on(e.target_[t]))&&null===this.extend_(e,t,n,!1))return 0;if(on(n.value))return 1;var a=oe(e,this,t,n,!1,!1);return l(r,t,a),2}function ie(e,t,n,r){var i,a=oe(e,this,t,n,null==(i=this.options_)?void 0:i.bound);return e.defineProperty_(t,a,r)}function ae(e,t){var n;var r=t.name,i=t.addInitializer;return on(e)||(e=rn(e)),null!=(n=this.options_)&&n.bound&&i((function(){var e=this,t=e[r].bind(e);t.isMobXFlow=!0,e[r]=t})),e}function oe(e,t,n,r,i,a){var o;void 0===a&&(a=pt.safeDescriptors),o=r,t.annotationType_,o.value;var l,s=r.value;(on(s)||(s=rn(s)),i)&&((s=s.bind(null!=(l=e.proxy_)?l:e.target_)).isMobXFlow=!0);return{value:s,configurable:!a||e.isPlainObject_,enumerable:!1,writable:!a}}function le(e,t){return{annotationType_:e,options_:t,make_:se,extend_:ue,decorate_20223_:ce}}function se(e,t,n){return null===this.extend_(e,t,n,!1)?0:1}function ue(e,t,n,r){return function(e,t,n,r){t.annotationType_,r.get;0}(0,this,0,n),e.defineComputedProperty_(t,L({},this.options_,{get:n.get,set:n.set}),r)}function ce(e,t){var n=this,r=t.name;return(0,t.addInitializer)((function(){var t=Gn(this)[H],i=L({},n.options_,{get:e,context:this});i.name||(i.name="ObservableObject."+r.toString()),t.values_.set(r,new Ye(i))})),function(){return this[H].getObservablePropValue_(r)}}function de(e,t){return{annotationType_:e,options_:t,make_:fe,extend_:he,decorate_20223_:pe}}function fe(e,t,n){return null===this.extend_(e,t,n,!1)?0:1}function he(e,t,n,r){var i,a;return function(e,t,n,r){t.annotationType_;0}(0,this),e.defineObservableProperty_(t,n.value,null!=(i=null==(a=this.options_)?void 0:a.enhancer)?i:Q,r)}function pe(e,t){var n=this,r=t.kind,i=t.name,a=new WeakSet;function o(e,t){var r,o,l=Gn(e)[H],s=new qe(t,null!=(r=null==(o=n.options_)?void 0:o.enhancer)?r:Q,"ObservableObject."+i.toString(),!1);l.values_.set(i,s),a.add(e)}if("accessor"==r)return{get:function(){return a.has(this)||o(this,e.get.call(this)),this[H].getObservablePropValue_(i)},set:function(e){return a.has(this)||o(this,e),this[H].setObservablePropValue_(i,e)},init:function(e){return a.has(this)||o(this,e),e}}}var ve="true",me=ge();function ge(e){return{annotationType_:ve,options_:e,make_:ye,extend_:be,decorate_20223_:_e}}function ye(e,t,n,r){var i,a,o,s;if(n.get)return Me.make_(e,t,n,r);if(n.set){var u=Fe(t.toString(),n.set);return r===e.target_?null===e.defineProperty_(t,{configurable:!pt.safeDescriptors||e.isPlainObject_,set:u})?0:2:(l(r,t,{configurable:!0,set:u}),2)}if(r!==e.target_&&"function"==typeof n.value)return _(n.value)?(null!=(s=this.options_)&&s.autoBind?rn.bound:rn).make_(e,t,n,r):(null!=(o=this.options_)&&o.autoBind?Rt.bound:Rt).make_(e,t,n,r);var c,d=!1===(null==(i=this.options_)?void 0:i.deep)?Te.ref:Te;"function"==typeof n.value&&null!=(a=this.options_)&&a.autoBind&&(n.value=n.value.bind(null!=(c=e.proxy_)?c:e.target_));return d.make_(e,t,n,r)}function be(e,t,n,r){var i,a,o;if(n.get)return Me.extend_(e,t,n,r);if(n.set)return e.defineProperty_(t,{configurable:!pt.safeDescriptors||e.isPlainObject_,set:Fe(t.toString(),n.set)},r);"function"==typeof n.value&&null!=(i=this.options_)&&i.autoBind&&(n.value=n.value.bind(null!=(o=e.proxy_)?o:e.target_));return(!1===(null==(a=this.options_)?void 0:a.deep)?Te.ref:Te).extend_(e,t,n,r)}function _e(e,n){t("'"+this.annotationType_+"' cannot be used as a decorator")}var we={deep:!0,name:void 0,defaultDecorator:void 0,proxy:!0};function ke(e){return e||we}Object.freeze(we);var xe=de("observable"),Se=de("observable.ref",{enhancer:G}),ze=de("observable.shallow",{enhancer:function(e,t,n){return null==e||Jn(e)||An(e)||Fn(e)||Wn(e)?e:Array.isArray(e)?Te.array(e,{name:n,deep:!1}):b(e)?Te.object(e,void 0,{name:n,deep:!1}):S(e)?Te.map(e,{name:n,deep:!1}):z(e)?Te.set(e,{name:n,deep:!1}):void 0}}),Pe=de("observable.struct",{enhancer:function(e,t){return gr(e,t)?t:e}}),Ce=V(xe);function je(e){return!0===e.deep?Q:!1===e.deep?G:(t=e.defaultDecorator)&&null!=(n=null==(r=t.options_)?void 0:r.enhancer)?n:Q;var t,n,r}function Oe(e,t,n){return F(t)?xe.decorate_20223_(e,t):g(t)?void B(e,t,xe):sn(e)?e:b(e)?Te.object(e,t,n):Array.isArray(e)?Te.array(e,t):S(e)?Te.map(e,t):z(e)?Te.set(e,t):"object"==typeof e&&null!==e?e:Te.box(e,t)}a(Oe,Ce);var Ee,Ne,Te=a(Oe,{box:function(e,t){var n=ke(t);return new qe(e,je(n),n.name,!0,n.equals)},array:function(e,t){var n=ke(t);return(!1===pt.useProxies||!1===n.proxy?dr:Pn)(e,je(n),n.name)},map:function(e,t){var n=ke(t);return new Bn(e,je(n),n.name)},set:function(e,t){var n=ke(t);return new $n(e,je(n),n.name)},object:function(e,t,n){return vr((function(){return Xt(!1===pt.useProxies||!1===(null==n?void 0:n.proxy)?Gn({},n):function(e,t){var n,r;return h(),e=Gn(e,t),null!=(r=(n=e[H]).proxy_)?r:n.proxy_=new Proxy(e,hn)}({},n),e,t)}))},ref:V(Se),shallow:V(ze),deep:Ce,struct:V(Pe)}),Le="computed",Ie=le(Le),Ae=le("computed.struct",{equals:q.structural}),Me=function(e,t){if(F(t))return Ie.decorate_20223_(e,t);if(g(t))return B(e,t,Ie);if(b(e))return V(le(Le,e));var n=b(t)?t:{};return n.get=e,n.name||(n.name=e.name||""),new Ye(n)};Object.assign(Me,Ie),Me.struct=V(Ae);var Re,De=0,Ue=1,Ve=null!=(Ee=null==(Ne=o((function(){}),"name"))?void 0:Ne.configurable)&&Ee,Be={value:"action",configurable:!0,writable:!1,enumerable:!1};function Fe(e,t,n,r){function i(){return He(e,n,t,r||this,arguments)}return void 0===n&&(n=!1),i.isMobxAction=!0,i.toString=function(){return t.toString()},Ve&&(Be.value=e,l(i,"name",Be)),i}function He(e,n,r,i,a){var o=function(e,t,n,r){var i=!1,a=0;0;var o=pt.trackingDerivation,l=!t||!o;yt();var s=pt.allowStateChanges;l&&(ot(),s=$e(!0));var u=st(!0),c={runAsAction_:l,prevDerivation_:o,prevAllowStateChanges_:s,prevAllowStateReads_:u,notifySpy_:i,startTime_:a,actionId_:Ue++,parentActionId_:De};return De=c.actionId_,c}(0,n);try{return r.apply(i,a)}catch(e){throw o.error_=e,e}finally{!function(e){De!==e.actionId_&&t(30);De=e.parentActionId_,void 0!==e.error_&&(pt.suppressReactionErrors=!0);We(e.prevAllowStateChanges_),ut(e.prevAllowStateReads_),bt(),e.runAsAction_&&lt(e.prevDerivation_);0;pt.suppressReactionErrors=!1}(o)}}function $e(e){var t=pt.allowStateChanges;return pt.allowStateChanges=e,t}function We(e){pt.allowStateChanges=e}Re=Symbol.toPrimitive;var Ke,qe=function(e){function t(t,n,r,i,a){var o;return void 0===r&&(r="ObservableValue"),void 0===i&&(i=!0),void 0===a&&(a=q.default),(o=e.call(this,r)||this).enhancer=void 0,o.name_=void 0,o.equals=void 0,o.hasUnreportedChange_=!1,o.interceptors_=void 0,o.changeListeners_=void 0,o.value_=void 0,o.dehancer=void 0,o.enhancer=n,o.name_=r,o.equals=a,o.value_=n(t,void 0,r),o}I(t,e);var n=t.prototype;return n.dehanceValue=function(e){return void 0!==this.dehancer?this.dehancer(e):e},n.set=function(e){this.value_;if((e=this.prepareNewValue_(e))!==pt.UNCHANGED){0,this.setNewValue_(e)}},n.prepareNewValue_=function(e){if(nt(this),pn(this)){var t=mn(this,{object:this,type:xn,newValue:e});if(!t)return pt.UNCHANGED;e=t.newValue}return e=this.enhancer(e,this.value_,this.name_),this.equals(this.value_,e)?pt.UNCHANGED:e},n.setNewValue_=function(e){var t=this.value_;this.value_=e,this.reportChanged(),gn(this)&&bn(this,{type:xn,object:this,newValue:e,oldValue:t})},n.get=function(){return this.reportObserved(),this.dehanceValue(this.value_)},n.intercept_=function(e){return vn(this,e)},n.observe_=function(e,t){return t&&e({observableKind:"value",debugObjectName:this.name_,object:this,type:xn,newValue:this.value_,oldValue:void 0}),yn(this,e)},n.raw=function(){return this.value_},n.toJSON=function(){return this.get()},n.toString=function(){return this.name_+"["+this.value_+"]"},n.valueOf=function(){return j(this.get())},n[Re]=function(){return this.valueOf()},t}($),Qe=x("ObservableValue",qe);Ke=Symbol.toPrimitive;var Ge,Xe,Ye=function(){function e(e){this.dependenciesState_=Ge.NOT_TRACKING_,this.observing_=[],this.newObserving_=null,this.isBeingObserved_=!1,this.isPendingUnobservation_=!1,this.observers_=new Set,this.diffValue_=0,this.runId_=0,this.lastAccessedBy_=0,this.lowestObserverState_=Ge.UP_TO_DATE_,this.unboundDepsCount_=0,this.value_=new Ze(null),this.name_=void 0,this.triggeredBy_=void 0,this.isComputing_=!1,this.isRunningSetter_=!1,this.derivation=void 0,this.setter_=void 0,this.isTracing_=Xe.NONE,this.scope_=void 0,this.equals_=void 0,this.requiresReaction_=void 0,this.keepAlive_=void 0,this.onBOL=void 0,this.onBUOL=void 0,e.get||t(31),this.derivation=e.get,this.name_=e.name||"ComputedValue",e.set&&(this.setter_=Fe("ComputedValue-setter",e.set)),this.equals_=e.equals||(e.compareStructural||e.struct?q.structural:q.default),this.scope_=e.context,this.requiresReaction_=e.requiresReaction,this.keepAlive_=!!e.keepAlive}var n=e.prototype;return n.onBecomeStale_=function(){!function(e){if(e.lowestObserverState_!==Ge.UP_TO_DATE_)return;e.lowestObserverState_=Ge.POSSIBLY_STALE_,e.observers_.forEach((function(e){e.dependenciesState_===Ge.UP_TO_DATE_&&(e.dependenciesState_=Ge.POSSIBLY_STALE_,e.onBecomeStale_())}))}(this)},n.onBO=function(){this.onBOL&&this.onBOL.forEach((function(e){return e()}))},n.onBUO=function(){this.onBUOL&&this.onBUOL.forEach((function(e){return e()}))},n.get=function(){if(this.isComputing_&&t(32,this.name_,this.derivation),0!==pt.inBatch||0!==this.observers_.size||this.keepAlive_){if(_t(this),tt(this)){var e=pt.trackingContext;this.keepAlive_&&!e&&(pt.trackingContext=this),this.trackAndCompute()&&function(e){if(e.lowestObserverState_===Ge.STALE_)return;e.lowestObserverState_=Ge.STALE_,e.observers_.forEach((function(t){t.dependenciesState_===Ge.POSSIBLY_STALE_?t.dependenciesState_=Ge.STALE_:t.dependenciesState_===Ge.UP_TO_DATE_&&(e.lowestObserverState_=Ge.UP_TO_DATE_)}))}(this),pt.trackingContext=e}}else tt(this)&&(this.warnAboutUntrackedRead_(),yt(),this.value_=this.computeValue_(!1),bt());var n=this.value_;if(et(n))throw n.cause;return n},n.set=function(e){if(this.setter_){this.isRunningSetter_&&t(33,this.name_),this.isRunningSetter_=!0;try{this.setter_.call(this.scope_,e)}finally{this.isRunningSetter_=!1}}else t(34,this.name_)},n.trackAndCompute=function(){var e=this.value_,t=this.dependenciesState_===Ge.NOT_TRACKING_,n=this.computeValue_(!0),r=t||et(e)||et(n)||!this.equals_(e,n);return r&&(this.value_=n),r},n.computeValue_=function(e){this.isComputing_=!0;var t,n=$e(!1);if(e)t=rt(this,this.derivation,this.scope_);else if(!0===pt.disableErrorBoundaries)t=this.derivation.call(this.scope_);else try{t=this.derivation.call(this.scope_)}catch(e){t=new Ze(e)}return We(n),this.isComputing_=!1,t},n.suspend_=function(){this.keepAlive_||(it(this),this.value_=void 0)},n.observe_=function(e,t){var n=this,r=!0,i=void 0;return Ut((function(){var a=n.get();if(!r||t){var o=ot();e({observableKind:"computed",debugObjectName:n.name_,type:xn,object:n,newValue:a,oldValue:i}),lt(o)}r=!1,i=a}))},n.warnAboutUntrackedRead_=function(){},n.toString=function(){return this.name_+"["+this.derivation.toString()+"]"},n.valueOf=function(){return j(this.get())},n[Ke]=function(){return this.valueOf()},e}(),Je=x("ComputedValue",Ye);!function(e){e[e.NOT_TRACKING_=-1]="NOT_TRACKING_",e[e.UP_TO_DATE_=0]="UP_TO_DATE_",e[e.POSSIBLY_STALE_=1]="POSSIBLY_STALE_",e[e.STALE_=2]="STALE_"}(Ge||(Ge={})),function(e){e[e.NONE=0]="NONE",e[e.LOG=1]="LOG",e[e.BREAK=2]="BREAK"}(Xe||(Xe={}));var Ze=function(e){this.cause=void 0,this.cause=e};function et(e){return e instanceof Ze}function tt(e){switch(e.dependenciesState_){case Ge.UP_TO_DATE_:return!1;case Ge.NOT_TRACKING_:case Ge.STALE_:return!0;case Ge.POSSIBLY_STALE_:for(var t=st(!0),n=ot(),r=e.observing_,i=r.length,a=0;a<i;a++){var o=r[a];if(Je(o)){if(pt.disableErrorBoundaries)o.get();else try{o.get()}catch(e){return lt(n),ut(t),!0}if(e.dependenciesState_===Ge.STALE_)return lt(n),ut(t),!0}}return ct(e),lt(n),ut(t),!1}}function nt(e){}function rt(e,t,n){var r=st(!0);ct(e),e.newObserving_=new Array(e.observing_.length+100),e.unboundDepsCount_=0,e.runId_=++pt.runId;var i,a=pt.trackingDerivation;if(pt.trackingDerivation=e,pt.inBatch++,!0===pt.disableErrorBoundaries)i=t.call(n);else try{i=t.call(n)}catch(e){i=new Ze(e)}return pt.inBatch--,pt.trackingDerivation=a,function(e){for(var t=e.observing_,n=e.observing_=e.newObserving_,r=Ge.UP_TO_DATE_,i=0,a=e.unboundDepsCount_,o=0;o<a;o++){var l=n[o];0===l.diffValue_&&(l.diffValue_=1,i!==o&&(n[i]=l),i++),l.dependenciesState_>r&&(r=l.dependenciesState_)}n.length=i,e.newObserving_=null,a=t.length;for(;a--;){var s=t[a];0===s.diffValue_&&mt(s,e),s.diffValue_=0}for(;i--;){var u=n[i];1===u.diffValue_&&(u.diffValue_=0,vt(u,e))}r!==Ge.UP_TO_DATE_&&(e.dependenciesState_=r,e.onBecomeStale_())}(e),ut(r),i}function it(e){var t=e.observing_;e.observing_=[];for(var n=t.length;n--;)mt(t[n],e);e.dependenciesState_=Ge.NOT_TRACKING_}function at(e){var t=ot();try{return e()}finally{lt(t)}}function ot(){var e=pt.trackingDerivation;return pt.trackingDerivation=null,e}function lt(e){pt.trackingDerivation=e}function st(e){var t=pt.allowStateReads;return pt.allowStateReads=e,t}function ut(e){pt.allowStateReads=e}function ct(e){if(e.dependenciesState_!==Ge.UP_TO_DATE_){e.dependenciesState_=Ge.UP_TO_DATE_;for(var t=e.observing_,n=t.length;n--;)t[n].lowestObserverState_=Ge.UP_TO_DATE_}}var dt=function(){this.version=6,this.UNCHANGED={},this.trackingDerivation=null,this.trackingContext=null,this.runId=0,this.mobxGuid=0,this.inBatch=0,this.pendingUnobservations=[],this.pendingReactions=[],this.isRunningReactions=!1,this.allowStateChanges=!1,this.allowStateReads=!0,this.enforceActions=!0,this.spyListeners=[],this.globalReactionErrorHandlers=[],this.computedRequiresReaction=!1,this.reactionRequiresObservable=!1,this.observableRequiresReaction=!1,this.disableErrorBoundaries=!1,this.suppressReactionErrors=!1,this.useProxies=!0,this.verifyProxies=!1,this.safeDescriptors=!0},ft=!0,ht=!1,pt=function(){var e=i();return e.__mobxInstanceCount>0&&!e.__mobxGlobals&&(ft=!1),e.__mobxGlobals&&e.__mobxGlobals.version!==(new dt).version&&(ft=!1),ft?e.__mobxGlobals?(e.__mobxInstanceCount+=1,e.__mobxGlobals.UNCHANGED||(e.__mobxGlobals.UNCHANGED={}),e.__mobxGlobals):(e.__mobxInstanceCount=1,e.__mobxGlobals=new dt):(setTimeout((function(){ht||t(35)}),1),new dt)}();function vt(e,t){e.observers_.add(t),e.lowestObserverState_>t.dependenciesState_&&(e.lowestObserverState_=t.dependenciesState_)}function mt(e,t){e.observers_.delete(t),0===e.observers_.size&&gt(e)}function gt(e){!1===e.isPendingUnobservation_&&(e.isPendingUnobservation_=!0,pt.pendingUnobservations.push(e))}function yt(){pt.inBatch++}function bt(){if(0==--pt.inBatch){zt();for(var e=pt.pendingUnobservations,t=0;t<e.length;t++){var n=e[t];n.isPendingUnobservation_=!1,0===n.observers_.size&&(n.isBeingObserved_&&(n.isBeingObserved_=!1,n.onBUO()),n instanceof Ye&&n.suspend_())}pt.pendingUnobservations=[]}}function _t(e){var t=pt.trackingDerivation;return null!==t?(t.runId_!==e.lastAccessedBy_&&(e.lastAccessedBy_=t.runId_,t.newObserving_[t.unboundDepsCount_++]=e,!e.isBeingObserved_&&pt.trackingContext&&(e.isBeingObserved_=!0,e.onBO())),e.isBeingObserved_):(0===e.observers_.size&&pt.inBatch>0&&gt(e),!1)}function wt(e){e.lowestObserverState_!==Ge.STALE_&&(e.lowestObserverState_=Ge.STALE_,e.observers_.forEach((function(e){e.dependenciesState_===Ge.UP_TO_DATE_&&e.onBecomeStale_(),e.dependenciesState_=Ge.STALE_})))}var kt=function(){function e(e,t,n,r){void 0===e&&(e="Reaction"),this.name_=void 0,this.onInvalidate_=void 0,this.errorHandler_=void 0,this.requiresObservable_=void 0,this.observing_=[],this.newObserving_=[],this.dependenciesState_=Ge.NOT_TRACKING_,this.diffValue_=0,this.runId_=0,this.unboundDepsCount_=0,this.isDisposed_=!1,this.isScheduled_=!1,this.isTrackPending_=!1,this.isRunning_=!1,this.isTracing_=Xe.NONE,this.name_=e,this.onInvalidate_=t,this.errorHandler_=n,this.requiresObservable_=r}var t=e.prototype;return t.onBecomeStale_=function(){this.schedule_()},t.schedule_=function(){this.isScheduled_||(this.isScheduled_=!0,pt.pendingReactions.push(this),zt())},t.isScheduled=function(){return this.isScheduled_},t.runReaction_=function(){if(!this.isDisposed_){yt(),this.isScheduled_=!1;var e=pt.trackingContext;if(pt.trackingContext=this,tt(this)){this.isTrackPending_=!0;try{this.onInvalidate_()}catch(e){this.reportExceptionInDerivation_(e)}}pt.trackingContext=e,bt()}},t.track=function(e){if(!this.isDisposed_){yt();0,this.isRunning_=!0;var t=pt.trackingContext;pt.trackingContext=this;var n=rt(this,e,void 0);pt.trackingContext=t,this.isRunning_=!1,this.isTrackPending_=!1,this.isDisposed_&&it(this),et(n)&&this.reportExceptionInDerivation_(n.cause),bt()}},t.reportExceptionInDerivation_=function(e){var t=this;if(this.errorHandler_)this.errorHandler_(e,this);else{if(pt.disableErrorBoundaries)throw e;var n="[mobx] uncaught error in '"+this+"'";pt.suppressReactionErrors||console.error(n,e),pt.globalReactionErrorHandlers.forEach((function(n){return n(e,t)}))}},t.dispose=function(){this.isDisposed_||(this.isDisposed_=!0,this.isRunning_||(yt(),it(this),bt()))},t.getDisposer_=function(e){var t=this,n=function n(){t.dispose(),null==e||null==e.removeEventListener||e.removeEventListener("abort",n)};return null==e||null==e.addEventListener||e.addEventListener("abort",n),n[H]=this,n},t.toString=function(){return"Reaction["+this.name_+"]"},t.trace=function(e){void 0===e&&(e=!1)},e}();var xt=100,St=function(e){return e()};function zt(){pt.inBatch>0||pt.isRunningReactions||St(Pt)}function Pt(){pt.isRunningReactions=!0;for(var e=pt.pendingReactions,t=0;e.length>0;){++t===xt&&(console.error("[mobx] cycle in reaction: "+e[0]),e.splice(0));for(var n=e.splice(0),r=0,i=n.length;r<i;r++)n[r].runReaction_()}pt.isRunningReactions=!1}var Ct=x("Reaction",kt);var jt="action",Ot="autoAction",Et="<unnamed action>",Nt=Y(jt),Tt=Y("action.bound",{bound:!0}),Lt=Y(Ot,{autoAction:!0}),It=Y("autoAction.bound",{autoAction:!0,bound:!0});function At(e){return function(t,n){return m(t)?Fe(t.name||Et,t,e):m(n)?Fe(t,n,e):F(n)?(e?Lt:Nt).decorate_20223_(t,n):g(n)?B(t,n,e?Lt:Nt):g(t)?V(Y(e?Ot:jt,{name:t,autoAction:e})):void 0}}var Mt=At(!1);Object.assign(Mt,Nt);var Rt=At(!0);function Dt(e){return m(e)&&!0===e.isMobxAction}function Ut(e,t){var n,r,i,a,o;void 0===t&&(t=c);var l,s=null!=(n=null==(r=t)?void 0:r.name)?n:"Autorun";if(!t.scheduler&&!t.delay)l=new kt(s,(function(){this.track(f)}),t.onError,t.requiresObservable);else{var u=Bt(t),d=!1;l=new kt(s,(function(){d||(d=!0,u((function(){d=!1,l.isDisposed_||l.track(f)})))}),t.onError,t.requiresObservable)}function f(){e(l)}return null!=(i=t)&&null!=(a=i.signal)&&a.aborted||l.schedule_(),l.getDisposer_(null==(o=t)?void 0:o.signal)}Object.assign(Rt,Lt),Mt.bound=V(Tt),Rt.bound=V(It);var Vt=function(e){return e()};function Bt(e){return e.scheduler?e.scheduler:e.delay?function(t){return setTimeout(t,e.delay)}:Vt}var Ft="onBO",Ht="onBUO";function $t(e,t,n){return Wt(Ht,e,t,n)}function Wt(e,t,n,r){var i="function"==typeof r?fr(t,n):fr(t),a=m(r)?r:n,o=e+"L";return i[o]?i[o].add(a):i[o]=new Set([a]),function(){var e=i[o];e&&(e.delete(a),0===e.size&&delete i[o])}}var Kt="never",qt="always",Qt="observed";function Gt(e){!0===e.isolateGlobalState&&function(){if((pt.pendingReactions.length||pt.inBatch||pt.isRunningReactions)&&t(36),ht=!0,ft){var e=i();0==--e.__mobxInstanceCount&&(e.__mobxGlobals=void 0),pt=new dt}}();var n,r,a=e.useProxies,o=e.enforceActions;if(void 0!==a&&(pt.useProxies=a===qt||a!==Kt&&"undefined"!=typeof Proxy),"ifavailable"===a&&(pt.verifyProxies=!0),void 0!==o){var l=o===qt?qt:o===Qt;pt.enforceActions=l,pt.allowStateChanges=!0!==l&&l!==qt}["computedRequiresReaction","reactionRequiresObservable","observableRequiresReaction","disableErrorBoundaries","safeDescriptors"].forEach((function(t){t in e&&(pt[t]=!!e[t])})),pt.allowStateReads=!pt.observableRequiresReaction,e.reactionScheduler&&(n=e.reactionScheduler,r=St,St=function(e){return n((function(){return r(e)}))})}function Xt(e,t,n,r){var i=E(t);return vr((function(){var t=Gn(e,r)[H];C(i).forEach((function(e){t.extend_(e,i[e],!n||(!(e in n)||n[e]))}))})),e}function Yt(e,t){return Jt(fr(e,t))}function Jt(e){var t,n={name:e.name_};return e.observing_&&e.observing_.length>0&&(n.dependencies=(t=e.observing_,Array.from(new Set(t))).map(Jt)),n}var Zt=0;function en(){this.message="FLOW_CANCELLED"}en.prototype=Object.create(Error.prototype);var tn=ne("flow"),nn=ne("flow.bound",{bound:!0}),rn=Object.assign((function(e,t){if(F(t))return tn.decorate_20223_(e,t);if(g(t))return B(e,t,tn);var n=e,r=n.name||"<unnamed flow>",i=function(){var e,t=arguments,i=++Zt,a=Mt(r+" - runid: "+i+" - init",n).apply(this,t),o=void 0,l=new Promise((function(t,n){var l=0;function s(e){var t;o=void 0;try{t=Mt(r+" - runid: "+i+" - yield "+l++,a.next).call(a,e)}catch(e){return n(e)}c(t)}function u(e){var t;o=void 0;try{t=Mt(r+" - runid: "+i+" - yield "+l++,a.throw).call(a,e)}catch(e){return n(e)}c(t)}function c(e){if(!m(null==e?void 0:e.then))return e.done?t(e.value):(o=Promise.resolve(e.value)).then(s,u);e.then(c,n)}e=n,s(void 0)}));return l.cancel=Mt(r+" - runid: "+i+" - cancel",(function(){try{o&&an(o);var t=a.return(void 0),n=Promise.resolve(t.value);n.then(v,v),an(n),e(new en)}catch(t){e(t)}})),l};return i.isMobXFlow=!0,i}),tn);function an(e){m(e.cancel)&&e.cancel()}function on(e){return!0===(null==e?void 0:e.isMobXFlow)}function ln(e,t){return!!e&&(void 0!==t?!!Jn(e)&&e[H].values_.has(t):Jn(e)||!!e[H]||W(e)||Ct(e)||Je(e))}function sn(e){return ln(e)}function un(e,t,n){return e.set(t,n),n}function cn(e,n){if(null==e||"object"!=typeof e||e instanceof Date||!sn(e))return e;if(Qe(e)||Je(e))return cn(e.get(),n);if(n.has(e))return n.get(e);if(An(e)){var r=un(n,e,new Array(e.length));return e.forEach((function(e,t){r[t]=cn(e,n)})),r}if(Wn(e)){var i=un(n,e,new Set);return e.forEach((function(e){i.add(cn(e,n))})),i}if(Fn(e)){var a=un(n,e,new Map);return e.forEach((function(e,t){a.set(t,cn(e,n))})),a}var o=un(n,e,{});return function(e){if(Jn(e))return e[H].ownKeys_();t(38)}(e).forEach((function(t){s.propertyIsEnumerable.call(e,t)&&(o[t]=cn(e[t],n))})),o}function dn(e,t){void 0===t&&(t=void 0),yt();try{return e.apply(t)}finally{bt()}}function fn(e){return e[H]}rn.bound=V(nn);var hn={has:function(e,t){return fn(e).has_(t)},get:function(e,t){return fn(e).get_(t)},set:function(e,t,n){var r;return!!g(t)&&(null==(r=fn(e).set_(t,n,!0))||r)},deleteProperty:function(e,t){var n;return!!g(t)&&(null==(n=fn(e).delete_(t,!0))||n)},defineProperty:function(e,t,n){var r;return null==(r=fn(e).defineProperty_(t,n))||r},ownKeys:function(e){return fn(e).ownKeys_()},preventExtensions:function(e){t(13)}};function pn(e){return void 0!==e.interceptors_&&e.interceptors_.length>0}function vn(e,t){var n=e.interceptors_||(e.interceptors_=[]);return n.push(t),p((function(){var e=n.indexOf(t);-1!==e&&n.splice(e,1)}))}function mn(e,n){var r=ot();try{for(var i=[].concat(e.interceptors_||[]),a=0,o=i.length;a<o&&((n=i[a](n))&&!n.type&&t(14),n);a++);return n}finally{lt(r)}}function gn(e){return void 0!==e.changeListeners_&&e.changeListeners_.length>0}function yn(e,t){var n=e.changeListeners_||(e.changeListeners_=[]);return n.push(t),p((function(){var e=n.indexOf(t);-1!==e&&n.splice(e,1)}))}function bn(e,t){var n=ot(),r=e.changeListeners_;if(r){for(var i=0,a=(r=r.slice()).length;i<a;i++)r[i](t);lt(n)}}var _n=Symbol("mobx-keys");function wn(e,t,n){return b(e)?Xt(e,e,t,n):(vr((function(){var r=Gn(e,n)[H];if(!e[_n]){var i=Object.getPrototypeOf(e),a=new Set([].concat(C(e),C(i)));a.delete("constructor"),a.delete(H),w(i,_n,a)}e[_n].forEach((function(e){return r.make_(e,!t||(!(e in t)||t[e]))}))})),e)}var kn="splice",xn="update",Sn={get:function(e,t){var n=e[H];return t===H?n:"length"===t?n.getArrayLength_():"string"!=typeof t||isNaN(t)?O(Cn,t)?Cn[t]:e[t]:n.get_(parseInt(t))},set:function(e,t,n){var r=e[H];return"length"===t&&r.setArrayLength_(n),"symbol"==typeof t||isNaN(t)?e[t]=n:r.set_(parseInt(t),n),!0},preventExtensions:function(){t(15)}},zn=function(){function e(e,t,n,r){void 0===e&&(e="ObservableArray"),this.owned_=void 0,this.legacyMode_=void 0,this.atom_=void 0,this.values_=[],this.interceptors_=void 0,this.changeListeners_=void 0,this.enhancer_=void 0,this.dehancer=void 0,this.proxy_=void 0,this.lastKnownLength_=0,this.owned_=n,this.legacyMode_=r,this.atom_=new $(e),this.enhancer_=function(e,n){return t(e,n,"ObservableArray[..]")}}var n=e.prototype;return n.dehanceValue_=function(e){return void 0!==this.dehancer?this.dehancer(e):e},n.dehanceValues_=function(e){return void 0!==this.dehancer&&e.length>0?e.map(this.dehancer):e},n.intercept_=function(e){return vn(this,e)},n.observe_=function(e,t){return void 0===t&&(t=!1),t&&e({observableKind:"array",object:this.proxy_,debugObjectName:this.atom_.name_,type:"splice",index:0,added:this.values_.slice(),addedCount:this.values_.length,removed:[],removedCount:0}),yn(this,e)},n.getArrayLength_=function(){return this.atom_.reportObserved(),this.values_.length},n.setArrayLength_=function(e){("number"!=typeof e||isNaN(e)||e<0)&&t("Out of range: "+e);var n=this.values_.length;if(e!==n)if(e>n){for(var r=new Array(e-n),i=0;i<e-n;i++)r[i]=void 0;this.spliceWithArray_(n,0,r)}else this.spliceWithArray_(e,n-e)},n.updateArrayLength_=function(e,n){e!==this.lastKnownLength_&&t(16),this.lastKnownLength_+=n,this.legacyMode_&&n>0&&cr(e+n+1)},n.spliceWithArray_=function(e,t,n){var r=this;this.atom_;var i=this.values_.length;if(void 0===e?e=0:e>i?e=i:e<0&&(e=Math.max(0,i+e)),t=1===arguments.length?i-e:null==t?0:Math.max(0,Math.min(t,i-e)),void 0===n&&(n=u),pn(this)){var a=mn(this,{object:this.proxy_,type:kn,index:e,removedCount:t,added:n});if(!a)return u;t=a.removedCount,n=a.added}if(n=0===n.length?n:n.map((function(e){return r.enhancer_(e,void 0)})),this.legacyMode_){var o=n.length-t;this.updateArrayLength_(i,o)}var l=this.spliceItemsIntoValues_(e,t,n);return 0===t&&0===n.length||this.notifyArraySplice_(e,n,l),this.dehanceValues_(l)},n.spliceItemsIntoValues_=function(e,t,n){var r;if(n.length<1e4)return(r=this.values_).splice.apply(r,[e,t].concat(n));var i=this.values_.slice(e,e+t),a=this.values_.slice(e+t);this.values_.length+=n.length-t;for(var o=0;o<n.length;o++)this.values_[e+o]=n[o];for(var l=0;l<a.length;l++)this.values_[e+n.length+l]=a[l];return i},n.notifyArrayChildUpdate_=function(e,t,n){var r=!this.owned_&&!1,i=gn(this),a=i||r?{observableKind:"array",object:this.proxy_,type:xn,debugObjectName:this.atom_.name_,index:e,newValue:t,oldValue:n}:null;this.atom_.reportChanged(),i&&bn(this,a)},n.notifyArraySplice_=function(e,t,n){var r=!this.owned_&&!1,i=gn(this),a=i||r?{observableKind:"array",object:this.proxy_,debugObjectName:this.atom_.name_,type:kn,index:e,removed:n,added:t,removedCount:n.length,addedCount:t.length}:null;this.atom_.reportChanged(),i&&bn(this,a)},n.get_=function(e){if(!(this.legacyMode_&&e>=this.values_.length))return this.atom_.reportObserved(),this.dehanceValue_(this.values_[e]);console.warn("[mobx] Out of bounds read: "+e)},n.set_=function(e,n){var r=this.values_;if(this.legacyMode_&&e>r.length&&t(17,e,r.length),e<r.length){this.atom_;var i=r[e];if(pn(this)){var a=mn(this,{type:xn,object:this.proxy_,index:e,newValue:n});if(!a)return;n=a.newValue}(n=this.enhancer_(n,i))!==i&&(r[e]=n,this.notifyArrayChildUpdate_(e,n,i))}else{for(var o=new Array(e+1-r.length),l=0;l<o.length-1;l++)o[l]=void 0;o[o.length-1]=n,this.spliceWithArray_(r.length,0,o)}},e}();function Pn(e,t,n,r){return void 0===n&&(n="ObservableArray"),void 0===r&&(r=!1),h(),vr((function(){var i=new zn(n,t,r,!1);k(i.values_,H,i);var a=new Proxy(i.values_,Sn);return i.proxy_=a,e&&e.length&&i.spliceWithArray_(0,0,e),a}))}var Cn={clear:function(){return this.splice(0)},replace:function(e){var t=this[H];return t.spliceWithArray_(0,t.values_.length,e)},toJSON:function(){return this.slice()},splice:function(e,t){for(var n=arguments.length,r=new Array(n>2?n-2:0),i=2;i<n;i++)r[i-2]=arguments[i];var a=this[H];switch(arguments.length){case 0:return[];case 1:return a.spliceWithArray_(e);case 2:return a.spliceWithArray_(e,t)}return a.spliceWithArray_(e,t,r)},spliceWithArray:function(e,t,n){return this[H].spliceWithArray_(e,t,n)},push:function(){for(var e=this[H],t=arguments.length,n=new Array(t),r=0;r<t;r++)n[r]=arguments[r];return e.spliceWithArray_(e.values_.length,0,n),e.values_.length},pop:function(){return this.splice(Math.max(this[H].values_.length-1,0),1)[0]},shift:function(){return this.splice(0,1)[0]},unshift:function(){for(var e=this[H],t=arguments.length,n=new Array(t),r=0;r<t;r++)n[r]=arguments[r];return e.spliceWithArray_(0,0,n),e.values_.length},reverse:function(){return pt.trackingDerivation&&t(37,"reverse"),this.replace(this.slice().reverse()),this},sort:function(){pt.trackingDerivation&&t(37,"sort");var e=this.slice();return e.sort.apply(e,arguments),this.replace(e),this},remove:function(e){var t=this[H],n=t.dehanceValues_(t.values_).indexOf(e);return n>-1&&(this.splice(n,1),!0)}};function jn(e,t){"function"==typeof Array.prototype[e]&&(Cn[e]=t(e))}function On(e){return function(){var t=this[H];t.atom_.reportObserved();var n=t.dehanceValues_(t.values_);return n[e].apply(n,arguments)}}function En(e){return function(t,n){var r=this,i=this[H];return i.atom_.reportObserved(),i.dehanceValues_(i.values_)[e]((function(e,i){return t.call(n,e,i,r)}))}}function Nn(e){return function(){var t=this,n=this[H];n.atom_.reportObserved();var r=n.dehanceValues_(n.values_),i=arguments[0];return arguments[0]=function(e,n,r){return i(e,n,r,t)},r[e].apply(r,arguments)}}jn("at",On),jn("concat",On),jn("flat",On),jn("includes",On),jn("indexOf",On),jn("join",On),jn("lastIndexOf",On),jn("slice",On),jn("toString",On),jn("toLocaleString",On),jn("toSorted",On),jn("toSpliced",On),jn("with",On),jn("every",En),jn("filter",En),jn("find",En),jn("findIndex",En),jn("findLast",En),jn("findLastIndex",En),jn("flatMap",En),jn("forEach",En),jn("map",En),jn("some",En),jn("toReversed",En),jn("reduce",Nn),jn("reduceRight",Nn);var Tn,Ln,In=x("ObservableArrayAdministration",zn);function An(e){return y(e)&&In(e[H])}var Mn={},Rn="add",Dn="delete";Tn=Symbol.iterator,Ln=Symbol.toStringTag;var Un,Vn,Bn=function(){function e(e,n,r){var i=this;void 0===n&&(n=Q),void 0===r&&(r="ObservableMap"),this.enhancer_=void 0,this.name_=void 0,this[H]=Mn,this.data_=void 0,this.hasMap_=void 0,this.keysAtom_=void 0,this.interceptors_=void 0,this.changeListeners_=void 0,this.dehancer=void 0,this.enhancer_=n,this.name_=r,m(Map)||t(18),vr((function(){i.keysAtom_=K("ObservableMap.keys()"),i.data_=new Map,i.hasMap_=new Map,e&&i.merge(e)}))}var n=e.prototype;return n.has_=function(e){return this.data_.has(e)},n.has=function(e){var t=this;if(!pt.trackingDerivation)return this.has_(e);var n=this.hasMap_.get(e);if(!n){var r=n=new qe(this.has_(e),G,"ObservableMap.key?",!1);this.hasMap_.set(e,r),$t(r,(function(){return t.hasMap_.delete(e)}))}return n.get()},n.set=function(e,t){var n=this.has_(e);if(pn(this)){var r=mn(this,{type:n?xn:Rn,object:this,newValue:t,name:e});if(!r)return this;t=r.newValue}return n?this.updateValue_(e,t):this.addValue_(e,t),this},n.delete=function(e){var t=this;if((this.keysAtom_,pn(this))&&!mn(this,{type:Dn,object:this,name:e}))return!1;if(this.has_(e)){var n=gn(this),r=n?{observableKind:"map",debugObjectName:this.name_,type:Dn,object:this,oldValue:this.data_.get(e).value_,name:e}:null;return dn((function(){var n;t.keysAtom_.reportChanged(),null==(n=t.hasMap_.get(e))||n.setNewValue_(!1),t.data_.get(e).setNewValue_(void 0),t.data_.delete(e)})),n&&bn(this,r),!0}return!1},n.updateValue_=function(e,t){var n=this.data_.get(e);if((t=n.prepareNewValue_(t))!==pt.UNCHANGED){var r=gn(this),i=r?{observableKind:"map",debugObjectName:this.name_,type:xn,object:this,oldValue:n.value_,name:e,newValue:t}:null;0,n.setNewValue_(t),r&&bn(this,i)}},n.addValue_=function(e,t){var n=this;this.keysAtom_,dn((function(){var r,i=new qe(t,n.enhancer_,"ObservableMap.key",!1);n.data_.set(e,i),t=i.value_,null==(r=n.hasMap_.get(e))||r.setNewValue_(!0),n.keysAtom_.reportChanged()}));var r=gn(this),i=r?{observableKind:"map",debugObjectName:this.name_,type:Rn,object:this,name:e,newValue:t}:null;r&&bn(this,i)},n.get=function(e){return this.has(e)?this.dehanceValue_(this.data_.get(e).get()):this.dehanceValue_(void 0)},n.dehanceValue_=function(e){return void 0!==this.dehancer?this.dehancer(e):e},n.keys=function(){return this.keysAtom_.reportObserved(),this.data_.keys()},n.values=function(){var e=this,t=this.keys();return _r({next:function(){var n=t.next(),r=n.done,i=n.value;return{done:r,value:r?void 0:e.get(i)}}})},n.entries=function(){var e=this,t=this.keys();return _r({next:function(){var n=t.next(),r=n.done,i=n.value;return{done:r,value:r?void 0:[i,e.get(i)]}}})},n[Tn]=function(){return this.entries()},n.forEach=function(e,t){for(var n,r=D(this);!(n=r()).done;){var i=n.value,a=i[0],o=i[1];e.call(t,o,a,this)}},n.merge=function(e){var n=this;return Fn(e)&&(e=new Map(e)),dn((function(){b(e)?function(e){var t=Object.keys(e);if(!P)return t;var n=Object.getOwnPropertySymbols(e);return n.length?[].concat(t,n.filter((function(t){return s.propertyIsEnumerable.call(e,t)}))):t}(e).forEach((function(t){return n.set(t,e[t])})):Array.isArray(e)?e.forEach((function(e){var t=e[0],r=e[1];return n.set(t,r)})):S(e)?(e.constructor!==Map&&t(19,e),e.forEach((function(e,t){return n.set(t,e)}))):null!=e&&t(20,e)})),this},n.clear=function(){var e=this;dn((function(){at((function(){for(var t,n=D(e.keys());!(t=n()).done;){var r=t.value;e.delete(r)}}))}))},n.replace=function(e){var n=this;return dn((function(){for(var r,i=function(e){if(S(e)||Fn(e))return e;if(Array.isArray(e))return new Map(e);if(b(e)){var n=new Map;for(var r in e)n.set(r,e[r]);return n}return t(21,e)}(e),a=new Map,o=!1,l=D(n.data_.keys());!(r=l()).done;){var s=r.value;if(!i.has(s))if(n.delete(s))o=!0;else{var u=n.data_.get(s);a.set(s,u)}}for(var c,d=D(i.entries());!(c=d()).done;){var f=c.value,h=f[0],p=f[1],v=n.data_.has(h);if(n.set(h,p),n.data_.has(h)){var m=n.data_.get(h);a.set(h,m),v||(o=!0)}}if(!o)if(n.data_.size!==a.size)n.keysAtom_.reportChanged();else for(var g=n.data_.keys(),y=a.keys(),_=g.next(),w=y.next();!_.done;){if(_.value!==w.value){n.keysAtom_.reportChanged();break}_=g.next(),w=y.next()}n.data_=a})),this},n.toString=function(){return"[object ObservableMap]"},n.toJSON=function(){return Array.from(this)},n.observe_=function(e,t){return yn(this,e)},n.intercept_=function(e){return vn(this,e)},T(e,[{key:"size",get:function(){return this.keysAtom_.reportObserved(),this.data_.size}},{key:Ln,get:function(){return"Map"}}]),e}(),Fn=x("ObservableMap",Bn);var Hn={};Un=Symbol.iterator,Vn=Symbol.toStringTag;var $n=function(){function e(e,n,r){var i=this;void 0===n&&(n=Q),void 0===r&&(r="ObservableSet"),this.name_=void 0,this[H]=Hn,this.data_=new Set,this.atom_=void 0,this.changeListeners_=void 0,this.interceptors_=void 0,this.dehancer=void 0,this.enhancer_=void 0,this.name_=r,m(Set)||t(22),this.enhancer_=function(e,t){return n(e,t,r)},vr((function(){i.atom_=K(i.name_),e&&i.replace(e)}))}var n=e.prototype;return n.dehanceValue_=function(e){return void 0!==this.dehancer?this.dehancer(e):e},n.clear=function(){var e=this;dn((function(){at((function(){for(var t,n=D(e.data_.values());!(t=n()).done;){var r=t.value;e.delete(r)}}))}))},n.forEach=function(e,t){for(var n,r=D(this);!(n=r()).done;){var i=n.value;e.call(t,i,i,this)}},n.add=function(e){var t=this;if((this.atom_,pn(this))&&!mn(this,{type:Rn,object:this,newValue:e}))return this;if(!this.has(e)){dn((function(){t.data_.add(t.enhancer_(e,void 0)),t.atom_.reportChanged()}));var n=!1,r=gn(this),i=r?{observableKind:"set",debugObjectName:this.name_,type:Rn,object:this,newValue:e}:null;n,r&&bn(this,i)}return this},n.delete=function(e){var t=this;if(pn(this)&&!mn(this,{type:Dn,object:this,oldValue:e}))return!1;if(this.has(e)){var n=gn(this),r=n?{observableKind:"set",debugObjectName:this.name_,type:Dn,object:this,oldValue:e}:null;return dn((function(){t.atom_.reportChanged(),t.data_.delete(e)})),n&&bn(this,r),!0}return!1},n.has=function(e){return this.atom_.reportObserved(),this.data_.has(this.dehanceValue_(e))},n.entries=function(){var e=0,t=Array.from(this.keys()),n=Array.from(this.values());return _r({next:function(){var r=e;return e+=1,r<n.length?{value:[t[r],n[r]],done:!1}:{done:!0}}})},n.keys=function(){return this.values()},n.values=function(){this.atom_.reportObserved();var e=this,t=0,n=Array.from(this.data_.values());return _r({next:function(){return t<n.length?{value:e.dehanceValue_(n[t++]),done:!1}:{done:!0}}})},n.replace=function(e){var n=this;return Wn(e)&&(e=new Set(e)),dn((function(){Array.isArray(e)||z(e)?(n.clear(),e.forEach((function(e){return n.add(e)}))):null!=e&&t("Cannot initialize set from "+e)})),this},n.observe_=function(e,t){return yn(this,e)},n.intercept_=function(e){return vn(this,e)},n.toJSON=function(){return Array.from(this)},n.toString=function(){return"[object ObservableSet]"},n[Un]=function(){return this.values()},T(e,[{key:"size",get:function(){return this.atom_.reportObserved(),this.data_.size}},{key:Vn,get:function(){return"Set"}}]),e}(),Wn=x("ObservableSet",$n),Kn=Object.create(null),qn="remove",Qn=function(){function e(e,t,n,r){void 0===t&&(t=new Map),void 0===r&&(r=me),this.target_=void 0,this.values_=void 0,this.name_=void 0,this.defaultAnnotation_=void 0,this.keysAtom_=void 0,this.changeListeners_=void 0,this.interceptors_=void 0,this.proxy_=void 0,this.isPlainObject_=void 0,this.appliedAnnotations_=void 0,this.pendingKeys_=void 0,this.target_=e,this.values_=t,this.name_=n,this.defaultAnnotation_=r,this.keysAtom_=new $("ObservableObject.keys"),this.isPlainObject_=b(this.target_)}var n=e.prototype;return n.getObservablePropValue_=function(e){return this.values_.get(e).get()},n.setObservablePropValue_=function(e,t){var n=this.values_.get(e);if(n instanceof Ye)return n.set(t),!0;if(pn(this)){var r=mn(this,{type:xn,object:this.proxy_||this.target_,name:e,newValue:t});if(!r)return null;t=r.newValue}if((t=n.prepareNewValue_(t))!==pt.UNCHANGED){var i=gn(this),a=i?{type:xn,observableKind:"object",debugObjectName:this.name_,object:this.proxy_||this.target_,oldValue:n.value_,name:e,newValue:t}:null;0,n.setNewValue_(t),i&&bn(this,a)}return!0},n.get_=function(e){return pt.trackingDerivation&&!O(this.target_,e)&&this.has_(e),this.target_[e]},n.set_=function(e,t,n){return void 0===n&&(n=!1),O(this.target_,e)?this.values_.has(e)?this.setObservablePropValue_(e,t):n?Reflect.set(this.target_,e,t):(this.target_[e]=t,!0):this.extend_(e,{value:t,enumerable:!0,writable:!0,configurable:!0},this.defaultAnnotation_,n)},n.has_=function(e){if(!pt.trackingDerivation)return e in this.target_;this.pendingKeys_||(this.pendingKeys_=new Map);var t=this.pendingKeys_.get(e);return t||(t=new qe(e in this.target_,G,"ObservableObject.key?",!1),this.pendingKeys_.set(e,t)),t.get()},n.make_=function(e,n){if(!0===n&&(n=this.defaultAnnotation_),!1!==n){if(er(this,n,e),!(e in this.target_)){var r;if(null!=(r=this.target_[U])&&r[e])return;t(1,n.annotationType_,this.name_+"."+e.toString())}for(var i=this.target_;i&&i!==s;){var a=o(i,e);if(a){var l=n.make_(this,e,a,i);if(0===l)return;if(1===l)break}i=Object.getPrototypeOf(i)}Zn(this,n,e)}},n.extend_=function(e,t,n,r){if(void 0===r&&(r=!1),!0===n&&(n=this.defaultAnnotation_),!1===n)return this.defineProperty_(e,t,r);er(this,n,e);var i=n.extend_(this,e,t,r);return i&&Zn(this,n,e),i},n.defineProperty_=function(e,t,n){void 0===n&&(n=!1),this.keysAtom_;try{yt();var r=this.delete_(e);if(!r)return r;if(pn(this)){var i=mn(this,{object:this.proxy_||this.target_,name:e,type:Rn,newValue:t.value});if(!i)return null;var a=i.newValue;t.value!==a&&(t=L({},t,{value:a}))}if(n){if(!Reflect.defineProperty(this.target_,e,t))return!1}else l(this.target_,e,t);this.notifyPropertyAddition_(e,t.value)}finally{bt()}return!0},n.defineObservableProperty_=function(e,t,n,r){void 0===r&&(r=!1),this.keysAtom_;try{yt();var i=this.delete_(e);if(!i)return i;if(pn(this)){var a=mn(this,{object:this.proxy_||this.target_,name:e,type:Rn,newValue:t});if(!a)return null;t=a.newValue}var o=Yn(e),s={configurable:!pt.safeDescriptors||this.isPlainObject_,enumerable:!0,get:o.get,set:o.set};if(r){if(!Reflect.defineProperty(this.target_,e,s))return!1}else l(this.target_,e,s);var u=new qe(t,n,"ObservableObject.key",!1);this.values_.set(e,u),this.notifyPropertyAddition_(e,u.value_)}finally{bt()}return!0},n.defineComputedProperty_=function(e,t,n){void 0===n&&(n=!1),this.keysAtom_;try{yt();var r=this.delete_(e);if(!r)return r;if(pn(this))if(!mn(this,{object:this.proxy_||this.target_,name:e,type:Rn,newValue:void 0}))return null;t.name||(t.name="ObservableObject.key"),t.context=this.proxy_||this.target_;var i=Yn(e),a={configurable:!pt.safeDescriptors||this.isPlainObject_,enumerable:!1,get:i.get,set:i.set};if(n){if(!Reflect.defineProperty(this.target_,e,a))return!1}else l(this.target_,e,a);this.values_.set(e,new Ye(t)),this.notifyPropertyAddition_(e,void 0)}finally{bt()}return!0},n.delete_=function(e,t){if(void 0===t&&(t=!1),this.keysAtom_,!O(this.target_,e))return!0;if(pn(this)&&!mn(this,{object:this.proxy_||this.target_,name:e,type:qn}))return null;try{var n,r;yt();var i,a=gn(this),l=this.values_.get(e),s=void 0;if(!l&&a)s=null==(i=o(this.target_,e))?void 0:i.value;if(t){if(!Reflect.deleteProperty(this.target_,e))return!1}else delete this.target_[e];if(l&&(this.values_.delete(e),l instanceof qe&&(s=l.value_),wt(l)),this.keysAtom_.reportChanged(),null==(n=this.pendingKeys_)||null==(r=n.get(e))||r.set(e in this.target_),a){var u={type:qn,observableKind:"object",object:this.proxy_||this.target_,debugObjectName:this.name_,oldValue:s,name:e};0,a&&bn(this,u)}}finally{bt()}return!0},n.observe_=function(e,t){return yn(this,e)},n.intercept_=function(e){return vn(this,e)},n.notifyPropertyAddition_=function(e,t){var n,r,i=gn(this);if(i){var a=i?{type:Rn,observableKind:"object",debugObjectName:this.name_,object:this.proxy_||this.target_,name:e,newValue:t}:null;0,i&&bn(this,a)}null==(n=this.pendingKeys_)||null==(r=n.get(e))||r.set(!0),this.keysAtom_.reportChanged()},n.ownKeys_=function(){return this.keysAtom_.reportObserved(),C(this.target_)},n.keys_=function(){return this.keysAtom_.reportObserved(),Object.keys(this.target_)},e}();function Gn(e,t){var n;if(O(e,H))return e;var r=null!=(n=null==t?void 0:t.name)?n:"ObservableObject",i=new Qn(e,new Map,String(r),function(e){var t;return e?null!=(t=e.defaultDecorator)?t:ge(e):void 0}(t));return w(e,H,i),e}var Xn=x("ObservableObjectAdministration",Qn);function Yn(e){return Kn[e]||(Kn[e]={get:function(){return this[H].getObservablePropValue_(e)},set:function(t){return this[H].setObservablePropValue_(e,t)}})}function Jn(e){return!!y(e)&&Xn(e[H])}function Zn(e,t,n){var r;null==(r=e.target_[U])||delete r[n]}function er(e,t,n){}var tr,nr,rr=sr(0),ir=function(){var e=!1,t={};return Object.defineProperty(t,"0",{set:function(){e=!0}}),Object.create(t)[0]=1,!1===e}(),ar=0,or=function(){};tr=or,nr=Array.prototype,Object.setPrototypeOf?Object.setPrototypeOf(tr.prototype,nr):void 0!==tr.prototype.__proto__?tr.prototype.__proto__=nr:tr.prototype=nr;var lr=function(e,t,n){function r(t,n,r,i){var a;return void 0===r&&(r="ObservableArray"),void 0===i&&(i=!1),a=e.call(this)||this,vr((function(){var e=new zn(r,n,i,!0);e.proxy_=M(a),k(M(a),H,e),t&&t.length&&a.spliceWithArray(0,0,t),ir&&Object.defineProperty(M(a),"0",rr)})),a}I(r,e);var i=r.prototype;return i.concat=function(){this[H].atom_.reportObserved();for(var e=arguments.length,t=new Array(e),n=0;n<e;n++)t[n]=arguments[n];return Array.prototype.concat.apply(this.slice(),t.map((function(e){return An(e)?e.slice():e})))},i[n]=function(){var e=this,t=0;return _r({next:function(){return t<e.length?{value:e[t++],done:!1}:{done:!0,value:void 0}}})},T(r,[{key:"length",get:function(){return this[H].getArrayLength_()},set:function(e){this[H].setArrayLength_(e)}},{key:t,get:function(){return"Array"}}]),r}(or,Symbol.toStringTag,Symbol.iterator);function sr(e){return{enumerable:!1,configurable:!0,get:function(){return this[H].get_(e)},set:function(t){this[H].set_(e,t)}}}function ur(e){l(lr.prototype,""+e,sr(e))}function cr(e){if(e>ar){for(var t=ar;t<e+100;t++)ur(t);ar=e}}function dr(e,t,n){return new lr(e,t,n)}function fr(e,n){if("object"==typeof e&&null!==e){if(An(e))return void 0!==n&&t(23),e[H].atom_;if(Wn(e))return e.atom_;if(Fn(e)){if(void 0===n)return e.keysAtom_;var r=e.data_.get(n)||e.hasMap_.get(n);return r||t(25,n,pr(e)),r}if(Jn(e)){if(!n)return t(26);var i=e[H].values_.get(n);return i||t(27,n,pr(e)),i}if(W(e)||Je(e)||Ct(e))return e}else if(m(e)&&Ct(e[H]))return e[H];t(28)}function hr(e,n){return e||t(29),void 0!==n?hr(fr(e,n)):W(e)||Je(e)||Ct(e)||Fn(e)||Wn(e)?e:e[H]?e[H]:void t(24,e)}function pr(e,t){var n;if(void 0!==t)n=fr(e,t);else{if(Dt(e))return e.name;n=Jn(e)||Fn(e)||Wn(e)?hr(e):fr(e)}return n.name_}function vr(e){var t=ot(),n=$e(!0);yt();try{return e()}finally{bt(),We(n),lt(t)}}Object.entries(Cn).forEach((function(e){var t=e[0],n=e[1];"concat"!==t&&w(lr.prototype,t,n)})),cr(1e3);var mr=s.toString;function gr(e,t,n){return void 0===n&&(n=-1),yr(e,t,n)}function yr(e,t,n,r,i){if(e===t)return 0!==e||1/e==1/t;if(null==e||null==t)return!1;if(e!=e)return t!=t;var a=typeof e;if("function"!==a&&"object"!==a&&"object"!=typeof t)return!1;var o=mr.call(e);if(o!==mr.call(t))return!1;switch(o){case"[object RegExp]":case"[object String]":return""+e==""+t;case"[object Number]":return+e!=+e?+t!=+t:0==+e?1/+e==1/t:+e==+t;case"[object Date]":case"[object Boolean]":return+e==+t;case"[object Symbol]":return"undefined"!=typeof Symbol&&Symbol.valueOf.call(e)===Symbol.valueOf.call(t);case"[object Map]":case"[object Set]":n>=0&&n++}e=br(e),t=br(t);var l="[object Array]"===o;if(!l){if("object"!=typeof e||"object"!=typeof t)return!1;var s=e.constructor,u=t.constructor;if(s!==u&&!(m(s)&&s instanceof s&&m(u)&&u instanceof u)&&"constructor"in e&&"constructor"in t)return!1}if(0===n)return!1;n<0&&(n=-1),i=i||[];for(var c=(r=r||[]).length;c--;)if(r[c]===e)return i[c]===t;if(r.push(e),i.push(t),l){if((c=e.length)!==t.length)return!1;for(;c--;)if(!yr(e[c],t[c],n-1,r,i))return!1}else{var d,f=Object.keys(e);if(c=f.length,Object.keys(t).length!==c)return!1;for(;c--;)if(!O(t,d=f[c])||!yr(e[d],t[d],n-1,r,i))return!1}return r.pop(),i.pop(),!0}function br(e){return An(e)?e.slice():S(e)||Fn(e)||z(e)||Wn(e)?Array.from(e.entries()):e}function _r(e){return e[Symbol.iterator]=wr,e}function wr(){return this}["Symbol","Map","Set"].forEach((function(e){void 0===i()[e]&&t("MobX requires global '"+e+"' to be available or polyfilled")})),"object"==typeof __MOBX_DEVTOOLS_GLOBAL_HOOK__&&__MOBX_DEVTOOLS_GLOBAL_HOOK__.injectMobx({spy:function(e){return console.warn("[mobx.spy] Is a no-op in production builds"),function(){}},extras:{getDebugName:pr},$mobx:H});var kr=n(959);if(!kr.useState)throw new Error("mobx-react-lite requires React with Hooks support");if(!function(e,t,n){return vr((function(){var r=Gn(e,n)[H];null!=t||(t=function(e){return O(e,U)||w(e,U,L({},e[U])),e[U]}(e)),C(t).forEach((function(e){return r.make_(e,t[e])}))})),e})throw new Error("mobx-react-lite@3 requires mobx at least version 6 to be available");var xr=n(422);function Sr(e){e()}function zr(e){return Yt(e)}var Pr=!1;function Cr(){return Pr}var jr=function(){function e(e){var t=this;Object.defineProperty(this,"finalize",{enumerable:!0,configurable:!0,writable:!0,value:e}),Object.defineProperty(this,"registrations",{enumerable:!0,configurable:!0,writable:!0,value:new Map}),Object.defineProperty(this,"sweepTimeout",{enumerable:!0,configurable:!0,writable:!0,value:void 0}),Object.defineProperty(this,"sweep",{enumerable:!0,configurable:!0,writable:!0,value:function(e){void 0===e&&(e=1e4),clearTimeout(t.sweepTimeout),t.sweepTimeout=void 0;var n=Date.now();t.registrations.forEach((function(r,i){n-r.registeredAt>=e&&(t.finalize(r.value),t.registrations.delete(i))})),t.registrations.size>0&&t.scheduleSweep()}}),Object.defineProperty(this,"finalizeAllImmediately",{enumerable:!0,configurable:!0,writable:!0,value:function(){t.sweep(0)}})}return Object.defineProperty(e.prototype,"register",{enumerable:!1,configurable:!0,writable:!0,value:function(e,t,n){this.registrations.set(n,{value:t,registeredAt:Date.now()}),this.scheduleSweep()}}),Object.defineProperty(e.prototype,"unregister",{enumerable:!1,configurable:!0,writable:!0,value:function(e){this.registrations.delete(e)}}),Object.defineProperty(e.prototype,"scheduleSweep",{enumerable:!1,configurable:!0,writable:!0,value:function(){void 0===this.sweepTimeout&&(this.sweepTimeout=setTimeout(this.sweep,1e4))}}),e}(),Or=new("undefined"!=typeof FinalizationRegistry?FinalizationRegistry:jr)((function(e){var t;null===(t=e.reaction)||void 0===t||t.dispose(),e.reaction=null})),Er=n(322),Nr=function(){};function Tr(e){e.reaction=new kt("observer".concat(e.name),(function(){var t;e.stateVersion=Symbol(),null===(t=e.onStoreChange)||void 0===t||t.call(e)}))}function Lr(e,t){if(void 0===t&&(t="observed"),Cr())return e();var n=kr.useRef(null);if(!n.current){var r={reaction:null,onStoreChange:null,stateVersion:Symbol(),name:t,subscribe:function(e){return Or.unregister(r),r.onStoreChange=e,r.reaction||(Tr(r),r.stateVersion=Symbol()),function(){var e;r.onStoreChange=null,null===(e=r.reaction)||void 0===e||e.dispose(),r.reaction=null}},getSnapshot:function(){return r.stateVersion}};n.current=r}var i,a,o=n.current;if(o.reaction||(Tr(o),Or.register(n,o,o)),kr.useDebugValue(o.reaction,zr),(0,Er.useSyncExternalStore)(o.subscribe,o.getSnapshot,Nr),o.reaction.track((function(){try{i=e()}catch(e){a=e}})),a)throw a;return i}var Ir="function"==typeof Symbol&&Symbol.for,Ar=Ir?Symbol.for("react.forward_ref"):"function"==typeof kr.forwardRef&&(0,kr.forwardRef)((function(e){return null})).$$typeof,Mr=Ir?Symbol.for("react.memo"):"function"==typeof kr.memo&&(0,kr.memo)((function(e){return null})).$$typeof;function Rr(e,t){var n;if(Mr&&e.$$typeof===Mr)throw new Error("[mobx-react-lite] You are trying to use `observer` on a function component wrapped in either another `observer` or `React.memo`. The observer already applies 'React.memo' for you.");if(Cr())return e;var r=null!==(n=null==t?void 0:t.forwardRef)&&void 0!==n&&n,i=e,a=e.displayName||e.name;if(Ar&&e.$$typeof===Ar&&(r=!0,"function"!=typeof(i=e.render)))throw new Error("[mobx-react-lite] `render` property of ForwardRef was not a function");var o,l,s=function(e,t){return Lr((function(){return i(e,t)}),a)};return s.displayName=e.displayName,Object.defineProperty(s,"name",{value:e.name,writable:!0,configurable:!0}),e.contextTypes&&(s.contextTypes=e.contextTypes),r&&(s=(0,kr.forwardRef)(s)),s=(0,kr.memo)(s),o=e,l=s,Object.keys(o).forEach((function(e){Dr[e]||Object.defineProperty(l,e,Object.getOwnPropertyDescriptor(o,e))})),s}var Dr={$$typeof:!0,render:!0,compare:!0,type:!0,displayName:!0};var Ur;!function(e){e||(e=Sr),Gt({reactionScheduler:e})}(xr.unstable_batchedUpdates);Ur=Or.finalizeAllImmediately;var Vr=JSON.parse('{"(Latest {{latestPhpVersion}})":{"ja":"\uff08\u6700\u65b0 {{latestPhpVersion}}\uff09","zh":"\uff08\u6700\u65b0  {{latestPhpVersion}}\uff09","zhcn":"\uff08\u6700\u65b0  {{latestPhpVersion}}\uff09","zhhk":"\uff08\u6700\u65b0 {{latestPhpVersion}}\uff09","zhtw":"\uff08\u6700\u65b0 {{latestPhpVersion}}\uff09"},"Becnhmark":{"ja":"\u57fa\u6e96","zh":"\u8dd1\u5206","zhcn":"\u8dd1\u5206","zhhk":"\u8dd1\u5206","zhtw":"\u8dd1\u5206"},"CPU model":{"ja":"CPU\u30e2\u30c7\u30eb","zh":"CPU \u578b\u53f7","zhcn":"CPU \u578b\u53f7","zhhk":"CPU \u578b\u865f","zhtw":"CPU \u578b\u865f"},"CPU usage":{"ja":"CPU \u4f7f\u7528\u7387","zh":"CPU \u5360\u7528","zhcn":"CPU \u5360\u7528","zhhk":"CPU \u4f7f\u7528\u7387","zhtw":"CPU \u4f7f\u7528\u7387"},"Can not fetch IP":{"ja":"IP\u3092\u53d6\u5f97\u3067\u304d\u307e\u305b\u3093","zh":"\u65e0\u6cd5\u83b7\u53d6 IP","zhcn":"\u65e0\u6cd5\u83b7\u53d6 IP","zhhk":"\u7121\u6cd5\u7372\u53d6 IP","zhtw":"\u7121\u6cd5\u7372\u53d6 IP \u5730\u5740"},"Can not fetch location.":{"ja":"\u5834\u6240\u3092\u53d6\u5f97\u3067\u304d\u307e\u305b\u3093\u3002","zh":"\u65e0\u6cd5\u83b7\u53d6\u5730\u7406\u4f4d\u7f6e\u3002","zhcn":"\u65e0\u6cd5\u83b7\u53d6\u5730\u7406\u4f4d\u7f6e\u3002","zhhk":"\u7121\u6cd5\u7372\u53d6\u5730\u7406\u4f4d\u7f6e\u3002","zhtw":"\u7121\u6cd5\u7372\u53d6\u5730\u7406\u4fe1\u606f\u3002"},"Can not fetch marks data from GitHub.":{"ja":"GitHub\u304b\u3089\u30de\u30fc\u30af\u30c7\u30fc\u30bf\u3092\u53d6\u5f97\u3067\u304d\u307e\u305b\u3093\u3002","zh":"\u65e0\u6cd5\u4ece GitHub \u4e2d\u83b7\u53d6\u8dd1\u5206\u6570\u636e\u3002","zhcn":"\u65e0\u6cd5\u4ece GitHub \u4e2d\u83b7\u53d6\u8dd1\u5206\u6570\u636e\u3002","zhhk":"\u7121\u6cd5\u5f9e GitHub \u4e2d\u7372\u53d6\u8dd1\u5206\u6578\u64da\u3002","zhtw":"\u7121\u6cd5\u5f9e GitHub \u4e2d\u7372\u53d6\u8dd1\u5206\u8cc7\u6599\u3002"},"Can not update file, please check the server permissions and space.":{"ja":"\u30d5\u30a1\u30a4\u30eb\u3092\u66f4\u65b0\u3067\u304d\u307e\u305b\u3093\u3002\u30b5\u30fc\u30d0\u30fc\u306e\u6a29\u9650\u3068\u30b9\u30da\u30fc\u30b9\u3092\u78ba\u8a8d\u3057\u3066\u304f\u3060\u3055\u3044\u3002","zh":"\u65e0\u6cd5\u66f4\u65b0\u6587\u4ef6\uff0c\u8bf7\u68c0\u67e5\u670d\u52a1\u5668\u6743\u9650\u548c\u7a7a\u95f4\u3002","zhcn":"\u65e0\u6cd5\u66f4\u65b0\u6587\u4ef6\uff0c\u8bf7\u68c0\u67e5\u670d\u52a1\u5668\u6743\u9650\u548c\u7a7a\u95f4\u3002","zhhk":"\u7121\u6cd5\u66f4\u65b0\u6587\u4ef6\uff0c\u8acb\u6aa2\u67e5\u4f3a\u670d\u5668\u6b0a\u9650\u548c\u7a7a\u9593\u3002","zhtw":"\u7121\u6cd5\u66f4\u65b0\u6a94\u6848\uff0c\u8acb\u6aa2\u67e5\u4f3a\u670d\u5668\u6b0a\u9650\u548c\u7a7a\u9593\u3002"},"Click to close":{"ja":"\u30af\u30ea\u30c3\u30af\u3057\u3066\u9589\u3058\u308b","zh":"\u70b9\u51fb\u5173\u95ed","zhcn":"\u70b9\u51fb\u5173\u95ed","zhhk":"\u9ede\u64ca\u95dc\u9589","zhtw":"\u9ede\u64ca\u95dc\u9589"},"Click to update":{"ja":"\u30af\u30ea\u30c3\u30af\u3057\u3066\u66f4\u65b0","zh":"\u70b9\u51fb\u66f4\u65b0","zhcn":"\u70b9\u51fb\u66f4\u65b0","zhhk":"\u{1f446} \u9ede\u64ca\u66f4\u65b0","zhtw":"\u{1f446} \u9ede\u64ca\u66f4\u65b0"},"Copy marks":{"ja":"\u30b3\u30d4\u30fc\u30de\u30fc\u30af","zh":"\u590d\u5236\u5206\u6570","zhcn":"\u590d\u5236\u5206\u6570","zhhk":"\u62f7\u8c9d\u5206\u6578","zhtw":"\u62f7\u8c9d\u5206\u6578"},"DB":{"ja":"DB","zh":"\u6570\u636e\u5e93","zhcn":"\u6570\u636e\u5e93","zhhk":"\u8cc7\u6599\u5eab","zhtw":"\u8cc7\u6599\u5eab"},"Dark":{"ja":"\u95c7","zh":"\u6697\u9ed1","zhcn":"\u6697\u9ed1","zhhk":"\u6697\u9ed1","zhtw":"\u6697\u9ed1"},"Database":{"ja":"\u30c7\u30fc\u30bf\u30d9\u30fc\u30b9","zh":"\u6570\u636e\u5e93","zhcn":"\u6570\u636e\u5e93","zhhk":"\u8cc7\u6599\u5eab","zhtw":"\u8cc7\u6599\u5eab"},"Default":{"ja":"\u30c7\u30d5\u30a9\u30eb\u30c8","zh":"\u9ed8\u8ba4","zhcn":"\u9ed8\u8ba4","zhhk":"\u9ed8\u8a8d","zhtw":"\u9ed8\u8a8d"},"Disabled classes":{"ja":"\u7121\u52b9\u306a\u30af\u30e9\u30b9","zh":"\u5df2\u7981\u7528\u7684\u7c7b","zhcn":"\u5df2\u7981\u7528\u7684\u7c7b","zhhk":"\u7981\u7528\u7684\u985e","zhtw":"\u7981\u7528\u7684\u985e\u5225"},"Disabled functions":{"ja":"\u7121\u52b9\u306a\u6a5f\u80fd","zh":"\u5df2\u7981\u7528\u7684\u51fd\u6570","zhcn":"\u5df2\u7981\u7528\u7684\u51fd\u6570","zhhk":"\u7981\u7528\u7684\u51fd\u6578","zhtw":"\u7981\u7528\u7684\u51fd\u6578"},"Disk usage":{"ja":"\u30c7\u30a3\u30b9\u30af\u306e\u4f7f\u7528\u72b6\u6cc1","zh":"\u78c1\u76d8\u4f7f\u7528\u91cf","zhcn":"\u78c1\u76d8\u4f7f\u7528\u91cf","zhhk":"\u78c1\u789f\u4f7f\u7528","zhtw":"\u78c1\u789f\u4f7f\u7528"},"Display errors":{"ja":"\u30a8\u30e9\u30fc\u8868\u793a","zh":"\u663e\u793a\u9519\u8bef","zhcn":"\u663e\u793a\u9519\u8bef","zhhk":"\u986f\u793a\u932f\u8aa4","zhtw":"\u986f\u793a\u932f\u8aa4"},"Download speed test":{"ja":"\u30cd\u30c3\u30c8\u30ef\u30fc\u30af\u901f\u5ea6\u30c6\u30b9\u30c8\u7528\u306e\u30c0\u30a6\u30f3\u30ed\u30fc\u30c9\u30d5\u30a1\u30a4\u30eb","zh":"\u4e0b\u8f7d\u901f\u5ea6\u6d4b\u8bd5","zhcn":"\u4e0b\u8f7d\u901f\u5ea6\u6d4b\u8bd5","zhhk":"\u4e0b\u8f09\u6587\u4ef6\u4ee5\u6e2c\u8a66\u7db2\u901f","zhtw":"\u4e0b\u8f09\u6587\u4ef6\u4ee5\u6e2c\u8a66\u7db2\u901f"},"Error reporting":{"ja":"\u30a8\u30e9\u30fc\u5831\u544a","zh":"\u9519\u8bef\u62a5\u544a","zhcn":"\u9519\u8bef\u62a5\u544a","zhhk":"\u932f\u8aa4\u5831\u544a","zhtw":"\u932f\u8aa4\u5831\u544a"},"Ext":{"ja":"\u62e1\u5f35","zh":"\u6269\u5c55","zhcn":"\u6269\u5c55","zhhk":"\u64f4\u5c55","zhtw":"\u64f4\u5c55"},"Fetch error, please refresh page.":{"ja":"\u53d6\u5f97\u30a8\u30e9\u30fc\u3002\u30da\u30fc\u30b8\u3092\u66f4\u65b0\u3057\u3066\u304f\u3060\u3055\u3044\u3002","zh":"\u83b7\u53d6\u4fe1\u606f\u9519\u8bef\uff0c\u8bf7\u5237\u65b0\u9875\u9762\u3002","zhcn":"\u83b7\u53d6\u4fe1\u606f\u9519\u8bef\uff0c\u8bf7\u5237\u65b0\u9875\u9762\u3002","zhhk":"\u7372\u53d6\u932f\u8aa4\uff0c\u8acb\u5237\u65b0\u9801\u9762\u3002","zhtw":"\u7372\u53d6\u932f\u8aa4\uff0c\u8acb\u91cd\u65b0\u6574\u7406\u9801\u9762\u3002"},"Fetch failed. Node returns {{code}}.":{"ja":"\u30d5\u30a7\u30c3\u30c1\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002 \u30ce\u30fc\u30c9\u306f {{code}} \u3092\u8fd4\u3057\u307e\u3059\u3002","zh":"\u83b7\u53d6\u5931\u8d25\u3002\u8282\u70b9\u8fd4\u56de\u4e86 {{code}} \u9519\u8bef\u7801\u3002","zhcn":"\u83b7\u53d6\u5931\u8d25\u3002\u8282\u70b9\u8fd4\u56de\u4e86 {{code}} \u9519\u8bef\u7801\u3002","zhhk":"\u7372\u53d6\u5931\u6557\u3002\u7bc0\u9ede\u8fd4\u56de\u4e86 {{code}} \u78bc\u3002","zhtw":"\u7372\u53d6\u5931\u6557\u3002\u7bc0\u9ede\u8fd4\u56de\u4e86 {{code}} \u78bc\u3002"},"Fetching...":{"ja":"\u53d6\u5f97\u3057\u3066\u3044\u307e\u3059...","zh":"\u83b7\u53d6\u4e2d\u2026\u2026","zhcn":"\u83b7\u53d6\u4e2d\u2026\u2026","zhhk":"\u7372\u53d6\u4e2d\u2026\u2026","zhtw":"\u7372\u53d6\u4e2d\u2026\u2026"},"Info":{"ja":"\u60c5\u5831","zh":"\u4fe1\u606f","zhcn":"\u4fe1\u606f","zhhk":"\u8a0a\u606f","zhtw":"\u8a0a\u606f"},"Loaded extensions":{"ja":"\u30ed\u30fc\u30c9\u30a8\u30af\u30b9\u30c6\u30f3\u30b7\u30e7\u30f3","zh":"\u5df2\u52a0\u8f7d\u7684\u6269\u5c55","zhcn":"\u5df2\u52a0\u8f7d\u7684\u6269\u5c55","zhhk":"\u8f09\u5165\u7684 PHP \u64f4\u5c55","zhtw":"\u8f09\u5165\u7684 PHP \u64f4\u5c55"},"Loading...":{"ja":"\u23f3\u30ed\u30fc\u30c9\u4e2d...","zh":"\u52a0\u8f7d\u4e2d\u2026\u2026","zhcn":"\u52a0\u8f7d\u4e2d\u2026\u2026","zhhk":"\u8f09\u5165\u4e2d\u2026\u2026","zhtw":"\u8f09\u5165\u4e2d\u2026\u2026"},"Max POST size":{"ja":"\u6700\u5927 POST \u30b5\u30a4\u30ba","zh":"POST \u63d0\u4ea4\u9650\u5236","zhcn":"POST \u63d0\u4ea4\u9650\u5236","zhhk":"POST \u63d0\u4ea4\u9650\u5236","zhtw":"POST \u63d0\u4ea4\u9650\u5236"},"Max execution time":{"ja":"\u6700\u5927\u5b9f\u884c\u6642\u9593","zh":"\u8fd0\u884c\u8d85\u65f6\u79d2\u6570","zhcn":"\u8fd0\u884c\u8d85\u65f6\u79d2\u6570","zhhk":"\u57f7\u884c\u8d85\u6642\u79d2\u6578","zhtw":"\u57f7\u884c\u903e\u6642\u79d2\u6578"},"Max input variables":{"ja":"\u6700\u5927\u5165\u529b\u5909\u6570","zh":"\u63d0\u4ea4\u8868\u5355\u9650\u5236","zhcn":"\u63d0\u4ea4\u8868\u5355\u9650\u5236","zhhk":"\u63d0\u4ea4\u8868\u55ae\u9650\u5236","zhtw":"\u63d0\u4ea4\u8868\u55ae\u9650\u5236"},"Max memory limit":{"ja":"\u6700\u5927\u30e1\u30e2\u30ea\u5236\u9650","zh":"\u8fd0\u884c\u5185\u5b58\u9650\u5236","zhcn":"\u8fd0\u884c\u5185\u5b58\u9650\u5236","zhhk":"\u57f7\u884c\u8a18\u61b6\u9ad4\u9650\u5236","zhtw":"\u57f7\u884c\u8a18\u61b6\u9ad4\u9650\u5236"},"Max upload size":{"ja":"\u6700\u5927\u30a2\u30c3\u30d7\u30ed\u30fc\u30c9\u30b5\u30a4\u30ba","zh":"\u4e0a\u4f20\u6587\u4ef6\u9650\u5236","zhcn":"\u4e0a\u4f20\u6587\u4ef6\u9650\u5236","zhhk":"\u4e0a\u50b3\u6a94\u6848\u9650\u5236","zhtw":"\u4e0a\u50b3\u6a94\u6848\u9650\u5236"},"Memory":{"ja":"RAM","zh":"\u5185\u5b58","zhcn":"\u5185\u5b58","zhhk":"\u8a18\u61b6\u9ad4","zhtw":"\u8a18\u61b6\u9ad4"},"Memory buffers":{"ja":"\u30e1\u30e2\u30ea\u30d0\u30c3\u30d5\u30a1","zh":"\u5185\u5b58\u7f13\u51b2","zhcn":"\u5185\u5b58\u7f13\u51b2","zhhk":"\u8a18\u61b6\u9ad4\u7de9\u885d","zhtw":"\u8a18\u61b6\u9ad4\u7de9\u885d"},"Memory cached":{"ja":"\u30e1\u30e2\u30ea\u30ad\u30e3\u30c3\u30b7\u30e5","zh":"\u5185\u5b58\u7f13\u5b58","zhcn":"\u5185\u5b58\u7f13\u5b58","zhhk":"\u8a18\u61b6\u9ad4\u5feb\u53d6","zhtw":"\u8a18\u61b6\u9ad4\u5feb\u53d6"},"Memory real usage":{"ja":"\u5b9f\u30e1\u30e2\u30ea\u4f7f\u7528\u91cf","zh":"\u771f\u5b9e\u5185\u5b58\u5360\u7528","zhcn":"\u771f\u5b9e\u5185\u5b58\u5360\u7528","zhhk":"\u771f\u5be6\u8a18\u61b6\u9ad4\u4f7f\u7528","zhtw":"\u771f\u5be6\u8a18\u61b6\u9ad4\u4f7f\u7528"},"Min:{{min}} / Max:{{max}} / Avg:{{avg}}":{"ja":"\u6700\u5c0f: {{min}} / \u6700\u5927: {{max}} / \u5e73\u5747: {{avg}}","zh":"\u6700\u5c0f:{{min}} / \u6700\u5927:{{max}} / \u5e73\u5747:{{avg}}","zhcn":"\u6700\u5c0f:{{min}} / \u6700\u5927:{{max}} / \u5e73\u5747:{{avg}}","zhhk":"\u6700\u5c0f:{{min}} / \u6700\u5927:{{max}} / \u5e73\u5747:{{avg}}","zhtw":"\u6700\u5c0f:{{min}} / \u6700\u5927:{{max}} / \u5e73\u5747:{{avg}}"},"Mine":{"ja":"\u79c1\u306e","zh":"\u6211\u7684","zhcn":"\u6211\u7684","zhhk":"\u6211\u7684","zhtw":"\u6211\u7684"},"Move down":{"ja":"\u4e0b\u306b\u79fb\u52d5","zh":"\u4e0b\u79fb","zhcn":"\u4e0b\u79fb","zhhk":"\u4e0b\u79fb","zhtw":"\u4e0b\u79fb"},"Move up":{"ja":"\u4e0a\u306b\u79fb\u52d5","zh":"\u4e0a\u79fb","zhcn":"\u4e0a\u79fb","zhhk":"\u4e0a\u79fb","zhtw":"\u4e0a\u79fb"},"My IPv4":{"ja":"\u79c1\u306eIPv4","zh":"\u6211\u7684 IPv4","zhcn":"\u6211\u7684 IPv4","zhhk":"\u6211\u7684 IPv4","zhtw":"\u6211\u7684 IPv4"},"My IPv6":{"ja":"\u79c1\u306eIPv6","zh":"\u6211\u7684 IPv6","zhcn":"\u6211\u7684 IPv6","zhhk":"\u6211\u7684 IPv6","zhtw":"\u6211\u7684 IPv6"},"My Information":{"ja":"\u79c1\u306e\u60c5\u5831","zh":"\u6211\u7684\u4fe1\u606f","zhcn":"\u6211\u7684\u4fe1\u606f","zhhk":"\u6211\u7684\u8a0a\u606f","zhtw":"\u6211\u7684\u8a0a\u606f"},"My browser UA":{"ja":"\u79c1\u306e\u30d6\u30e9\u30a6\u30b6 UA","zh":"\u6211\u7684\u6d4f\u89c8\u5668 UA","zhcn":"\u6211\u7684\u6d4f\u89c8\u5668 UA","zhhk":"\u6211\u7684\u700f\u89bd\u5668","zhtw":"\u6211\u7684\u700f\u89bd\u5668"},"My browser languages (via JS)":{"ja":"\u79c1\u306e\u30d6\u30e9\u30a6\u30b6\u306e\u8a00\u8a9e\uff08JS\uff09","zh":"\u6211\u7684\u6d4f\u89c8\u5668\u8bed\u8a00\uff08JS\uff09","zhcn":"\u6211\u7684\u6d4f\u89c8\u5668\u8bed\u8a00\uff08JS\uff09","zhhk":"\u6211\u7684\u700f\u89bd\u5668\u8a9e\u8a00\uff08JS\uff09","zhtw":"\u6211\u7684\u700f\u89bd\u5668\u8a9e\u8a00\uff08JS\uff09"},"My browser languages (via PHP)":{"ja":"\u79c1\u306e\u30d6\u30e9\u30a6\u30b6\u306e\u8a00\u8a9e\uff08PHP\uff09","zh":"\u6211\u7684\u6d4f\u89c8\u5668\u8bed\u8a00\uff08PHP\uff09","zhcn":"\u6211\u7684\u6d4f\u89c8\u5668\u8bed\u8a00\uff08PHP\uff09","zhhk":"\u6211\u7684\u700f\u89bd\u5668\u8a9e\u8a00\uff08PHP\uff09","zhtw":"\u6211\u7684\u700f\u89bd\u5668\u8a9e\u8a00\uff08PHP\uff09"},"My location (IPv4)":{"ja":"\u79c1\u306e\u5834\u6240 (IPv4)","zh":"\u6211\u7684\u4f4d\u7f6e\uff08IPv4\uff09","zhcn":"\u6211\u7684\u4f4d\u7f6e\uff08IPv4\uff09","zhhk":"\u6211\u7684\u4f4d\u7f6e\uff08IPv4\uff09","zhtw":"\u6211\u7684\u4f4d\u7f6e\uff08IPv4\uff09"},"My server":{"ja":"\u79c1\u306e\u30b5\u30fc\u30d0\u30fc","zh":"\u6211\u7684\u670d\u52a1\u5668","zhcn":"\u6211\u7684\u670d\u52a1\u5668","zhhk":"\u6211\u7684\u4f3a\u670d\u5668","zhtw":"\u6211\u7684\u4f3a\u670d\u5668"},"Net":{"ja":"\u30cd\u30c3\u30c8","zh":"\u7f51\u7edc","zhcn":"\u7f51\u7edc","zhhk":"\u6d41\u91cf","zhtw":"\u6d41\u91cf"},"Network Ping":{"ja":"\u30cd\u30c3\u30c8\u30ef\u30fc\u30afPing","zh":"\u7f51\u7edc Ping","zhcn":"\u7f51\u7edc Ping","zhhk":"\u7db2\u901f Ping","zhtw":"\u7db2\u901f Ping"},"Network Stats":{"ja":"\u30cd\u30c3\u30c8\u30ef\u30fc\u30af\u7d71\u8a08","zh":"\u6d41\u91cf\u7edf\u8ba1","zhcn":"\u6d41\u91cf\u7edf\u8ba1","zhhk":"\u6d41\u91cf\u7d71\u8a08","zhtw":"\u6d41\u91cf\u7d71\u8a08"},"Network error, please try again later.":{"ja":"\u30cd\u30c3\u30c8\u30ef\u30fc\u30af\u30a8\u30e9\u30fc\u3067\u3059\u3002\u3057\u3070\u3089\u304f\u3057\u3066\u304b\u3089\u3082\u3046\u4e00\u5ea6\u304a\u8a66\u3057\u304f\u3060\u3055\u3044\u3002","zh":"\u7f51\u7edc\u9519\u8bef\uff0c\u8bf7\u7a0d\u5019\u91cd\u8bd5\u3002","zhcn":"\u7f51\u7edc\u9519\u8bef\uff0c\u8bf7\u7a0d\u5019\u91cd\u8bd5\u3002","zhhk":"\u7db2\u8def\u932f\u8aa4\uff0c\u8acb\u7a0d\u5f8c\u91cd\u8a66\u3002","zhtw":"\u7db2\u8def\u932f\u8aa4\uff0c\u8acb\u7a0d\u5f8c\u91cd\u8a66\u3002"},"Nodes":{"ja":"\u30ce\u30fc\u30c9","zh":"\u8282\u70b9","zhcn":"\u8282\u70b9","zhhk":"\u7bc0\u9ede","zhtw":"\u7bc0\u9ede"},"Not support":{"ja":"\u30b5\u30dd\u30fc\u30c8\u3057\u307e\u305b\u3093","zh":"\u4e0d\u652f\u6301","zhcn":"\u4e0d\u652f\u6301","zhhk":"\u4e0d\u652f\u63f4","zhtw":"\u4e0d\u652f\u63f4"},"Opcache JIT enabled":{"ja":"Opcache JIT \u6709\u52b9","zh":"OPcache JIT \u5df2\u542f\u7528","zhcn":"OPcache JIT \u5df2\u542f\u7528","zhhk":"OPcache JIT \u5df2\u5553\u7528","zhtw":"OPcache JIT \u5df2\u555f\u7528"},"Opcache enabled":{"ja":"Opcache \u6709\u52b9","zh":"OPcache \u5df2\u542f\u7528","zhcn":"OPcache \u5df2\u542f\u7528","zhhk":"OPcache \u5df2\u5553\u7528","zhtw":"OPcache \u5df2\u555f\u7528"},"PHP":{"ja":"PHP","zh":"PHP","zhcn":"PHP","zhhk":"PHP","zhtw":"PHP"},"PHP Extensions":{"ja":"PHP\u30a8\u30af\u30b9\u30c6\u30f3\u30b7\u30e7\u30f3","zh":"PHP \u6269\u5c55","zhcn":"PHP \u6269\u5c55","zhhk":"PHP \u64f4\u5c55","zhtw":"PHP \u64f4\u5c55"},"PHP Information":{"ja":"PHP\u60c5\u5831","zh":"PHP \u4fe1\u606f","zhcn":"PHP \u4fe1\u606f","zhhk":"PHP \u8cc7\u8a0a","zhtw":"PHP \u8cc7\u8a0a"},"Ping":{"ja":"Ping","zh":"Ping","zhcn":"Ping","zhhk":"Ping","zhtw":"Ping"},"SAPI interface":{"ja":"SAPI \u30a4\u30f3\u30bf\u30d5\u30a7\u30fc\u30b9","zh":"SAPI \u63a5\u53e3","zhcn":"SAPI \u63a5\u53e3","zhhk":"SAPI \u4ecb\u9762","zhtw":"SAPI \u4ecb\u9762"},"SMTP support":{"ja":"SMTP \u30b5\u30dd\u30fc\u30c8","zh":"SMTP \u652f\u6301","zhcn":"SMTP \u652f\u6301","zhhk":"SMTP \u652f\u63f4","zhtw":"SMTP \u652f\u63f4"},"STAR \u{1f31f} ME":{"ja":"\u661f\u{1f31f}\u5370","zh":"\u661f \u{1f31f} \u6807","zhcn":"\u661f \u{1f31f} \u6807","zhhk":"\u661f\u{1f31f}\u6a19","zhtw":"\u661f\u{1f31f}\u6a19"},"Script path":{"ja":"\u30b9\u30af\u30ea\u30d7\u30c8\u30d1\u30b9","zh":"\u811a\u672c\u8def\u5f84","zhcn":"\u811a\u672c\u8def\u5f84","zhhk":"\u8173\u672c\u8def\u5f91","zhtw":"\u8173\u672c\u8def\u5f91"},"Server Benchmark":{"ja":"\u30b5\u30fc\u30d0\u30fc\u57fa\u6e96","zh":"\u670d\u52a1\u5668\u8dd1\u5206","zhcn":"\u670d\u52a1\u5668\u8dd1\u5206","zhhk":"\u4f3a\u670d\u5668\u6027\u80fd\u8dd1\u5206","zhtw":"\u4f3a\u670d\u5668\u6027\u80fd\u8dd1\u5206"},"Server IPv4":{"ja":"\u30b5\u30fc\u30d0\u30fc IPv4","zh":"\u670d\u52a1\u5668 IPv4","zhcn":"\u670d\u52a1\u5668 IPv4","zhhk":"\u4f3a\u670d\u5668 IPv4","zhtw":"\u4f3a\u670d\u5668 IPv4"},"Server IPv6":{"ja":"\u30b5\u30fc\u30d0\u30fc IPv6","zh":"\u670d\u52a1\u5668 IPv6","zhcn":"\u670d\u52a1\u5668 IPv6","zhhk":"\u4f3a\u670d\u5668 IPv6","zhtw":"\u4f3a\u670d\u5668 IPv6"},"Server Information":{"ja":"\u30b5\u30fc\u30d0\u30fc\u60c5\u5831","zh":"\u670d\u52a1\u5668\u4fe1\u606f","zhcn":"\u670d\u52a1\u5668\u4fe1\u606f","zhhk":"\u4f3a\u670d\u5668\u8a0a\u606f","zhtw":"\u4f3a\u670d\u5668\u8a0a\u606f"},"Server OS":{"ja":"\u30b5\u30fc\u30d0\u30fc OS","zh":"\u670d\u52a1\u5668\u7cfb\u7edf","zhcn":"\u670d\u52a1\u5668\u7cfb\u7edf","zhhk":"\u4f3a\u670d\u5668\u7cfb\u7d71","zhtw":"\u4f3a\u670d\u5668\u7cfb\u7d71"},"Server Status":{"ja":"\u30b5\u30fc\u30d0\u30fc\u306e\u72b6\u614b","zh":"\u670d\u52a1\u5668\u72b6\u6001","zhcn":"\u670d\u52a1\u5668\u72b6\u6001","zhhk":"\u4f3a\u670d\u5668\u72c0\u614b","zhtw":"\u4f3a\u670d\u5668\u72c0\u614b"},"Server location (IPv4)":{"ja":"\u30b5\u30fc\u30d0\u30fc\u306e\u5834\u6240 (IPv4)","zh":"\u670d\u52a1\u5668\u5730\u7406\u4f4d\u7f6e\uff08IPv4\uff09","zhcn":"\u670d\u52a1\u5668\u5730\u7406\u4f4d\u7f6e\uff08IPv4\uff09","zhhk":"\u4f3a\u670d\u5668\u4f4d\u7f6e\uff08IPv4\uff09","zhtw":"\u4f3a\u670d\u5668\u4f4d\u7f6e\uff08IPv4\uff09"},"Server name":{"ja":"\u30b5\u30fc\u30d0\u30fc\u306e\u540d\u524d","zh":"\u670d\u52a1\u5668\u540d","zhcn":"\u670d\u52a1\u5668\u540d","zhhk":"\u4f3a\u670d\u5668\u540d","zhtw":"\u4f3a\u670d\u5668\u540d"},"Server software":{"ja":"\u30b5\u30fc\u30d0\u30fc\u30bd\u30d5\u30c8\u30a6\u30a7\u30a2","zh":"\u670d\u52a1\u5668\u8f6f\u4ef6","zhcn":"\u670d\u52a1\u5668\u8f6f\u4ef6","zhhk":"\u4f3a\u670d\u5668\u8edf\u9ad4","zhtw":"\u4f3a\u670d\u5668\u8edf\u9ad4"},"Server time":{"ja":"\u30b5\u30fc\u30d0\u30fc\u6642\u9593","zh":"\u670d\u52a1\u5668\u65f6\u95f4","zhcn":"\u670d\u52a1\u5668\u65f6\u95f4","zhhk":"\u6301\u7e8c\u4e0a\u7dda\u6642\u9593","zhtw":"\u6301\u7e8c\u4e0a\u7dda\u6642\u9593"},"Server uptime":{"ja":"\u30b5\u30fc\u30d0\u30fc\u306e\u7a3c\u50cd\u6642\u9593","zh":"\u6301\u7eed\u8fd0\u4f5c\u65f6\u95f4","zhcn":"\u6301\u7eed\u8fd0\u4f5c\u65f6\u95f4","zhhk":"\u6301\u7e8c\u4e0a\u7dda\u6642\u9593","zhtw":"\u6301\u7e8c\u4e0a\u7dda\u6642\u9593"},"Status":{"ja":"\u72b6\u614b","zh":"\u72b6\u6001","zhcn":"\u72b6\u6001","zhhk":"\u72c0\u614b","zhtw":"\u72c0\u614b"},"Swap":{"ja":"Swap","zh":"Swap","zhcn":"Swap","zhhk":"Swap","zhtw":"Swap"},"Swap cached":{"ja":"SWAP \u30ad\u30e3\u30c3\u30b7\u30e5","zh":"SWAP \u7f13\u5b58","zhcn":"SWAP \u7f13\u5b58","zhhk":"SWAP \u5feb\u53d6","zhtw":"SWAP \u5feb\u53d6"},"Swap usage":{"ja":"SWAP \u4f7f\u7528\u91cf","zh":"SWAP \u5360\u7528","zhcn":"SWAP \u5360\u7528","zhhk":"SWAP \u4f7f\u7528","zhtw":"SWAP \u4f7f\u7528"},"System load":{"ja":"\u30b7\u30b9\u30c6\u30e0\u8ca0\u8377","zh":"\u7cfb\u7edf\u8d1f\u8f7d","zhcn":"\u7cfb\u7edf\u8d1f\u8f7d","zhhk":"\u7cfb\u7d71\u8ca0\u8f09","zhtw":"\u7cfb\u7d71\u8ca0\u8f09"},"Temp.":{"ja":"\u6e29\u5ea6","zh":"\u6e29\u5ea6","zhcn":"\u6e29\u5ea6","zhhk":"\u6eab\u5ea6","zhtw":"\u6eab\u5ea6"},"Temperature Sensor":{"ja":"\u6e29\u5ea6\u30bb\u30f3\u30b5\u30fc","zh":"\u6e29\u5ea6\u4f20\u611f\u5668","zhcn":"\u6e29\u5ea6\u4f20\u611f\u5668","zhhk":"\u6eab\u5ea6\u50b3\u611f\u5668","zhtw":"\u6eab\u5ea6\u50b3\u611f\u5668"},"Timeout for socket":{"ja":"\u30bd\u30b1\u30c3\u30c8\u306e\u30bf\u30a4\u30e0\u30a2\u30a6\u30c8","zh":"Socket \u8d85\u65f6\u79d2\u6570","zhcn":"Socket \u8d85\u65f6\u79d2\u6570","zhhk":"Socket \u8d85\u6642\u79d2\u6578","zhtw":"Socket \u903e\u6642\u79d2\u6578"},"Times:{{times}}":{"ja":"\u56de: {{times}}","zh":"\u6b21\u6570\uff1a{{times}}","zhcn":"\u6b21\u6570\uff1a{{times}}","zhhk":"\u6b21\u6578\uff1a{{times}}","zhtw":"\u6b21\u6578\uff1a{{times}}"},"Treatment URLs file":{"ja":"Treatment URLs \u30d5\u30a1\u30a4\u30eb","zh":"\u6587\u4ef6\u8fdc\u7aef\u6253\u5f00","zhcn":"\u6587\u4ef6\u8fdc\u7aef\u6253\u5f00","zhhk":"\u6a94\u6848\u9060\u7aef\u6253\u958b","zhtw":"\u6a94\u6848\u9060\u7aef\u6253\u958b"},"Unavailable":{"ja":"\u5229\u7528\u4e0d\u53ef","zh":"\u4e0d\u53ef\u7528","zhcn":"\u4e0d\u53ef\u7528","zhhk":"\u4e0d\u53ef\u7528","zhtw":"\u4e0d\u53ef\u7528"},"Usage: {{percent}}":{"ja":"\u4f54\u7528: {{percent}}","zh":"\u4f7f\u7528\uff1a{{percent}}","zhcn":"\u4f7f\u7528\uff1a{{percent}}","zhhk":"\u4f54\u7528\uff1a{{percent}}","zhtw":"\u4f54\u7528\uff1a{{percent}}"},"Version":{"ja":"\u30d0\u30fc\u30b8\u30e7\u30f3","zh":"\u7248\u672c","zhcn":"\u7248\u672c","zhhk":"\u7248\u672c","zhtw":"\u7248\u672c"},"Visit PHP.net Official website":{"ja":"PHP.net \u516c\u5f0f\u30a6\u30a7\u30d6\u30b5\u30a4\u30c8\u306b\u30a2\u30af\u30bb\u30b9","zh":"\u8bbf\u95ee PHP.net \u5b98\u7f51","zhcn":"\u8bbf\u95ee PHP.net \u5b98\u7f51","zhhk":"\u8a2a\u554f PHP.net \u5b98\u7db2","zhtw":"\u700f\u89bd PHP.net \u5b98\u7db2"},"Visit prober page":{"ja":"X-Prober \u30db\u30fc\u30e0\u30da\u30fc\u30b8\u3078","zh":"\u67e5\u770b\u63a2\u9488\u9875\u9762","zhcn":"\u67e5\u770b\u63a2\u9488\u9875\u9762","zhhk":"\u67e5\u95b1\u63a2\u91dd\u9801\u9762","zhtw":"\u67e5\u95b1\u63a2\u91dd\u9801\u9762"},"Visit the official website":{"ja":"\u516c\u5f0f\u30a6\u30a7\u30d6\u30b5\u30a4\u30c8\u3092\u3054\u89a7\u304f\u3060\u3055\u3044","zh":"\u8bbf\u95ee\u5b98\u7f51","zhcn":"\u8bbf\u95ee\u5b98\u7f51","zhhk":"\u8a2a\u554f\u5b98\u7db2","zhtw":"\u700f\u89bd\u5b98\u7db2"},"idle: {{idle}} \\\\\\\\nnice: {{nice}} \\\\\\\\nsys: {{sys}} \\\\\\\\nuser: {{user}}":{"ja":"idle: {{idle}} \\\\\\\\nnice: {{nice}} \\\\\\\\nsys: {{sys}} \\\\\\\\nuser: {{user}}","zh":"idle: {{idle}} \\\\\\\\nnice: {{nice}} \\\\\\\\nsys: {{sys}} \\\\\\\\nuser: {{user}}","zhcn":"idle: {{idle}} \\\\\\\\nnice: {{nice}} \\\\\\\\nsys: {{sys}} \\\\\\\\nuser: {{user}}","zhhk":"idle: {{idle}} \\\\\\\\nnice: {{nice}} \\\\\\\\nsys: {{sys}} \\\\\\\\nuser: {{user}}","zhtw":"idle: {{idle}} \\\\\\\\nnice: {{nice}} \\\\\\\\nsys: {{sys}} \\\\\\\\nuser: {{user}}"},"{{days}} days {{hours}} hours {{mins}} mins {{secs}} secs":{"ja":"{{days}} \u65e5 {{hours}} \u6642 {{mins}} \u5206 {{secs}} \u79d2","zh":"{{days}} \u5929 {{hours}} \u5c0f\u65f6 {{mins}} \u5206 {{secs}} \u79d2","zhcn":"{{days}} \u5929 {{hours}} \u5c0f\u65f6 {{mins}} \u5206 {{secs}} \u79d2","zhhk":"{{days}} \u5929 {{hours}} \u6642 {{mins}} \u5206 {{secs}} \u79d2","zhtw":"{{days}} \u5929 {{hours}} \u6642 {{mins}} \u5206 {{secs}} \u79d2"},"{{minute}} minute average":{"ja":"{{minute}} \u5206\u3054\u3068\u306e\u5e73\u5747\u8ca0\u8377","zh":"{{minute}} \u5206\u949f\u5e73\u5747\u8d1f\u8f7d","zhcn":"{{minute}} \u5206\u949f\u5e73\u5747\u8d1f\u8f7d","zhhk":"{{minute}} \u5206\u9418\u5e73\u5747\u8ca0\u8f09","zhtw":"{{minute}} \u5206\u9418\u5e73\u5747\u8ca0\u8f09"},"{{sensor}} temperature":{"ja":"{{sensor}} \u6e29\u5ea6","zh":"{{sensor}} \u6e29\u5ea6","zhcn":"{{sensor}} \u6e29\u5ea6","zhhk":"{{sensor}} \u6eab\u5ea6","zhtw":"{{sensor}} \u6eab\u5ea6"},"\u23f3 Please wait {{seconds}}s":{"ja":"\u23f3 {{seconds}} \u79d2\u304a\u5f85\u3061\u304f\u3060\u3055\u3044","zh":"\u23f3 \u8bf7\u7b49\u5f85 {{seconds}}\u79d2","zhcn":"\u23f3 \u8bf7\u7b49\u5f85 {{seconds}}\u79d2","zhhk":"\u23f3 \u8acb\u7b49\u5f85 {{seconds}} \u79d2","zhtw":"\u23f3 \u8acb\u7b49\u5f85 {{seconds}} \u79d2"},"\u23f3 Testing, please wait...":{"ja":"\u23f3 \u30c6\u30b9\u30c8\u3057\u3066\u3044\u307e\u3059\u3002\u304a\u5f85\u3061\u304f\u3060\u3055\u3044...","zh":"\u23f3 \u8dd1\u5206\u4e2d\uff0c\u8bf7\u7a0d\u7b49\u2026\u2026","zhcn":"\u23f3 \u8dd1\u5206\u4e2d\uff0c\u8bf7\u7a0d\u7b49\u2026\u2026","zhhk":"\u23f3 \u8dd1\u5206\u4e2d\uff0c\u8acb\u7a0d\u7b49\u2026\u2026","zhtw":"\u23f3 \u8dd1\u5206\u4e2d\uff0c\u8acb\u7a0d\u7b49\u2026\u2026"},"\u23f3 Updating, please wait a second...":{"ja":"\u23f3 \u66f4\u65b0\u3057\u3066\u3044\u307e\u3059\u3002\u3057\u3070\u3089\u304f\u304a\u5f85\u3061\u304f\u3060\u3055\u3044...","zh":"\u23f3 \u66f4\u65b0\u4e2d\uff0c\u8bf7\u7a0d\u7b49\u4e00\u4f1a\u2026\u2026","zhcn":"\u23f3 \u66f4\u65b0\u4e2d\uff0c\u8bf7\u7a0d\u7b49\u4e00\u4f1a\u2026\u2026","zhhk":"\u23f3 \u66f4\u65b0\u4e2d\uff0c\u8acb\u7a0d\u7b49\u2026\u2026","zhtw":"\u23f3 \u66f4\u65b0\u4e2d\uff0c\u8acb\u7a0d\u7b49\u2026\u2026"},"\u23f8\ufe0f Stop ping":{"ja":"\u23f8\ufe0f Ping\u3092\u505c\u6b62","zh":"\u23f8\ufe0f \u505c\u6b62 Ping","zhcn":"\u23f8\ufe0f \u505c\u6b62 Ping","zhhk":"\u23f8\ufe0f \u505c\u6b62 Ping","zhtw":"\u23f8\ufe0f \u505c\u6b62 Ping"},"\u2728 Found update! Version {{oldVersion}} \u2192 {{newVersion}}":{"ja":"\u2728 \u30a2\u30c3\u30d7\u30c7\u30fc\u30c8\u304c\u898b\u305f\uff01\u30d0\u30fc\u30b8\u30e7\u30f3 {{oldVersion}} \u2192 {{newVersion}}","zh":"\u2728 \u53d1\u73b0\u66f4\u65b0\uff01\u7248\u672c {{oldVersion}} \u2192 {{newVersion}}","zhcn":"\u2728 \u53d1\u73b0\u66f4\u65b0\uff01\u7248\u672c {{oldVersion}} \u2192 {{newVersion}}","zhhk":"\u2728 \u767c\u73fe\u66f4\u65b0\uff01\u7248\u672c {{oldVersion}} \u2192 {{newVersion}}","zhtw":"\u2728 \u767c\u73fe\u66f4\u65b0\uff01\u7248\u672c {{oldVersion}} \u2192 {{newVersion}}"},"\u274c Update error, click here to try again?":{"ja":"\u274c \u66f4\u65b0\u30a8\u30e9\u30fc\u3002\u3053\u3053\u3092\u30af\u30ea\u30c3\u30af\u3057\u3066\u518d\u8a66\u884c\u3057\u307e\u3059\u304b\uff1f","zh":"\u274c \u66f4\u65b0\u9519\u8bef\uff0c\u70b9\u51fb\u6b64\u5904\u518d\u8bd5\u4e00\u6b21\uff1f","zhcn":"\u274c \u66f4\u65b0\u9519\u8bef\uff0c\u70b9\u51fb\u6b64\u5904\u518d\u8bd5\u4e00\u6b21\uff1f","zhhk":"\u274c \u66f4\u65b0\u932f\u8aa4\uff0c\u9ede\u64ca\u6b64\u8655\u518d\u8a66\u4e00\u6b21\uff1f","zhtw":"\u274c \u66f4\u65b0\u932f\u8aa4\uff0c\u9ede\u64ca\u6b64\u8655\u518d\u8a66\u4e00\u6b21\uff1f"},"\u{1f446} Click for detail":{"ja":"\u8a73\u7d30\u306f\u3053\u3061\u3089","zh":"\u{1f446} \u8be6\u7ec6\u4fe1\u606f","zhcn":"\u{1f446} \u8be6\u7ec6\u4fe1\u606f","zhhk":"\u{1f446} \u67e5\u770b\u8a73\u7d30","zhtw":"\u{1f446} \u67e5\u770b\u8a73\u7d30"},"\u{1f446} Click to fetch":{"ja":"\u{1f446} \u30af\u30ea\u30c3\u30af\u3057\u3066\u30d5\u30a7\u30c3\u30c1","zh":"\u{1f446} \u70b9\u51fb\u83b7\u53d6","zhcn":"\u{1f446} \u70b9\u51fb\u83b7\u53d6","zhhk":"\u{1f446} \u9ede\u64ca\u7372\u53d6","zhtw":"\u{1f446} \u9ede\u64ca\u7372\u53d6"},"\u{1f446} Click to test":{"ja":"\u{1f446} \u30af\u30ea\u30c3\u30af\u3057\u3066\u30c6\u30b9\u30c8","zh":"\u{1f446} \u70b9\u51fb\u8dd1\u5206","zhcn":"\u{1f446} \u70b9\u51fb\u8dd1\u5206","zhhk":"\u{1f446} \u9ede\u64ca\u8dd1\u5206","zhtw":"\u{1f446} \u9ede\u64ca\u8dd1\u5206"},"\u{1f446} Start ping":{"ja":"\u{1f446} Ping\u3092\u958b\u59cb","zh":"\u{1f446} \u5f00\u59cb Ping","zhcn":"\u{1f446} \u5f00\u59cb Ping","zhhk":"\u{1f446} \u958b\u59cb Ping","zhtw":"\u{1f446} \u958b\u59cb Ping"},"Error: can not fetch remote config data, update checker is disabled.":{"zh":"\u9519\u8bef\uff1a\u65e0\u6cd5\u83b7\u53d6\u8fdc\u7a0b\u914d\u7f6e\u6570\u636e\uff0c\u66f4\u65b0\u68c0\u6d4b\u5df2\u7981\u7528\u3002","zhcn":"\u9519\u8bef\uff1a\u65e0\u6cd5\u83b7\u53d6\u8fdc\u7a0b\u914d\u7f6e\u6570\u636e\uff0c\u66f4\u65b0\u68c0\u6d4b\u5df2\u7981\u7528\u3002","zhhk":"\u932f\u8aa4\uff1a\u7121\u6cd5\u7372\u53d6\u914d\u7f6e\u6578\u64da\uff0c\u66f4\u65b0\u6aa2\u6e2c\u5df2\u7981\u7528\u3002","zhtw":"\u932f\u8aa4\uff1a\u7121\u6cd5\u7372\u53d6\u914d\u5099\u8cc7\u6599\uff0c\u66f4\u65b0\u6aa2\u6e2c\u5df2\u7981\u7528\u3002"},"Read":{"zh":"\u8bfb","zhcn":"\u8bfb","zhhk":"\u8b80","zhtw":"\u8b80"},"Write":{"zh":"\u5199","zhcn":"\u5199","zhhk":"\u5beb","zhtw":"\u5beb"}}');const Br=navigator.language.replace("-","").replace("_","").toLowerCase(),Fr=(e,t="")=>{var n,r;return null!==(r=null===(n=null==Vr?void 0:Vr[`${t||""}${e}`])||void 0===n?void 0:n[Br])&&void 0!==r?r:e};Gt({enforceActions:"observed"});const Hr=new class{constructor(){this.activeIndex=0,this.setActiveIndex=e=>{this.activeIndex=e},wn(this)}};var $r=function(e,t){var n={};for(var r in e)Object.prototype.hasOwnProperty.call(e,r)&&t.indexOf(r)<0&&(n[r]=e[r]);if(null!=e&&"function"==typeof Object.getOwnPropertySymbols){var i=0;for(r=Object.getOwnPropertySymbols(e);i<r.length;i++)t.indexOf(r[i])<0&&Object.prototype.propertyIsEnumerable.call(e,r[i])&&(n[r[i]]=e[r[i]])}return n};const Wr=({activeIndex:t,children:n})=>(0,e.jsx)(e.Fragment,{children:kr.Children.map(n,((n,r)=>{const i=t===r,{type:a,props:o}=n,{className:l=""}=o,s=$r(o,["className"]);return(0,e.jsx)(a,Object.assign({className:l,"data-active":i||void 0},s))}))}),Kr=({id:t,setActiveIndex:n,threshold:r=50,topOffset:i=50,children:a})=>{const o=(0,kr.useRef)([[0,0]]),l=(0,kr.useRef)(0),s=(0,kr.useCallback)((()=>{l.current&&window.clearTimeout(l.current),l.current=window.setTimeout((()=>{const e=Math.round(window.scrollY)+i;o.current.map((([t,r],i)=>e>=t&&e<t+r?n(i):null))}),r)}),[n,r,i]);return(0,kr.useEffect)((()=>{const e=new ResizeObserver((()=>{const e=kr.Children.count(a);o.current=a.map(((n,r)=>{const i=document.querySelector(`[data-elevator='${t}-${r}']`);if(!i)return[0,0];switch(r){case 0:return[0,Math.round(i.offsetHeight)];case e-1:return[Math.round(i.offsetTop),Math.round(document.body.offsetHeight)];default:return[Math.round(i.offsetTop),Math.round(i.offsetHeight)]}}))}));return e.observe(document.body),()=>e.unobserve(document.body)}),[a,t]),(0,kr.useEffect)((()=>(window.addEventListener("scroll",s),()=>{window.removeEventListener("scroll",s)})),[s]),(0,e.jsx)(e.Fragment,{children:kr.Children.map(a,((n,r)=>{const{type:i,props:a}=n;return(0,e.jsx)(i,Object.assign({},a,{"data-elevator":`${t}-${r}`}))}))})};var qr=function(e,t){var n={};for(var r in e)Object.prototype.hasOwnProperty.call(e,r)&&t.indexOf(r)<0&&(n[r]=e[r]);if(null!=e&&"function"==typeof Object.getOwnPropertySymbols){var i=0;for(r=Object.getOwnPropertySymbols(e);i<r.length;i++)t.indexOf(r[i])<0&&Object.prototype.propertyIsEnumerable.call(e,r[i])&&(n[r[i]]=e[r[i]])}return n};Gt({enforceActions:"observed"});const Qr=new class{constructor(){this.cards=[],this.addCard=e=>{const t=this.getStoragePriority(e.id);t&&(e.priority=t),this.cards.push(e)},this.setCardsPriority=e=>{e.forEach((({id:e,priority:t})=>{const n=this.cards.findIndex((t=>t.id===e));-1!==n&&this.cards[n].priority!==t&&(this.cards[n].priority=t)}))},this.setCard=e=>{var{id:t}=e,n=qr(e,["id"]);const r=this.cards.findIndex((e=>e.id===t));-1!==r&&(this.cards[r]=Object.assign(Object.assign({},this.cards[r]),n))},this.moveCardUp=e=>{const t=this.enabledCards,n=t.findIndex((t=>t.id===e));n<=0||([t[n].priority,t[n-1].priority]=[t[n-1].priority,t[n].priority],this.setCardsPriority(t),this.setStoragePriorityItems())},this.moveCardDown=e=>{const t=this.enabledCards,n=t.findIndex((t=>t.id===e));-1!==n&&n!==t.length-1&&([t[n].priority,t[n+1].priority]=[t[n+1].priority,t[n].priority],this.setCardsPriority(t),this.setStoragePriorityItems())},this.getStoragePriorityItems=()=>{const e=localStorage.getItem("cardsPriority");return e&&JSON.parse(e)||null},this.setStoragePriorityItems=()=>{localStorage.setItem("cardsPriority",JSON.stringify(this.enabledCards.map((({id:e,priority:t})=>({id:e,priority:t})))))},this.getStoragePriority=e=>{const t=this.getStoragePriorityItems();if(!t)return 0;const n=t.find((t=>t.id===e));return n?n.priority:0},wn(this)}get cardsLength(){return this.cards.length}get enabledCards(){return this.cards.slice().filter((({enabled:e=!0})=>e)).sort(((e,t)=>e.priority-t.priority))}get enabledCardsLength(){return this.enabledCards.length}};var Gr="src-Components-Card-components-styles-module__des--EgOss",Xr="src-Components-Card-components-styles-module__link--QMvaX",Yr="src-Components-Card-components-styles-module__error--RxEjQ",Jr="src-Components-Card-components-styles-module__title--sQBIC",Zr="src-Components-Card-components-styles-module__group--onjSH",ei="src-Components-Card-components-styles-module__content--Ibvay",ti="src-Components-Card-components-styles-module__fieldset--GoXuV",ni="src-Components-Card-components-styles-module__body--aNmjc",ri="src-Components-Card-components-styles-module__arrow--YXo0g",ii="src-Components-Card-components-styles-module__legend--fgO2f",ai="src-Components-Card-components-styles-module__legendText--q65Xw",oi="src-Components-Card-components-styles-module__multiItemContainer--CAVDM";const li=({isDown:t,disabled:n,id:r,handleClick:i})=>(0,e.jsx)("a",{className:ri,title:Fr("Move up"),"data-disabled":n||void 0,onClick:e=>{e.preventDefault(),i(r)},href:"#",children:t?"\u25bc":"\u25b2"}),si=Rr((()=>{const{cardsLength:t,enabledCards:n,enabledCardsLength:r,moveCardDown:i,moveCardUp:a}=Qr;return t?(0,e.jsx)(Kr,{id:"innCard",setActiveIndex:Hr.setActiveIndex,children:n.map((({id:t,title:n,component:o},l)=>(0,e.jsxs)("fieldset",{className:ti,id:t,children:[(0,e.jsxs)("legend",{className:ii,children:[(0,e.jsx)(li,{id:t,handleClick:a,isDown:!1,disabled:0===l}),(0,e.jsx)("span",{className:ai,children:n}),(0,e.jsx)(li,{id:t,handleClick:i,isDown:!0,disabled:l===r-1})]}),(0,e.jsx)("div",{className:ni,children:(0,e.jsx)(o,{})})]},t)))}):null}));var ui="src-Components-Container-components-styles-module__main--rQ91J";const ci=t=>(0,e.jsx)("div",Object.assign({className:ui},t));var di={container:"src-Components-Grid-components-styles-module__container--EXgkw",grid:"src-Components-Grid-components-styles-module__grid--qbVV1"},fi=function(e,t){var n={};for(var r in e)Object.prototype.hasOwnProperty.call(e,r)&&t.indexOf(r)<0&&(n[r]=e[r]);if(null!=e&&"function"==typeof Object.getOwnPropertySymbols){var i=0;for(r=Object.getOwnPropertySymbols(e);i<r.length;i++)t.indexOf(r[i])<0&&Object.prototype.propertyIsEnumerable.call(e,r[i])&&(n[r[i]]=e[r[i]])}return n};const hi=t=>{var{xs:n,sm:r,md:i,lg:a,xl:o,xxl:l}=t,s=fi(t,["xs","sm","md","lg","xl","xxl"]);const u={xs:n,sm:r,md:i,lg:a,xl:o,xxl:l},c={};for(const e of Object.keys(u)){const t=null==u?void 0:u[e];t&&(c[`data-${e}`]=t)}return(0,e.jsx)("div",Object.assign({className:di.grid},c,s))};var pi=function(e,t){var n={};for(var r in e)Object.prototype.hasOwnProperty.call(e,r)&&t.indexOf(r)<0&&(n[r]=e[r]);if(null!=e&&"function"==typeof Object.getOwnPropertySymbols){var i=0;for(r=Object.getOwnPropertySymbols(e);i<r.length;i++)t.indexOf(r[i])<0&&Object.prototype.propertyIsEnumerable.call(e,r[i])&&(n[r[i]]=e[r[i]])}return n};const vi=t=>{var{name:n="",title:r="",children:i}=t,a=pi(t,["name","title","children"]);return(0,e.jsx)(hi,Object.assign({},a,{children:(0,e.jsxs)("div",{className:Zr,children:[Boolean(n)&&(0,e.jsx)("div",{className:Jr,title:r,children:n}),(0,e.jsx)("div",{className:ei,children:i})]})}))},mi=t=>(0,e.jsx)("div",Object.assign({className:di.container},t));var gi="src-Components-Utils-components-alert-styles-module__main--fj45p";const yi=({isSuccess:t,msg:n=""})=>(0,e.jsx)("div",{className:gi,"data-ok":t||void 0,"data-error":!t||void 0,"data-icon":!n||void 0,children:n}),bi=null===window||void 0===window?void 0:window.CONF;Gt({enforceActions:"observed"});const _i=new class{constructor(){this.ID="database",this.conf=null==bi?void 0:bi[this.ID],this.enabled=Boolean(this.conf)}},wi=Rr((()=>{const{conf:t}=_i,n=[["SQLite3",null==t?void 0:t.sqlite3],["SQLite",null==t?void 0:t.sqliteLibversion],["MySQLi client",null==t?void 0:t.mysqliClientVersion],["Mongo",null==t?void 0:t.mongo],["MongoDB",null==t?void 0:t.mongoDb],["PostgreSQL",null==t?void 0:t.postgreSql],["Paradox",null==t?void 0:t.paradox],["MS SQL",null==t?void 0:t.msSql],["PDO",null==t?void 0:t.pdo]];return(0,e.jsx)(mi,{children:n.map((([t,n])=>(0,e.jsx)(vi,{name:t,sm:2,lg:2,xl:3,xxl:4,children:(0,e.jsx)(yi,{isSuccess:Boolean(n),msg:n})},t)))})})),ki=(e,t=2)=>{if(0===e)return"0";let n=Math.floor(Math.log(e)/Math.log(1024));n=n<0?0:n;const r=parseFloat((e/Math.pow(1024,n)).toFixed(t));return r?`${r} ${["B","K","M","G","T","P","E","Z","Y"][n]}`:"0"},xi=e=>{e=e.replace("#","");const t=new ArrayBuffer(4);new DataView(t).setUint32(0,parseInt(e,16),!1);const n=new Uint8Array(t);return[n[1],n[2],n[3]]},Si=(e,t,n,r=1)=>`${`${(256|e).toString(16).slice(1)}${(256|t).toString(16).slice(1)}${(256|n).toString(16).slice(1)}`}${1===r?"":(255*r|256).toString(16).slice(1)}`,zi=(e,t,n=100)=>{const r=xi(e),i=xi(t),a=(i[0]-r[0])/n,o=(i[1]-r[1])/n,l=(i[2]-r[2])/n,s=[];for(let e=0;e<n;e+=1)s.push(Si(Number(a*e+r[0]),Number(o*e+r[1]),Number(l*e+r[2])));return s};var Pi={main:"src-Components-ProgressBar-components-styles-module__main--vmjyU",overview:"src-Components-ProgressBar-components-styles-module__overview--bibEt",precent:"src-Components-ProgressBar-components-styles-module__precent--wnWh2",shell:"src-Components-ProgressBar-components-styles-module__shell--gG7gJ",value:"src-Components-ProgressBar-components-styles-module__value--itYdo"};const Ci=(0,kr.memo)((({title:t="",value:n,max:r,isCapacity:i,percentTag:a="%",left:o=""})=>{const l=0===r||0===n?0:n/r*100,s=i?`${ki(n)} / ${ki(r)}`:`${n.toFixed(1)}${a} / ${r}${a}`,u=o||`${l.toFixed(1)}${a}`;return(0,e.jsxs)("div",{className:Pi.main,title:t,children:[(0,e.jsx)("div",{className:[Pi.precent,Pi.overview].join(" "),children:u}),(0,e.jsx)("div",{className:Pi.overview,children:s}),(0,e.jsx)("div",{className:Pi.shell,children:(0,e.jsx)("div",{className:Pi.value,style:{background:"#"+zi("#00cc00","#ef2d2d")[Math.round(l)-1],width:`${l<=5?5:l}%`}})})]})}));const ji=new class{constructor(){this.id="diskUsage",this.conf=null==bi?void 0:bi[this.id],this.isEnable=Boolean(this.conf)}},Oi=Rr((()=>{var t;const{conf:n}=ji,r=null!==(t=null==n?void 0:n.items)&&void 0!==t?t:[];return r.length?(0,e.jsx)(mi,{children:r.map((({id:t,free:n,total:r})=>(0,e.jsx)(vi,{name:t,children:(0,e.jsx)(Ci,{value:r-n,max:r,isCapacity:!0})},t)))}):null}));const Ei=new class{constructor(){var e,t,n,r,i,a,o,l,s,u,c,d,f,h,p,v;this.id="bootstrap",this.conf=null==bi?void 0:bi[this.id],this.version=String(null!==(t=null===(e=this.conf)||void 0===e?void 0:e.version)&&void 0!==t?t:"0.0.0"),this.appConfigUrls=null!==(r=null===(n=this.conf)||void 0===n?void 0:n.appConfigUrls)&&void 0!==r?r:[],this.appConfigUrlDev=String(null!==(a=null===(i=this.conf)||void 0===i?void 0:i.appConfigUrlDev)&&void 0!==a?a:""),this.appName=String(null!==(l=null===(o=this.conf)||void 0===o?void 0:o.appName)&&void 0!==l?l:""),this.appUrl=String(null!==(u=null===(s=this.conf)||void 0===s?void 0:s.appUrl)&&void 0!==u?u:""),this.authorUrl=String(null!==(d=null===(c=this.conf)||void 0===c?void 0:c.authorUrl)&&void 0!==d?d:""),this.authorName=String(null!==(h=null===(f=this.conf)||void 0===f?void 0:f.authorName)&&void 0!==h?h:""),this.isDev=Boolean(null!==(v=null===(p=this.conf)||void 0===p?void 0:p.isDev)&&void 0!==v&&v)}};function Ni(e,t){for(const[n,r]of Object.entries(t)){const t=new RegExp(`\\{\\{${n}\\}\\}`,"g");e=e.replace(t,String(r))}return e}Gt({enforceActions:"observed"});const Ti=new class{constructor(){this.ID="footer",this.conf=null==bi?void 0:bi[this.ID]}};var Li="src-Components-Footer-components-styles-module__main--zdKev";const Ii=Rr((()=>{const{appName:t,appUrl:n,authorName:r,authorUrl:i}=Ei,{memUsage:a,time:o}=Ti.conf;return(0,e.jsx)("div",{className:Li,dangerouslySetInnerHTML:{__html:Ni(Fr("Generator {{appName}} / Author {{authorName}} / {{memUsage}} / {{time}}ms"),{appName:`<a href="${n}" target="_blank">${t}</a>`,authorName:`<a href="${i}" target="_blank">${r}</a>`,memUsage:ki(a),time:(1e3*o).toFixed(2)})}})}));var Ai="src-Components-Forkme-components-styles-module__link--MuvAU",Mi="src-Components-Forkme-components-styles-module__text--Fk_hI";const Ri=()=>(0,e.jsxs)("a",{className:Ai,href:Ei.appUrl,target:"_blank",title:"Star",rel:"noreferrer",children:[(0,e.jsx)("svg",{xmlns:"http://www.w3.org/2000/svg",width:"24",height:"24",viewBox:"0 0 24 24",children:(0,e.jsx)("path",{fill:"currentColor",d:"M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"})}),(0,e.jsx)("div",{className:Mi,children:"Star"})]}),Di=200;var Ui=function(e,t,n,r){return new(n||(n=Promise))((function(i,a){function o(e){try{s(r.next(e))}catch(e){a(e)}}function l(e){try{s(r.throw(e))}catch(e){a(e)}}function s(e){var t;e.done?i(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(o,l)}s((r=r.apply(e,t||[])).next())}))};const Vi=e=>{const[t,n]=(0,kr.useState)({ip:"",msg:Fr("Loading..."),isLoading:!0});return(0,kr.useEffect)((()=>{Ui(void 0,void 0,void 0,(function*(){try{const t=yield fetch(`https://ipv${e}.inn-studio.com/ip/?json`),r=yield t.json();(null==r?void 0:r.ip)&&t.status===Di?n({ip:r.ip,msg:"",isLoading:!1}):n({ip:"",msg:Fr("Can not fetch IP"),isLoading:!1})}catch(e){n({ip:"",msg:Fr("Not support"),isLoading:!1})}}))}),[e]),t};Gt({enforceActions:"observed"});const Bi=new class{constructor(){this.ID="myInfo",this.conf=null==bi?void 0:bi[this.ID],this.enabled=Boolean(this.conf)}};var Fi=function(e,t,n,r){return new(n||(n=Promise))((function(i,a){function o(e){try{s(r.next(e))}catch(e){a(e)}}function l(e){try{s(r.throw(e))}catch(e){a(e)}}function s(e){var t;e.done?i(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(o,l)}s((r=r.apply(e,t||[])).next())}))};const Hi=(e,t={})=>Fi(void 0,void 0,void 0,(function*(){var n,r;t=Object.assign({method:"GET",headers:{"Content-Type":"application/json",Authorization:null!==(r=null===(n=Ei.conf)||void 0===n?void 0:n.authorization)&&void 0!==r?r:""},cache:"no-cache",credentials:"omit"},t);const i=`${window.location.pathname}?action=${e}`,a=yield fetch(i,t);try{return{status:a.status,data:yield a.json()}}catch(e){return console.warn(e),{status:a.status}}}));Gt({enforceActions:"observed"});const $i=new class{constructor(){this.isOpen=!1,this.msg="",this.setMsg=e=>{this.msg=e},this.close=(e=0)=>{setTimeout((()=>{!function(e){He(e.name,!1,e,this,void 0)}((()=>{this.isOpen=!1}))}),1e3*e)},this.open=e=>{this.msg=e,this.isOpen=!0},wn(this)}};var Wi=function(e,t,n,r){return new(n||(n=Promise))((function(i,a){function o(e){try{s(r.next(e))}catch(e){a(e)}}function l(e){try{s(r.throw(e))}catch(e){a(e)}}function s(e){var t;e.done?i(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(o,l)}s((r=r.apply(e,t||[])).next())}))};const Ki=Rr((({ip:t})=>{const[n,r]=(0,kr.useState)(!1),[i,a]=(0,kr.useState)(null),o=(0,kr.useCallback)((e=>Wi(void 0,void 0,void 0,(function*(){if(e.preventDefault(),n)return;r(!0);const{data:i,status:o}=yield Hi(`clientLocationIpv4&ip=${t}`);r(!1),i&&o===Di?a(i):$i.open(Fr("Can not fetch location."))}))),[n,t]),l=n?Fr("Loading..."):"";let s="";return n||(s=i?[i.flag,i.country,i.region,i.city].filter((e=>Boolean(e))).join(", "):Fr("\u{1f446} Click to fetch")),t?(0,e.jsxs)("a",{onClick:o,href:"#",title:Fr("The author only has 10,000 API requests per month, please do not abuse it."),children:[l,s]}):(0,e.jsx)(e.Fragment,{children:"-"})})),qi=Rr((()=>{const{conf:t}=Bi,{ip:n,msg:r,isLoading:i}=Vi(4),{ip:a,msg:o,isLoading:l}=Vi(6);let s="",u="";s=i?r:n||((null==t?void 0:t.ipv4)?t.ipv4:r),u=l?o:a||((null==t?void 0:t.ipv6)?t.ipv6:o);const c=[[Fr("My IPv4"),s],[Fr("My IPv6"),u],[Fr("My location (IPv4)"),(0,e.jsx)(Ki,{ip:n||(null==t?void 0:t.ipv4)},"myLocalIpv4")],[Fr("My browser UA"),navigator.userAgent],[Fr("My browser languages (via JS)"),navigator.languages.join(",")],[Fr("My browser languages (via PHP)"),null==t?void 0:t.phpLanguage]];return(0,e.jsx)(mi,{children:c.map((([t,n])=>(0,e.jsx)(vi,{name:t,children:n},t)))})}));const Qi=new class{constructor(){this.id="myInfo",this.conf=null==bi?void 0:bi[this.id],this.isEnable=Boolean(this.conf)}};var Gi="src-Components-Nav-components-styles-module__main--gMYNN",Xi="src-Components-Nav-components-styles-module__link--kVaBO",Yi="src-Components-Nav-components-styles-module__linkTitle--qqTdU",Ji="src-Components-Nav-components-styles-module__linkTitleTiny--pkoLr";const Zi=Rr((()=>{const t=Qr.enabledCards.map((({id:t,title:n,tinyTitle:r,enabled:i=!0})=>i?(0,e.jsxs)("a",{className:Xi,href:`#${t}`,children:[(0,e.jsx)("span",{className:Yi,children:n}),(0,e.jsx)("span",{className:Ji,children:r})]},t):null)).filter((e=>e));return(0,e.jsx)("div",{className:Gi,children:(0,e.jsx)(Wr,{activeIndex:Hr.activeIndex,children:t})})}));var ea=function(e,t,n,r){return new(n||(n=Promise))((function(i,a){function o(e){try{s(r.next(e))}catch(e){a(e)}}function l(e){try{s(r.throw(e))}catch(e){a(e)}}function s(e){var t;e.done?i(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(o,l)}s((r=r.apply(e,t||[])).next())}))};Gt({enforceActions:"observed"});const ta=new class{constructor(){this.isLoading=!0,this.data={},this.initFetch=()=>ea(this,void 0,void 0,(function*(){const{data:e,status:t}=yield Hi("fetch");e&&t===Di?(this.setData(e),this.isLoading&&this.setIsLoading(!1),setTimeout((()=>ea(this,void 0,void 0,(function*(){yield this.initFetch()}))),1e3)):alert(Fr("Fetch error, please refresh page."))})),this.setIsLoading=e=>{this.isLoading=e},this.setData=e=>{this.data=e},wn(this),this.initFetch()}};const na=new class{constructor(){this.id="networkStats",this.conf=null==bi?void 0:bi[this.id],this.isEnable=Boolean(this.conf)}};Gt({enforceActions:"observed"});const{conf:ra,id:ia}=na;const aa=new class{constructor(){wn(this)}get items(){var e,t;return(ta.isLoading?null==ra?void 0:ra.networks:null===(t=null===(e=ta.data)||void 0===e?void 0:e[ia])||void 0===t?void 0:t.networks)||[]}get sortItems(){return this.items.slice().filter((({tx:e})=>Boolean(e))).sort(((e,t)=>e.tx-t.tx))}get itemsCount(){return this.sortItems.length}get timestamp(){var e,t;return(ta.isLoading?null==ra?void 0:ra.timestamp:null===(t=null===(e=ta.data)||void 0===e?void 0:e[ia])||void 0===t?void 0:t.timestamp)||(null==ra?void 0:ra.timestamp)||0}};var oa={id:"src-Components-NetworkStats-components-styles-module__id--eJf_G",idRow:"src-Components-NetworkStats-components-styles-module__idRow--ACOSC",dataContainer:"src-Components-NetworkStats-components-styles-module__dataContainer--bPvUe",data:"src-Components-NetworkStats-components-styles-module__data--Fo38e",rate:"src-Components-NetworkStats-components-styles-module__rate--eoXaN",rateRx:"src-Components-NetworkStats-components-styles-module__rateRx--IuEZe",rateTx:"src-Components-NetworkStats-components-styles-module__rateTx--gWEgj"};const la=({id:t,singleLine:n=!0,totalRx:r=0,rateRx:i=0,totalTx:a=0,rateTx:o=0})=>t?(0,e.jsxs)("div",{className:[oa.idRow,di.container].join(" "),children:[(0,e.jsx)(hi,{lg:n?3:1,children:(0,e.jsx)("div",{className:oa.id,children:t})}),(0,e.jsx)(hi,{lg:n?3:1,children:(0,e.jsxs)("div",{className:oa.dataContainer,children:[(0,e.jsxs)("div",{className:oa.data,"data-rx":!0,children:[(0,e.jsx)("div",{children:ki(r)}),(0,e.jsxs)("div",{className:oa.rateRx,children:[ki(i),"/s"]})]}),(0,e.jsxs)("div",{className:oa.data,"data-tx":!0,children:[(0,e.jsx)("div",{children:ki(a)}),(0,e.jsxs)("div",{className:oa.rateTx,children:[ki(o),"/s"]})]})]})})]}):null,sa=Rr((()=>{const{sortItems:t,itemsCount:n,timestamp:r}=aa;if(!n)return null;const i=(a={items:t,timestamp:r},o=(0,kr.useRef)(),(0,kr.useEffect)((function(){o.current=a})),o.current);var a,o;const l=r-((null==i?void 0:i.timestamp)||r);return(0,e.jsx)(mi,{children:t.map((({id:n,rx:r,tx:a})=>{if(!r&&!a)return null;const o=((null==i?void 0:i.items)||t).find((e=>e.id===n)),s=(null==o?void 0:o.rx)||0,u=(null==o?void 0:o.tx)||0;return(0,e.jsx)(vi,{lg:2,xxl:3,children:(0,e.jsx)(la,{id:n,totalRx:r,rateRx:(r-s)/l,totalTx:a,rateTx:(a-u)/l})},n)}))})}));const ua=new class{constructor(){this.id="serverStatus",this.conf=null==bi?void 0:bi[this.id],this.isEnable=Boolean(this.conf)}};Gt({enforceActions:"observed"});const{id:ca,conf:da}=ua;const fa=new class{constructor(){wn(this)}get fetchData(){var e;return null===(e=ta.data)||void 0===e?void 0:e[ca]}get sysLoad(){var e;return ta.isLoading?null==da?void 0:da.sysLoad:(null===(e=this.fetchData)||void 0===e?void 0:e.sysLoad)||[0,0,0]}get cpuUsage(){var e;return ta.isLoading?{idle:90,nice:0,sys:5,user:5}:null===(e=this.fetchData)||void 0===e?void 0:e.cpuUsage}get memRealUsage(){var e;return ta.isLoading?null==da?void 0:da.memRealUsage:null===(e=this.fetchData)||void 0===e?void 0:e.memRealUsage}get memCached(){var e;return ta.isLoading?null==da?void 0:da.memCached:null===(e=this.fetchData)||void 0===e?void 0:e.memCached}get memBuffers(){var e;return ta.isLoading?null==da?void 0:da.memBuffers:null===(e=this.fetchData)||void 0===e?void 0:e.memBuffers}get swapUsage(){var e;return ta.isLoading?null==da?void 0:da.swapUsage:null===(e=this.fetchData)||void 0===e?void 0:e.swapUsage}get swapCached(){var e;return ta.isLoading?null==da?void 0:da.swapCached:null===(e=this.fetchData)||void 0===e?void 0:e.swapCached}};var ha="src-Components-ServerStatus-components-styles-module__loadGroup--WzXPX",pa="src-Components-ServerStatus-components-styles-module__loadGroupItem--ZSsqk";const va=({sysLoad:t,isCenter:n})=>{const r=[1,5,15],i=t.map(((e,t)=>({id:`${r[t]}minAvg`,load:e,text:Ni(Fr("{{minute}} minute average"),{minute:r[t]})})));return(0,e.jsx)("div",{className:ha,"data-center":n||void 0,children:i.map((({id:t,load:n,text:r})=>(0,e.jsx)("div",{className:pa,title:r,children:n.toFixed(2)},t)))})},ma=Rr((({isCenter:t=!1})=>(0,e.jsx)(vi,{name:Fr("System load"),children:(0,e.jsx)(va,{isCenter:t,sysLoad:fa.sysLoad})})));var ga="src-Components-Utils-components-loading-styles-module__main--jnV53",ya="src-Components-Utils-components-loading-styles-module__text--opKiN";const ba=()=>(0,e.jsx)("svg",{width:"16px",height:"16px",viewBox:"0 0 100 100",preserveAspectRatio:"xMidYMid",children:(0,e.jsx)("g",{transform:"translate(50 50)",children:(0,e.jsx)("g",{transform:"scale(0.7)",children:(0,e.jsxs)("g",{transform:"translate(-50 -50)",children:[(0,e.jsxs)("g",{children:[(0,e.jsx)("animateTransform",{attributeName:"transform",type:"rotate",repeatCount:"indefinite",values:"0 50 50;360 50 50",keyTimes:"0;1",dur:"0.7575757575757576s"}),(0,e.jsx)("path",{fillOpacity:"0.8",fill:"#832f0e",d:"M50 50L50 0A50 50 0 0 1 100 50Z"})]}),(0,e.jsxs)("g",{children:[(0,e.jsx)("animateTransform",{attributeName:"transform",type:"rotate",repeatCount:"indefinite",values:"0 50 50;360 50 50",keyTimes:"0;1",dur:"1.0101010101010102s"}),(0,e.jsx)("path",{fillOpacity:"0.8",fill:"#0c0a08",d:"M50 50L50 0A50 50 0 0 1 100 50Z",transform:"rotate(90 50 50)"})]}),(0,e.jsxs)("g",{children:[(0,e.jsx)("animateTransform",{attributeName:"transform",type:"rotate",repeatCount:"indefinite",values:"0 50 50;360 50 50",keyTimes:"0;1",dur:"1.5151515151515151s"}),(0,e.jsx)("path",{fillOpacity:"0.8",fill:"#594a40",d:"M50 50L50 0A50 50 0 0 1 100 50Z",transform:"rotate(180 50 50)"})]}),(0,e.jsxs)("g",{children:[(0,e.jsx)("animateTransform",{attributeName:"transform",type:"rotate",repeatCount:"indefinite",values:"0 50 50;360 50 50",keyTimes:"0;1",dur:"3.0303030303030303s"}),(0,e.jsx)("path",{fillOpacity:"0.8",fill:"#8e7967",d:"M50 50L50 0A50 50 0 0 1 100 50Z",transform:"rotate(270 50 50)"})]})]})})})}),_a=t=>(0,e.jsxs)("div",{className:ga,children:[(0,e.jsx)(ba,{}),(0,e.jsx)("div",Object.assign({className:ya},t))]});const wa=new class{constructor(){this.id="nodes",this.conf=null==bi?void 0:bi[this.id],this.isEnable=Boolean(this.conf)}};var ka=function(e,t){var n={};for(var r in e)Object.prototype.hasOwnProperty.call(e,r)&&t.indexOf(r)<0&&(n[r]=e[r]);if(null!=e&&"function"==typeof Object.getOwnPropertySymbols){var i=0;for(r=Object.getOwnPropertySymbols(e);i<r.length;i++)t.indexOf(r[i])<0&&Object.prototype.propertyIsEnumerable.call(e,r[i])&&(n[r[i]]=e[r[i]])}return n};Gt({enforceActions:"observed"});const{conf:xa}=wa;const Sa=new class{constructor(){var e;this.DEFAULT_ITEM={id:"",url:"",isLoading:!0,isError:!1,fetchUrl:""},this.items=[],this.setItems=e=>{this.items=e},this.setItem=e=>{var{id:t}=e,n=ka(e,["id"]);const r=this.items.findIndex((e=>e.id===t));-1!==r&&(this.items[r]=Object.assign(Object.assign({},cn(this.items[r],new Map)),n))},wn(this);const t=(null!==(e=null==xa?void 0:xa.items)&&void 0!==e?e:[]).map((e=>{var{url:t}=e,n=ka(e,["url"]);return Object.assign(Object.assign({},this.DEFAULT_ITEM),Object.assign({url:t,fetchUrl:`${t}?action=fetch`},n))}));this.setItems(t)}get itemsCount(){return this.items.length}};var za="src-Components-Nodes-components-styles-module__groupId--PmHBP",Pa="src-Components-Nodes-components-styles-module__group--cvxdK",Ca="src-Components-Nodes-components-styles-module__groupMsg--wNqQl",ja="src-Components-Nodes-components-styles-module__groupNetworks--h1HMf",Oa="src-Components-Nodes-components-styles-module__groupNetwork--rvydY";const Ea=({items:t,timestamp:n})=>{const r=t.length,[i,a]=(0,kr.useState)({curr:{items:t,timestamp:n},prev:{items:t,timestamp:n}});if((0,kr.useEffect)((()=>{a((e=>({curr:{items:t,timestamp:n},prev:e.curr})))}),[t,n]),!r)return null;const{curr:o,prev:l}=i,s=o.timestamp-l.timestamp;return(0,e.jsx)("div",{className:ja,children:t.map((({id:t,rx:n,tx:r})=>{if(!n&&!r)return null;const i=l.items.find((e=>e.id===t)),a=(null==i?void 0:i.rx)||0,o=(null==i?void 0:i.tx)||0;return(0,e.jsx)("div",{className:Oa,children:(0,e.jsx)(la,{id:t,singleLine:!1,totalRx:n,rateRx:(n-a)/s,totalTx:r,rateTx:(r-o)/s})},t)}))})};var Na=function(e,t,n,r){return new(n||(n=Promise))((function(i,a){function o(e){try{s(r.next(e))}catch(e){a(e)}}function l(e){try{s(r.throw(e))}catch(e){a(e)}}function s(e){var t;e.done?i(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(o,l)}s((r=r.apply(e,t||[])).next())}))};const Ta=({sysLoad:t})=>(null==t?void 0:t.length)?(0,e.jsx)("div",{className:Pa,children:(0,e.jsx)(va,{isCenter:!0,sysLoad:t})}):null,La=({cpuUsage:t})=>(0,e.jsx)("div",{className:Pa,children:(0,e.jsx)(Ci,{title:Ni(Fr("idle: {{idle}} \nnice: {{nice}} \nsys: {{sys}} \nuser: {{user}}"),t),value:100-t.idle,max:100,isCapacity:!1,left:Fr("CPU usage")})}),Ia=({memRealUsage:t})=>{const{value:n=0,max:r=0}=t;if(!r)return null;const i=Math.floor(n/r*1e4)/100;return(0,e.jsx)("div",{className:Pa,children:(0,e.jsx)(Ci,{title:Ni(Fr("Usage: {{percent}}"),{percent:`${i.toFixed(1)}%`}),value:n,max:r,isCapacity:!0,left:Fr("Memory")})})},Aa=({swapUsage:t})=>{const{value:n=0,max:r=0}=t;if(!r)return null;const i=Math.floor(n/r*1e4)/100;return(0,e.jsx)("div",{className:Pa,children:(0,e.jsx)(Ci,{title:Ni(Fr("Usage: {{percent}}"),{percent:`${i.toFixed(1)}%`}),value:n,max:r,isCapacity:!0,left:Fr("Swap")})})},Ma=Rr((()=>{const t=Sa.items.map((({id:t,url:n,isLoading:r,isError:i,errMsg:a,data:o})=>{const l=(0,e.jsx)("a",{className:za,href:n,children:t});switch(!0){case r:return(0,e.jsxs)(hi,{lg:2,xl:3,children:[l,(0,e.jsx)("div",{className:Ca,children:(0,e.jsx)(_a,{children:Fr("Fetching...")})})]},t);case i:return(0,e.jsxs)(hi,{lg:2,xl:3,children:[l,(0,e.jsx)("div",{className:Ca,children:(0,e.jsx)(yi,{isSuccess:!1,msg:a})})]},t)}const{serverStatus:s,networkStats:u}=o;return(0,e.jsxs)(hi,{lg:2,xl:3,children:[l,(0,e.jsx)(Ta,{sysLoad:s.sysLoad}),(0,e.jsx)(La,{cpuUsage:null==s?void 0:s.cpuUsage}),(0,e.jsx)(Ia,{memRealUsage:null==s?void 0:s.memRealUsage}),(0,e.jsx)(Aa,{swapUsage:null==s?void 0:s.swapUsage}),(0,e.jsx)(Ea,{items:(null==u?void 0:u.networks)||[],timestamp:(null==u?void 0:u.timestamp)||0})]},t)}));return(0,e.jsx)(e.Fragment,{children:t})})),Ra=Rr((()=>{const{items:t,itemsCount:n}=Sa,r=(0,kr.useCallback)((e=>Na(void 0,void 0,void 0,(function*(){const{setItem:t}=Sa,{data:n,status:i}=yield Hi(`node&nodeId=${e}`);if(i===Di){if(!n)return;t({id:e,isLoading:!1,data:n}),setTimeout((()=>{r(e)}),1e3)}else t({id:e,isLoading:!1,isError:!0,errMsg:Ni(Fr("Fetch failed. Node returns {{code}}."),{code:i})})}))),[]);return(0,kr.useEffect)((()=>{if(n)for(const{id:e}of t)r(e)}),[r,t,n]),(0,e.jsx)(mi,{children:(0,e.jsx)(Ma,{})})})),Da=t=>(0,e.jsx)("div",Object.assign({className:oi},t));var Ua="src-Components-Utils-components-search-link-styles-module__main--kwUcX";const Va=({keyword:t})=>(0,e.jsx)("a",{className:Ua,href:`https://www.google.com/search?q=php+${encodeURIComponent(t)}`,target:"_blank",rel:"nofollow noreferrer",children:t});const Ba=new class{constructor(){this.id="phpExtensions",this.conf=null==bi?void 0:bi[this.id],this.isEnable=Boolean(this.conf)}},{conf:Fa}=Ba,Ha=[["Redis",Boolean(null==Fa?void 0:Fa.redis)],["SQLite3",Boolean(null==Fa?void 0:Fa.sqlite3)],["Memcache",Boolean(null==Fa?void 0:Fa.memcache)],["Memcached",Boolean(null==Fa?void 0:Fa.memcached)],["Opcache",Boolean(null==Fa?void 0:Fa.opcache)],[Fr("Opcache enabled"),Boolean(null==Fa?void 0:Fa.opcacheEnabled)],[Fr("Opcache JIT enabled"),Boolean(null==Fa?void 0:Fa.opcacheJitEnabled)],["Swoole",Boolean(null==Fa?void 0:Fa.swoole)],["Image Magick",Boolean(null==Fa?void 0:Fa.imagick)],["Graphics Magick",Boolean(null==Fa?void 0:Fa.gmagick)],["Exif",Boolean(null==Fa?void 0:Fa.exif)],["Fileinfo",Boolean(null==Fa?void 0:Fa.fileinfo)],["SimpleXML",Boolean(null==Fa?void 0:Fa.simplexml)],["Sockets",Boolean(null==Fa?void 0:Fa.sockets)],["MySQLi",Boolean(null==Fa?void 0:Fa.mysqli)],["Zip",Boolean(null==Fa?void 0:Fa.zip)],["Multibyte String",Boolean(null==Fa?void 0:Fa.mbstring)],["Phalcon",Boolean(null==Fa?void 0:Fa.phalcon)],["Xdebug",Boolean(null==Fa?void 0:Fa.xdebug)],["Zend Optimizer",Boolean(null==Fa?void 0:Fa.zendOptimizer)],["ionCube",Boolean(null==Fa?void 0:Fa.ionCube)],["Source Guardian",Boolean(null==Fa?void 0:Fa.sourceGuardian)],["LDAP",Boolean(null==Fa?void 0:Fa.ldap)],["cURL",Boolean(null==Fa?void 0:Fa.curl)]];Ha.sort(((e,t)=>{const n=e[0].toLowerCase(),r=t[0].toLowerCase();return n<r?-1:n>r?1:0}));const $a=(null==Fa?void 0:Fa.loadedExtensions)||[];$a.sort(((e,t)=>{const n=e.toLowerCase(),r=t.toLowerCase();return n<r?-1:n>r?1:0}));const Wa=()=>(0,e.jsxs)(mi,{children:[Ha.map((([t,n])=>(0,e.jsx)(vi,{name:t,lg:2,xl:3,xxl:4,children:(0,e.jsx)(yi,{isSuccess:n})},t))),Boolean($a.length)&&(0,e.jsx)(vi,{name:Fr("Loaded extensions"),children:(0,e.jsx)(Da,{children:$a.map((t=>(0,e.jsx)(Va,{keyword:t},t)))})})]});const Ka=new class{constructor(){this.id="phpInfo",this.conf=null==bi?void 0:bi[this.id],this.isEnable=Boolean(this.conf)}},qa=t=>(0,e.jsx)("a",Object.assign({className:Xr,target:"_blank"},t)),Qa=(e,t)=>{if(typeof e+typeof t!="stringstring")return!1;const n=e.split("."),r=t.split("."),i=Math.max(n.length,r.length);for(let e=0;e<i;e+=1){if(n[e]&&!r[e]&&Number(n[e])>0||Number(n[e])>Number(r[e]))return 1;if(r[e]&&!n[e]&&Number(r[e])>0||Number(n[e])<Number(r[e]))return-1}return 0};Gt({enforceActions:"observed"});const Ga=new class{constructor(){this.latestPhpVersion="",this.latestPhpDate="",this.setLatestPhpVersion=e=>{this.latestPhpVersion=e},this.setLatestPhpDate=e=>{this.latestPhpDate=e},wn(this)}};var Xa=function(e,t,n,r){return new(n||(n=Promise))((function(i,a){function o(e){try{s(r.next(e))}catch(e){a(e)}}function l(e){try{s(r.throw(e))}catch(e){a(e)}}function s(e){var t;e.done?i(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(o,l)}s((r=r.apply(e,t||[])).next())}))};const Ya=Rr((()=>{const{conf:{version:t}}=Ka,{setLatestPhpVersion:n,setLatestPhpDate:r,latestPhpVersion:i}=Ga,a=(0,kr.useCallback)((()=>Xa(void 0,void 0,void 0,(function*(){const{data:e,status:t}=yield Hi("latest-php-version");if(t===Di){const{version:t,date:i}=e;n(t),r(i)}}))),[r,n]);(0,kr.useEffect)((()=>{a()}),[a]);const o=Qa(t,i);return(0,e.jsxs)(qa,{href:"https://www.php.net/",title:Fr("Visit PHP.net Official website"),children:[t,-1===o?` ${Ni(Fr("(Latest {{latestPhpVersion}})"),{latestPhpVersion:i})}`:""]})})),Ja=Rr((()=>{const{conf:t}=Ka,n=[["PHP info",(0,e.jsx)("a",{href:"?action=phpInfoDetail",target:"_blank",children:Fr("\u{1f446} Click for detail")},"phpInfoDetail")],[Fr("Version"),(0,e.jsx)(Ya,{},"phpVersion")]],r=[[Fr("SAPI interface"),null==t?void 0:t.sapi],[Fr("Display errors"),(0,e.jsx)(yi,{isSuccess:null==t?void 0:t.displayErrors},"displayErrors")],[Fr("Error reporting"),null==t?void 0:t.errorReporting],[Fr("Max memory limit"),null==t?void 0:t.memoryLimit],[Fr("Max POST size"),null==t?void 0:t.postMaxSize],[Fr("Max upload size"),null==t?void 0:t.uploadMaxFilesize],[Fr("Max input variables"),null==t?void 0:t.maxInputVars],[Fr("Max execution time"),null==t?void 0:t.maxExecutionTime],[Fr("Timeout for socket"),null==t?void 0:t.defaultSocketTimeout],[Fr("Treatment URLs file"),(0,e.jsx)(yi,{isSuccess:null==t?void 0:t.allowUrlFopen},"allowUrlFopen")],[Fr("SMTP support"),(0,e.jsx)(yi,{isSuccess:null==t?void 0:t.smtp},"smtp")]],{disableFunctions:i,disableClasses:a}=t;i.slice().sort(),a.slice().sort();const o=[[Fr("Disabled functions"),i.length?i.map((t=>(0,e.jsx)(Va,{keyword:t},t))):"-"],[Fr("Disabled classes"),a.length?a.map((t=>(0,e.jsx)(Va,{keyword:t},t))):"-"]];return(0,e.jsxs)(mi,{children:[n.map((([t,n])=>(0,e.jsx)(vi,{name:t,children:n},t))),r.map((([t,n])=>(0,e.jsx)(vi,{name:t,lg:2,xl:3,xxl:4,children:n},t))),o.map((([t,n])=>(0,e.jsx)(vi,{name:t,children:(0,e.jsx)(Da,{children:n})},t)))]})}));Gt({enforceActions:"observed"});const Za=new class{constructor(){this.isPing=!1,this.pingItems=[],this.refs={},this.setRef=(e,t)=>{this.refs[e]=t},this.setIsPing=e=>{this.isPing=e},this.setPingItems=e=>{this.pingItems=e},this.appendPingItem=e=>{this.pingItems.push(e)},wn(this)}get pingItemsCount(){return this.pingItems.length}};var eo="src-Components-Ping-components-style-module__btn--o_4YN",to="src-Components-Ping-components-style-module__itemContainer--GLMRY",no="src-Components-Ping-components-style-module__item--kR0WD",ro="src-Components-Ping-components-style-module__itemNumber--KiUxL",io="src-Components-Ping-components-style-module__itemLine--OVM7p",ao="src-Components-Ping-components-style-module__itemTime--WiXML",oo="src-Components-Ping-components-style-module__resultContainer--xJz3t",lo="src-Components-Ping-components-style-module__result--qEqSo",so=function(e,t,n,r){return new(n||(n=Promise))((function(i,a){function o(e){try{s(r.next(e))}catch(e){a(e)}}function l(e){try{s(r.throw(e))}catch(e){a(e)}}function s(e){var t;e.done?i(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(o,l)}s((r=r.apply(e,t||[])).next())}))};const uo=Rr((()=>{const{pingItems:t}=Za,n=t.map((({time:t},n)=>(0,e.jsxs)("li",{className:no,children:[(0,e.jsx)("span",{className:ro,children:n+1<10?`0${n+1}`:n+1}),(0,e.jsx)("span",{className:io,children:" ------------ "}),(0,e.jsx)("span",{className:ao,children:`${t} ms`})]},String(n))));return(0,e.jsx)(e.Fragment,{children:n})})),co=Rr((()=>{const{pingItemsCount:t,pingItems:n}=Za,r=n.map((({time:e})=>e)),i=t?Math.floor(r.reduce(((e,t)=>e+t),0)/t):0,a=t?Number(Math.max(...r)):0,o=t?Number(Math.min(...r)):0;return(0,e.jsxs)("div",{className:lo,"data-ping":Boolean(t)||void 0,children:[(0,e.jsx)("div",{children:Ni(Fr("Times:{{times}}"),{times:t})}),(0,e.jsx)("div",{children:Ni(Fr("Min:{{min}} / Max:{{max}} / Avg:{{avg}}"),{min:o,max:a,avg:i})})]})})),fo=Rr((()=>{const{pingItemsCount:t}=Za,n=(0,kr.useRef)(0),r=(0,kr.useRef)(null),i=(0,kr.useCallback)((()=>so(void 0,void 0,void 0,(function*(){yield so(void 0,void 0,void 0,(function*(){const{appendPingItem:e}=Za,t=Number(new Date),{data:n,status:i}=yield Hi("ping");if(i===Di){const{time:i}=n,a=Number(new Date),o=1e3*i;e({time:Math.floor(a-t-o)}),setTimeout((()=>{if(!r.current)return;const e=r.current.scrollTop,t=r.current.scrollHeight;e<t&&(r.current.scrollTop=t)}),100)}})),n.current=window.setTimeout((()=>so(void 0,void 0,void 0,(function*(){yield i()}))),1e3)}))),[]),a=(0,kr.useCallback)((()=>so(void 0,void 0,void 0,(function*(){const{isPing:e,setIsPing:t}=Za;if(e)return t(!1),void clearTimeout(n.current);t(!0),yield i()}))),[i]);return(0,e.jsx)(mi,{children:(0,e.jsx)(vi,{name:(0,e.jsx)("a",{className:eo,onClick:a,children:Za.isPing?Fr("\u23f8\ufe0f Stop ping"):Fr("\u{1f446} Start ping")}),children:(0,e.jsxs)("div",{className:oo,children:[!t&&(0,e.jsx)("div",{children:Fr("No ping")}),Boolean(t)&&(0,e.jsx)("ul",{className:to,ref:r,children:(0,e.jsx)(uo,{})}),Boolean(t)&&(0,e.jsx)(co,{})]})})})}));const ho=new class{constructor(){this.id="ping",this.conf=null==bi?void 0:bi[this.id],this.isEnable=Boolean(this.conf)}};var po=n(874),vo=n.n(po);const mo=t=>(0,e.jsx)("div",Object.assign({className:Gr},t));var go=function(e,t){var n={};for(var r in e)Object.prototype.hasOwnProperty.call(e,r)&&t.indexOf(r)<0&&(n[r]=e[r]);if(null!=e&&"function"==typeof Object.getOwnPropertySymbols){var i=0;for(r=Object.getOwnPropertySymbols(e);i<r.length;i++)t.indexOf(r[i])<0&&Object.prototype.propertyIsEnumerable.call(e,r[i])&&(n[r[i]]=e[r[i]])}return n};const yo=t=>{var{ruby:n,rt:r,isResult:i=!1}=t,a=go(t,["ruby","rt","isResult"]);return(0,e.jsxs)("ruby",Object.assign({"data-is-result":i||void 0,title:Fr("Copy marks")},a,{children:[n,(0,e.jsx)("rp",{children:"("}),(0,e.jsx)("rt",{children:r}),(0,e.jsx)("rp",{children:")"})]}))},bo=t=>(0,e.jsx)("div",Object.assign({className:Yr},t));const _o=new class{constructor(){this.id="serverBenchmark",this.conf=null==bi?void 0:bi[this.id],this.isEnable=Boolean(this.conf)}};var wo=function(e,t,n,r){return new(n||(n=Promise))((function(i,a){function o(e){try{s(r.next(e))}catch(e){a(e)}}function l(e){try{s(r.throw(e))}catch(e){a(e)}}function s(e){var t;e.done?i(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(o,l)}s((r=r.apply(e,t||[])).next())}))};Gt({enforceActions:"observed"});const ko=new class{constructor(){this.appConfig=null,this.fetch=()=>wo(this,void 0,void 0,(function*(){const{isDev:e,appConfigUrls:t,appConfigUrlDev:n}=Ei;let r=!1;if(e)yield fetch(n).then((e=>e.json())).then((e=>{this.setAppConfig(e)})).catch((e=>{console.warn(e)}));else{for(let e=0;e<t.length&&(yield fetch(t[e]).then((e=>e.json())).then((e=>{this.setAppConfig(e),r=!0})).catch((e=>{console.warn(e)})),!r);e+=1);r||$i.open(Fr("Error: can not fetch remote config data, update checker is disabled."))}})),this.setAppConfig=e=>{this.appConfig=e},wn(this),this.fetch()}};Gt({enforceActions:"observed"});const xo=new class{constructor(){this.isLoading=!1,this.linkText=Fr("\u{1f446} Click to test"),this.marks={cpu:0,read:0,write:0},this.setMarks=e=>{this.marks=e},this.setIsLoading=e=>{this.isLoading=e},this.setLinkText=e=>{this.linkText=e},wn(this)}get servers(){var e;return(null===(e=null==ko?void 0:ko.appConfig)||void 0===e?void 0:e.BENCHMARKS)||null}};var So="src-Components-ServerBenchmark-components-styles-module__btn--DR6pA",zo="src-Components-ServerBenchmark-components-styles-module__aff--U6apK",Po=function(e,t,n,r){return new(n||(n=Promise))((function(i,a){function o(e){try{s(r.next(e))}catch(e){a(e)}}function l(e){try{s(r.throw(e))}catch(e){a(e)}}function s(e){var t;e.done?i(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(o,l)}s((r=r.apply(e,t||[])).next())}))};const Co=({cpu:t,read:n,write:r,date:i})=>{const a=t+n+r,o=t.toLocaleString(),l=n.toLocaleString(),s=r.toLocaleString(),u=a.toLocaleString(),c=Ni("{{cpu}} (CPU) + {{read}} (Read) + {{write}} (Write) = {{total}}",{cpu:o,read:l,write:s,total:u});return(0,e.jsxs)("div",{children:[(0,e.jsx)(yo,{ruby:o,rt:"CPU",onClick:()=>vo()(`CPU: ${o}`)})," + ",(0,e.jsx)(yo,{ruby:l,rt:Fr("Read"),onClick:()=>vo()(`Read: ${l}`)})," + ",(0,e.jsx)(yo,{ruby:s,rt:Fr("Write"),onClick:()=>vo()(`Write: ${s}`)})," = ",(0,e.jsx)(yo,{isResult:!0,ruby:u,rt:i||"",onClick:()=>vo()(c)})]})},jo=Rr((()=>{const{servers:t}=xo;if(!t)return(0,e.jsx)(bo,{children:Fr("Can not fetch marks data from GitHub.")});const n=t.map((e=>(e.total=e.detail?Object.values(e.detail).reduce(((e,t)=>e+t),0):0,e)));n.sort(((e,t)=>Number(t.total)-Number(e.total)));const r=n.map((({name:t,url:n,date:r,proberUrl:i,binUrl:a,detail:o})=>{if(!o)return null;const{cpu:l=0,read:s=0,write:u=0}=o,c=i?(0,e.jsx)("a",{href:i,target:"_blank",title:Fr("Visit prober page"),rel:"noreferrer",children:" \u{1f517} "}):"",d=a?(0,e.jsx)("a",{href:a,target:"_blank",title:Fr("Download speed test"),rel:"noreferrer",children:" \u2b07\ufe0f "}):"",f=(0,e.jsx)("a",{className:zo,href:n,target:"_blank",title:Fr("Visit the official website"),rel:"noreferrer",children:t});return(0,e.jsxs)(vi,{name:f,lg:2,xl:3,xxl:4,children:[(0,e.jsx)(Co,{cpu:l,read:s,write:u,date:r}),c,d]},t)}));return(0,e.jsx)(e.Fragment,{children:r})})),Oo=Rr((()=>{const{marks:t}=xo;return t?(0,e.jsx)(Co,Object.assign({},t)):null})),Eo=Rr((({onClick:t})=>{const{linkText:n}=xo;return(0,e.jsxs)(vi,{name:Fr("My server"),children:[(0,e.jsx)("a",{className:So,href:"#",onClick:t,children:n}),(0,e.jsx)(Oo,{})]})})),No=Rr((()=>{var t;const n=(0,kr.useCallback)((e=>Po(void 0,void 0,void 0,(function*(){e.preventDefault();const{isLoading:t,setIsLoading:n,setMarks:r,setLinkText:i}=xo;if(t)return;i(Fr("\u23f3 Testing, please wait...")),n(!0);const{data:a={},status:o}=yield Hi("benchmark"),{marks:l,seconds:s}=a;o===Di?l?(r(l),i(Fr("\u{1f446} Click to test"))):i(Fr("Network error, please try again later.")):i(429===o?Ni(Fr("\u23f3 Please wait {{seconds}}s"),{seconds:s}):Fr("Network error, please try again later.")),n(!1)}))),[]);return(0,e.jsxs)(e.Fragment,{children:[(0,e.jsx)(mo,{children:Fr("\u2694\ufe0f Different versions cannot be compared, and different time servers have different loads, just for reference.")}),(0,e.jsxs)(mi,{children:[(null===(t=_o.conf)||void 0===t?void 0:t.disabledMyServerBenchmark)||(0,e.jsx)(Eo,{onClick:n}),(0,e.jsx)(jo,{})]})]})}));var To=function(e,t,n,r){return new(n||(n=Promise))((function(i,a){function o(e){try{s(r.next(e))}catch(e){a(e)}}function l(e){try{s(r.throw(e))}catch(e){a(e)}}function s(e){var t;e.done?i(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(o,l)}s((r=r.apply(e,t||[])).next())}))};Gt({enforceActions:"observed"});const Lo=new class{constructor(){this.ID="serverInfo",this.conf=null==bi?void 0:bi[this.ID],this.enabled=Boolean(this.conf),this.serverIpv4=Fr("Loading..."),this.serverIpv6=Fr("Loading..."),this.serverLocation=null,this.setServerLocation=e=>{this.serverLocation=e},this.setServerIpv4=e=>{this.serverIpv4=e},this.setServerIpv6=e=>{this.serverIpv6=e},this.fetchServerIpv4=()=>To(this,void 0,void 0,(function*(){const{data:e,status:t}=yield Hi("serverIpv4");(null==e?void 0:e.ip)&&t===Di?this.setServerIpv4(e.ip):this.setServerIpv4("-")})),this.fetchServerIpv6=()=>To(this,void 0,void 0,(function*(){const{data:e,status:t}=yield Hi("serverIpv6");(null==e?void 0:e.ip)&&t===Di?this.setServerIpv6(e.ip):this.setServerIpv6("-")})),wn(this),this.fetchServerIpv4(),this.fetchServerIpv6()}get serverTime(){var e,t,n;return ta.isLoading?null===(e=this.conf)||void 0===e?void 0:e.serverTime:null===(n=null===(t=ta.data)||void 0===t?void 0:t[this.ID])||void 0===n?void 0:n.serverTime}get serverUptime(){var e,t,n;return ta.isLoading?null===(e=this.conf)||void 0===e?void 0:e.serverUptime:null===(n=null===(t=ta.data)||void 0===t?void 0:t[this.ID])||void 0===n?void 0:n.serverUptime}get serverUtcTime(){var e,t,n;return ta.isLoading?null===(e=this.conf)||void 0===e?void 0:e.serverUtcTime:null===(n=null===(t=ta.data)||void 0===t?void 0:t[this.ID])||void 0===n?void 0:n.serverUtcTime}get diskUsage(){var e,t,n;return ta.isLoading?null===(e=this.conf)||void 0===e?void 0:e.diskUsage:null===(n=null===(t=ta.data)||void 0===t?void 0:t[this.ID])||void 0===n?void 0:n.diskUsage}};var Io=function(e,t,n,r){return new(n||(n=Promise))((function(i,a){function o(e){try{s(r.next(e))}catch(e){a(e)}}function l(e){try{s(r.throw(e))}catch(e){a(e)}}function s(e){var t;e.done?i(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(o,l)}s((r=r.apply(e,t||[])).next())}))};const Ao=Rr((({action:t})=>{const[n,r]=(0,kr.useState)(!1),[i,a]=(0,kr.useState)(null),o=(0,kr.useCallback)((e=>Io(void 0,void 0,void 0,(function*(){if(e.preventDefault(),n)return;r(!0);const{data:i,status:o}=yield Hi(t);r(!1),i&&o===Di?a(i):$i.open(Fr("Can not fetch location."))}))),[t,n]),l=n?Fr("Loading..."):"";let s="";return n||(s=i?[i.flag,i.country,i.region,i.city].filter((e=>Boolean(e))).join(", "):Fr("\u{1f446} Click to fetch")),(0,e.jsxs)("a",{href:"#",onClick:o,title:Fr("The author only has 10,000 API requests per month, please do not abuse it."),children:[l,s]})})),Mo=Rr((()=>{const{serverUptime:{days:t,hours:n,mins:r,secs:i},serverTime:a}=Lo,o=Ni(Fr("{{days}} days {{hours}} hours {{mins}} mins {{secs}} secs"),{days:t,hours:n,mins:r,secs:i}),l=[[Fr("Server time"),a],[Fr("Server uptime"),o]];return(0,e.jsx)(e.Fragment,{children:l.map((([t,n])=>(0,e.jsx)(vi,{name:t,lg:2,xl:3,xxl:4,children:n},t)))})})),Ro=Rr((()=>{const{conf:t,serverIpv4:n,serverIpv6:r}=Lo,i=[[Fr("Server name"),null==t?void 0:t.serverName]],a=[[Fr("Server IPv4"),n],[Fr("Server IPv6"),r],[Fr("Server software"),null==t?void 0:t.serverSoftware]],o=[[Fr("Server location (IPv4)"),(0,e.jsx)(Ao,{action:"serverLocationIpv4"},"serverLocalIpv4")],[Fr("CPU model"),(null==t?void 0:t.cpuModel)||Fr("Unavailable")],[Fr("Server OS"),null==t?void 0:t.serverOs],[Fr("Script path"),null==t?void 0:t.scriptPath]];return(0,e.jsxs)(mi,{children:[i.map((([t,n])=>(0,e.jsx)(vi,{name:t,lg:2,xl:3,xxl:4,children:n},t))),(0,e.jsx)(Mo,{}),a.map((([t,n])=>(0,e.jsx)(vi,{name:t,lg:2,xl:3,xxl:4,children:n},t))),o.map((([t,n])=>(0,e.jsx)(vi,{name:t,children:n},t)))]})})),Do=Rr((()=>{const{cpuUsage:t}=fa,{idle:n}=t;return(0,e.jsx)(vi,{name:Fr("CPU usage"),children:(0,e.jsx)(Ci,{title:Ni(Fr("idle: {{idle}} \nnice: {{nice}} \nsys: {{sys}} \nuser: {{user}}"),t),value:100-n,max:100,isCapacity:!1})})})),Uo=Rr((()=>{const{max:t,value:n}=fa.memBuffers;return(0,e.jsx)(vi,{title:Fr("Buffers are in-memory block I/O buffers. They are relatively short-lived. Prior to Linux kernel version 2.4, Linux had separate page and buffer caches. Since 2.4, the page and buffer cache are unified and Buffers is raw disk blocks not represented in the page cache\u2014i.e., not file data."),name:Fr("Memory buffers"),lg:2,children:(0,e.jsx)(Ci,{value:n,max:t,isCapacity:!0})})})),Vo=Rr((()=>{const{max:t,value:n}=fa.memCached;return(0,e.jsx)(vi,{title:Fr("Cached memory is memory that Linux uses for disk caching. However, this doesn't count as \"used\" memory, since it will be freed when applications require it. Hence you don't have to worry if a large amount is being used."),name:Fr("Memory cached"),lg:2,children:(0,e.jsx)(Ci,{value:n,max:t,isCapacity:!0})})})),Bo=Rr((()=>{const{max:t,value:n}=fa.memRealUsage;return(0,e.jsx)(vi,{title:Fr('Linux comes with many commands to check memory usage. The "free" command usually displays the total amount of free and used physical and swap memory in the system, as well as the buffers used by the kernel. The "top" command provides a dynamic real-time view of a running system.'),name:Fr("Memory real usage"),children:(0,e.jsx)(Ci,{value:n,max:t,isCapacity:!0})})})),Fo=Rr((()=>{const{max:t,value:n}=fa.swapCached;return t?(0,e.jsx)(vi,{name:Fr("Swap cached"),children:(0,e.jsx)(Ci,{value:n,max:t,isCapacity:!0})}):null})),Ho=Rr((()=>{const{max:t,value:n}=fa.swapUsage;return t?(0,e.jsx)(vi,{name:Fr("Swap usage"),children:(0,e.jsx)(Ci,{value:n,max:t,isCapacity:!0})}):null})),$o=()=>(0,e.jsxs)(mi,{children:[(0,e.jsx)(ma,{}),(0,e.jsx)(Do,{}),(0,e.jsx)(Bo,{}),(0,e.jsx)(Vo,{}),(0,e.jsx)(Uo,{}),(0,e.jsx)(Ho,{}),(0,e.jsx)(Fo,{})]});const Wo=new class{constructor(){this.id="temperatureSensor"}};var Ko=function(e,t,n,r){return new(n||(n=Promise))((function(i,a){function o(e){try{s(r.next(e))}catch(e){a(e)}}function l(e){try{s(r.throw(e))}catch(e){a(e)}}function s(e){var t;e.done?i(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(o,l)}s((r=r.apply(e,t||[])).next())}))};Gt({enforceActions:"observed"});const{id:qo}=Wo;const Qo=new class{constructor(){this.items=[],this.setItems=e=>{this.items=e},this.setEnabledCard=()=>{const{setCard:e,cards:t}=Qr,n=t.find((e=>e.id===qo));n&&(n.enabled||e({id:qo,enabled:!0}))},this.fetch=()=>Ko(this,void 0,void 0,(function*(){const{data:e,status:t}=yield Hi("temperature-sensor");t===Di&&(this.setItems(e),this.setEnabledCard(),setTimeout((()=>{this.fetch()}),1e3))})),wn(this)}get itemsCount(){return this.items.length}},Go=Rr((()=>{const{itemsCount:t,items:n}=Qo;return t?(0,e.jsx)(mi,{children:n.map((({id:t,name:n,celsius:r})=>(0,e.jsx)(vi,{name:Ni(Fr("{{sensor}} temperature"),{sensor:n}),children:(0,e.jsx)(Ci,{value:r,max:150,isCapacity:!1,percentTag:"\u2103"})},t)))}):null}));Gt({enforceActions:"observed"});const Xo=new class{constructor(){this.isUpdating=!1,this.isUpdateError=!1,this.setIsUpdating=e=>{this.isUpdating=e},this.setIsUpdateError=e=>{this.isUpdateError=e},wn(this)}get newVersion(){const{appConfig:e}=ko;return e&&e.APP_VERSION&&-1===Qa(Ei.version,e.APP_VERSION)?e.APP_VERSION:""}get notiText(){return this.isUpdating?Fr("\u23f3 Updating, please wait a second..."):this.isUpdateError?Fr("\u274c Update error, click here to try again?"):this.newVersion?Ni(Fr("\u2728 Found update! Version {{oldVersion}} \u2192 {{newVersion}}"),{oldVersion:Ei.version,newVersion:this.newVersion}):""}};var Yo=function(e,t,n,r){return new(n||(n=Promise))((function(i,a){function o(e){try{s(r.next(e))}catch(e){a(e)}}function l(e){try{s(r.throw(e))}catch(e){a(e)}}function s(e){var t;e.done?i(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(o,l)}s((r=r.apply(e,t||[])).next())}))};const Jo=Rr((()=>{const t=(0,kr.useCallback)((()=>Yo(void 0,void 0,void 0,(function*(){const{setIsUpdating:e,setIsUpdateError:t}=Xo;e(!0);const{status:n}=yield Hi("update");switch(n){case Di:return void window.location.reload();case 507:case 500:return alert(Fr("Can not update file, please check the server permissions and space.")),e(!1),void t(!0)}alert(Fr("Network error, please try again later.")),e(!1),t(!0)}))),[]);return(0,e.jsx)(tl,{title:Fr("Click to update"),onClick:t,children:Xo.notiText})}));var Zo="src-Components-Title-components-styles-module__h1--z5lLy",el="src-Components-Title-components-styles-module__link--_O32A";const tl=t=>(0,e.jsx)("a",Object.assign({className:el},t)),nl=Rr((()=>{const{appUrl:t,appName:n,version:r}=Ei;return(0,e.jsx)("h1",{className:Zo,children:Xo.newVersion?(0,e.jsx)(Jo,{}):(0,e.jsx)(tl,{href:t,target:"_blank",rel:"noreferrer",children:`${n} v${r}`})})})),rl=({children:e})=>{const t=(e=>{const t=(0,kr.useRef)(document.createElement("div"));return(0,kr.useEffect)((()=>{if(e){const n=document.getElementById(e);if(!n)return;n.innerHTML="",n.appendChild(t.current)}else document.body.appendChild(t.current);return()=>{t.current.remove()}}),[e]),t.current})();return(0,xr.createPortal)(e,t)};var il="src-Components-Toast-components-styles-module__main--yKV4Y";const al=Rr((()=>{const{isOpen:t,msg:n,close:r}=$i;return t?(0,e.jsx)(rl,{children:(0,e.jsx)("div",{className:il,title:Fr("Click to close"),onClick:()=>r(),children:n})}):null}));var ol=n(478),ll="src-Components-Bootstrap-components-styles-module__app--llWF8";_i.enabled&&Qr.addCard({id:_i.ID,title:Fr("Database"),tinyTitle:Fr("DB"),priority:600,component:wi}),(()=>{const{id:e,isEnable:t}=Qi;t&&Qr.addCard({id:e,title:Fr("My Information"),tinyTitle:Fr("Mine"),priority:900,component:qi})})(),(()=>{const{id:e,isEnable:t}=ji;t&&Qr.addCard({id:e,title:Fr("Disk usage"),tinyTitle:Fr("Disk"),priority:250,component:Oi})})(),(()=>{const{id:e,isEnable:t}=na;t&&Qr.addCard({id:e,title:Fr("Network Stats"),tinyTitle:Fr("Net"),priority:200,component:sa})})(),(()=>{var e;const{id:t,isEnable:n,conf:r}=wa;n&&(null===(e=null==r?void 0:r.items)||void 0===e?void 0:e.length)&&Qr.addCard({id:t,title:Fr("Nodes"),tinyTitle:Fr("Nodes"),priority:50,component:Ra})})(),(()=>{const{id:e,isEnable:t}=Ba;t&&Qr.addCard({id:e,title:Fr("PHP Extensions"),tinyTitle:Fr("Ext"),priority:500,component:Wa})})(),(()=>{const{id:e,isEnable:t}=Ka;t&&Qr.addCard({id:e,title:Fr("PHP Information"),tinyTitle:Fr("PHP"),priority:400,component:Ja})})(),(()=>{const{id:e,isEnable:t}=ho;t&&Qr.addCard({id:e,title:Fr("Network Ping"),tinyTitle:Fr("Ping"),priority:250,component:fo})})(),(()=>{const{id:e,isEnable:t}=_o;t&&Qr.addCard({id:e,title:Fr("Server Benchmark"),tinyTitle:Fr("Becnhmark"),priority:800,component:No})})(),Lo.enabled&&Qr.addCard({id:Lo.ID,title:Fr("Server Information"),tinyTitle:Fr("Info"),priority:300,component:Ro}),(()=>{const{id:e,isEnable:t}=ua;t&&Qr.addCard({id:e,title:Fr("Server Status"),tinyTitle:Fr("Status"),priority:100,component:$o})})(),(()=>{const{id:e}=Wo;Qr.addCard({id:e,title:Fr("Temperature Sensor"),tinyTitle:Fr("Temp."),enabled:!1,priority:240,component:Go})})();const sl=()=>(0,e.jsxs)(e.Fragment,{children:[(0,e.jsx)(nl,{}),(0,e.jsx)("div",{className:ll,children:(0,e.jsxs)(ci,{children:[(0,e.jsx)(si,{}),(0,e.jsx)(Ii,{})]})}),(0,e.jsx)(Zi,{}),(0,e.jsx)(Ri,{}),(0,e.jsx)(al,{})]});(e=>{const t=navigator.userAgent,n="attachEvent";switch(!0){case t.indexOf("MSIE 8.0")>0:window[n]("onreadystatechange",(()=>{"complete"===document.readyState&&e()}));break;case t.indexOf("MSIE 9.0")>0:case t.indexOf("MSIE 10.0")>0:window[n]("onreadystatechange",(()=>{"loading"!==document.readyState&&e()}));break;default:(window[n]?"complete"===document.readyState:"loading"!==document.readyState)?e():document.addEventListener("DOMContentLoaded",e)}})((()=>{const t=document.createElement("div");document.body.innerHTML="",document.body.appendChild(t),(0,ol.s)(t).render((0,e.jsx)(sl,{}))}))}()}();
HTML;
        exit;
    }
}
namespace InnStudio\Prober\Components\MyInfo;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Utils\UtilsClientIp;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class Conf extends MyInfoConstants
{
    public function __construct()
    {
        EventsApi::on('conf', function (array $conf) {
            if (XconfigApi::isDisabled($this->ID)) {
                return $conf;
            }
            $ip = UtilsClientIp::getV4();
            $ipv4 = filter_var($ip, \FILTER_VALIDATE_IP, array(
                'flags' => \FILTER_FLAG_IPV4,
            )) ?: '';
            $ipv6 = filter_var($ip, \FILTER_VALIDATE_IP, array(
                'flags' => \FILTER_FLAG_IPV6,
            )) ?: '';
            $conf[$this->ID] = array(
                'phpLanguage' => isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '-',
                'ipv4' => $ipv4,
                'ipv6' => $ipv6,
            );
            return $conf;
        });
    }
}
namespace InnStudio\Prober\Components\MyInfo;
final class MyInfo
{
    public function __construct()
    {
        new Conf();
        new ClientLocationIpv4();
    }
}
namespace InnStudio\Prober\Components\MyInfo;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Rest\RestResponse;
use InnStudio\Prober\Components\Rest\StatusCode;
use InnStudio\Prober\Components\Utils\UtilsLocation;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class ClientLocationIpv4 extends MyInfoConstants
{
    public function __construct()
    {
        EventsApi::on('init', function ($action) {
            if ('clientLocationIpv4' !== $action) {
                return $action;
            }
            if (XconfigApi::isDisabled($this->ID)) {
                return $action;
            }
            $response = new RestResponse();
            $ip = filter_input(\INPUT_GET, 'ip', \FILTER_VALIDATE_IP, array(
                'flags' => \FILTER_FLAG_IPV4,
            ));
            if ( ! $ip) {
                $response->setStatus(StatusCode::$BAD_REQUEST)->json()->end();
            }
            $response->setData(UtilsLocation::getLocation($ip))->json()->end();
        });
    }
}
namespace InnStudio\Prober\Components\MyInfo;
class MyInfoConstants
{
    protected $ID = 'myInfo';
}
namespace InnStudio\Prober\Components\Bootstrap;
use InnStudio\Prober\Components\Config\ConfigApi;
use InnStudio\Prober\Components\Events\EventsApi;
final class Conf extends BootstrapConstants
{
    public function __construct()
    {
        EventsApi::on('conf', function (array $conf) {
            $conf[$this->ID] = array(
                'isDev' => XPROBER_IS_DEV,
                'version' => ConfigApi::$APP_VERSION,
                'appName' => ConfigApi::$APP_NAME,
                'appUrl' => ConfigApi::$APP_URL,
                'appConfigUrls' => ConfigApi::$APP_CONFIG_URLS,
                'appConfigUrlDev' => ConfigApi::$APP_CONFIG_URL_DEV,
                'authorUrl' => ConfigApi::$AUTHOR_URL,
                'authorName' => ConfigApi::$AUTHOR_NAME,
                'authorization' => isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '',
            );
            return $conf;
        });
    }
}
namespace InnStudio\Prober\Components\Bootstrap;
use InnStudio\Prober\Components\Events\EventsApi;
final class Action
{
    public function __construct()
    {
        $action = (string) filter_input(\INPUT_GET, 'action', \FILTER_DEFAULT);
        EventsApi::emit('init', $action);
        if ($action) {
            http_response_code(400);
            exit;
        }
    }
}
namespace InnStudio\Prober\Components\Bootstrap;
final class Bootstrap
{
    public function __construct()
    {
        new Action();
        new Conf();
        new Render();
    }
}
namespace InnStudio\Prober\Components\Bootstrap;
class BootstrapConstants
{
    protected $ID = 'bootstrap';
}
namespace InnStudio\Prober\Components\Bootstrap;
use InnStudio\Prober\Components\Config\ConfigApi;
use InnStudio\Prober\Components\Events\EventsApi;
final class Render
{
    public function __construct()
    {
        $appName = ConfigApi::$APP_NAME;
        $version = ConfigApi::$APP_VERSION;
        $scriptConf = json_encode(EventsApi::emit('conf', array()));
        $styleUrl = \defined('XPROBER_IS_DEV') && XPROBER_IS_DEV ? 'app.css' : "?action=style&amp;v={$version}";
        $scriptUrl = \defined('XPROBER_IS_DEV') && XPROBER_IS_DEV ? 'app.js' : "?action=script&amp;v={$version}";
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="renderer" content="webkit">
    <title>{$appName} v{$version}</title>
    <link rel="stylesheet" href="{$styleUrl}" />
    <script>window.CONF = {$scriptConf};</script>
    <script src="{$scriptUrl}" async></script>
</head>
<body>
<div style="display:flex;height:calc(100vh - 16px);width:calc(100vw - 16px);align-items:center;justify-content:center;flex-wrap:wrap;">
    <div style="font-size:15px;background:#333;color:#fff;padding:0.5rem 1rem;border-radius:10rem;box-shadow: 0 5px 10px rgba(0,0,0,0.3);">⏳ Loading...</div>
</div>
</body>
</html>
HTML;
    }
}
namespace InnStudio\Prober\Components\DiskUsage;
class DiskUsageConstants
{
    protected $ID = 'diskUsage';
}
namespace InnStudio\Prober\Components\DiskUsage;
final class DiskUsage
{
    public function __construct()
    {
        new Conf();
        new Fetch();
    }
}
namespace InnStudio\Prober\Components\DiskUsage;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Utils\UtilsDisk;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class Conf extends DiskUsageConstants
{
    public function __construct()
    {
        EventsApi::on('conf', function (array $conf) {
            if (XconfigApi::isDisabled($this->ID)) {
                return $conf;
            }
            $conf[$this->ID] = array(
                'items' => UtilsDisk::getItems(),
            );
            return $conf;
        });
    }
}
namespace InnStudio\Prober\Components\DiskUsage;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Utils\UtilsDisk;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class Fetch extends DiskUsageConstants
{
    public function __construct()
    {
        EventsApi::on('fetch', array($this, 'filter'));
        EventsApi::on('nodes', array($this, 'filter'));
    }
    public function filter(array $items)
    {
        if (XconfigApi::isDisabled($this->ID)) {
            return $items;
        }
        $items[$this->ID] = array(
            'diskUsage' => UtilsDisk::getItems(),
        );
        return $items;
    }
}
namespace InnStudio\Prober\Components\Updater;
use InnStudio\Prober\Components\Config\ConfigApi;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Rest\RestResponse;
use InnStudio\Prober\Components\Rest\StatusCode;
final class Updater
{
    public function __construct()
    {
        EventsApi::on('init', function ($action) {
            if ('update' !== $action) {
                return $action;
            }
            $response = new RestResponse();
            // check file writable
            if ( ! is_writable(__FILE__)) {
                $response->setStatus(StatusCode::$INSUFFICIENT_STORAGE)->end();
            }
            $code = '';
            foreach (ConfigApi::$UPDATE_PHP_URLS as $url) {
                $code = (string) file_get_contents($url);
                if ('' !== trim($code)) {
                    break;
                }
            }
            if ( ! $code) {
                $response->setStatus(StatusCode::$NOT_FOUND)->end();
            }
            // prevent update file on dev mode
            if (\defined('XPROBER_IS_DEV') && XPROBER_IS_DEV) {
                $response->end();
            }
            if ((bool) file_put_contents(__FILE__, $code)) {
                if (\function_exists('opcache_invalidate')) {
                    opcache_invalidate(__FILE__, true) || opcache_reset();
                }
                $response->end();
            }
            $response->setStatus(StatusCode::$INTERNAL_SERVER_ERROR)->end();
        });
    }
}
namespace InnStudio\Prober\Components\NetworkStats;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Utils\UtilsApi;
use InnStudio\Prober\Components\Utils\UtilsNetwork;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class Conf extends NetworkStatsConstants
{
    public function __construct()
    {
        UtilsApi::isWin() || EventsApi::on('conf', function (array $conf) {
            if (XconfigApi::isDisabled($this->ID)) {
                return $conf;
            }
            $conf[$this->ID] = array(
                'networks' => UtilsNetwork::getStats(),
                'timestamp' => time(),
            );
            return $conf;
        });
    }
}
namespace InnStudio\Prober\Components\NetworkStats;
final class NetworkStats
{
    public function __construct()
    {
        new Conf();
        new Fetch();
    }
}
namespace InnStudio\Prober\Components\NetworkStats;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Utils\UtilsApi;
use InnStudio\Prober\Components\Utils\UtilsNetwork;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class Fetch extends NetworkStatsConstants
{
    public function __construct()
    {
        if ( ! UtilsApi::isWin()) {
            EventsApi::on('fetch', array($this, 'filter'));
            EventsApi::on('nodes', array($this, 'filter'));
        }
    }
    public function filter(array $items)
    {
        if (XconfigApi::isDisabled($this->ID)) {
            return $items;
        }
        $items[$this->ID] = array(
            'networks' => UtilsNetwork::getStats(),
            'timestamp' => time(),
        );
        return $items;
    }
}
namespace InnStudio\Prober\Components\NetworkStats;
class NetworkStatsConstants
{
    protected $ID = 'networkStats';
}
namespace InnStudio\Prober\Components\Fetch;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Rest\RestResponse;
final class Fetch
{
    public function __construct()
    {
        EventsApi::on('init', function ($action) {
            if ('fetch' === $action) {
                EventsApi::emit('fetchBefore');
                $response = new RestResponse(EventsApi::emit('fetch', array()));
                $response->json()->end();
            }
            return $action;
        }, 100);
    }
}
namespace InnStudio\Prober\Components\Nodes;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class Conf extends NodesApi
{
    public function __construct()
    {
        EventsApi::on('conf', function (array $conf) {
            if (XconfigApi::isDisabled($this->ID)) {
                return $conf;
            }
            $conf[$this->ID] = array(
                'items' => $this->getNodes(),
            );
            return $conf;
        });
    }
}
namespace InnStudio\Prober\Components\Nodes;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Rest\RestResponse;
use InnStudio\Prober\Components\Rest\StatusCode;
final class Fetch extends NodesApi
{
    public function __construct()
    {
        EventsApi::on('init', function ($action) {
            switch ($action) {
                case 'nodes':
                    EventsApi::emit('fetchNodesBefore');
                    $response = new RestResponse(EventsApi::emit('nodes', array()));
                    $response->json()->end();
                    // no break
                case 'node':
                    EventsApi::emit('fetchNodeBefore');
                    $nodeId = filter_input(\INPUT_GET, 'nodeId', \FILTER_DEFAULT);
                    $response = new RestResponse();
                    if ( ! $nodeId) {
                        $response->setStatus(StatusCode::$BAD_REQUEST)->json()->end();
                    }
                    $data = $this->getNodeData($nodeId);
                    if ( ! $data) {
                        $response->setStatus(StatusCode::$NO_CONTENT)->json()->end();
                    }
                    $response->setData($data)->json()->end();
            }
            return $action;
        }, 100);
    }
    private function getNodeData($nodeId)
    {
        foreach ($this->getNodes() as $item) {
            if ( ! isset($item['id']) || ! isset($item['url']) || $item['id'] !== $nodeId) {
                continue;
            }
            return $this->getRemoteContent("{$item['url']}?action=fetch");
        }
    }
    private function getRemoteContent($url)
    {
        $content = '';
        if (\function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, array(
                \CURLOPT_URL => $url,
                \CURLOPT_RETURNTRANSFER => true,
            ));
            $content = curl_exec($ch);
            curl_close($ch);
            return json_decode($content, true) ?: null;
        }
        return json_decode(file_get_contents($url), true) ?: null;
    }
}
namespace InnStudio\Prober\Components\Nodes;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
class NodesApi
{
    public $ID = 'nodes';
    public function getNodes()
    {
        $items = XconfigApi::getNodes();
        if ( ! $items || ! \is_array($items)) {
            return array();
        }
        return array_filter(array_map(function ($item) {
            if (2 !== \count($item)) {
                return;
            }
            return array(
                'id' => $item[0],
                'url' => $item[1],
            );
        }, $items));
    }
}
namespace InnStudio\Prober\Components\Nodes;
final class Nodes
{
    public function __construct()
    {
        new Conf();
        new Fetch();
    }
}
/**
 * The file is automatically generated.
 */
namespace InnStudio\Prober\Components\Config;
class ConfigApi
{
    public static $APP_VERSION                  = '8.18';
    public static $APP_NAME                     = 'X Prober';
    public static $APP_URL                      = 'https://github.com/kmvan/x-prober';
    public static $APP_CONFIG_URLS              = array('https://raw.githubusercontent.com/kmvan/x-prober/master/AppConfig.json', 'https://api.inn-studio.com/download/?id=xprober-config');
    public static $APP_CONFIG_URL_DEV           = 'http://localhost:8000/AppConfig.json';
    public static $APP_TEMPERATURE_SENSOR_URL   = 'http://127.0.0.1';
    public static $APP_TEMPERATURE_SENSOR_PORTS = array(2048, 4096);
    public static $AUTHOR_URL                   = 'https://inn-studio.com/prober';
    public static $UPDATE_PHP_URLS              = array('https://raw.githubusercontent.com/kmvan/x-prober/master/dist/prober.php', 'https://api.inn-studio.com/download/?id=xprober');
    public static $AUTHOR_NAME                  = 'INN STUDIO';
    public static $LATEST_PHP_STABLE_VERSION    = '8';
    public static $LATEST_NGINX_STABLE_VERSION  = '1.22.0';
}
namespace InnStudio\Prober\Components\Database;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
use PDO;
use SQLite3;
final class Conf extends DatabaseConstants
{
    public function __construct()
    {
        EventsApi::on('conf', function (array $conf) {
            if (XconfigApi::isDisabled($this->ID)) {
                return $conf;
            }
            $sqlite3Version = class_exists('SQLite3') ? SQLite3::version() : false;
            $conf[$this->ID] = array(
                'sqlite3' => $sqlite3Version ? $sqlite3Version['versionString'] : false,
                'sqliteLibversion' => \function_exists('sqlite_libversion') ? sqlite_libversion() : false,
                'mysqliClientVersion' => \function_exists('mysqli_get_client_version') ? mysqli_get_client_version() : false,
                'mongo' => class_exists('Mongo'),
                'mongoDb' => class_exists('MongoDB'),
                'postgreSql' => \function_exists('pg_connect'),
                'paradox' => \function_exists('px_new'),
                'msSql' => \function_exists('sqlsrv_server_info'),
                'pdo' => class_exists('PDO') ? implode(',', PDO::getAvailableDrivers()) : false,
            );
            return $conf;
        });
    }
}
namespace InnStudio\Prober\Components\Database;
class DatabaseConstants
{
    protected $ID = 'database';
}
namespace InnStudio\Prober\Components\Database;
final class Database
{
    public function __construct()
    {
        new Conf();
    }
}
namespace InnStudio\Prober\Components\Xconfig;
use InnStudio\Prober\Components\Utils\UtilsApi;
final class XconfigApi
{
    private static $conf;
    private static $filename = 'xconfig.json';
    public static function isDisabled($id)
    {
        return \in_array($id, self::get('disabled') ?: array(), true);
    }
    public static function getNodes()
    {
        return self::get('nodes') ?: array();
    }
    public static function get($id = null)
    {
        self::setConf();
        if ($id) {
            return isset(self::$conf[$id]) ? self::$conf[$id] : null;
        }
        return self::$conf;
    }
    private static function getFilePath()
    {
        if ( ! \defined('\\XPROBER_DIR')) {
            return '';
        }
        if (\defined('\\XPROBER_IS_DEV') && XPROBER_IS_DEV) {
            return \dirname(XPROBER_DIR) . '/' . self::$filename;
        }
        return XPROBER_DIR . '/' . self::$filename;
    }
    private static function setConf()
    {
        if (null !== self::$conf) {
            return;
        }
        if ( ! is_readable(self::getFilePath())) {
            self::$conf = null;
            return;
        }
        $conf = UtilsApi::jsonDecode(file_get_contents(self::getFilePath()));
        if ( ! $conf) {
            self::$conf = null;
            return;
        }
        self::$conf = $conf;
    }
}
namespace InnStudio\Prober\Components\ServerInfo;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Rest\RestResponse;
use InnStudio\Prober\Components\Utils\UtilsServerIp;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class ServerInitIpv4 extends ServerInfoConstants
{
    public function __construct()
    {
        EventsApi::on('init', function ($action) {
            if ('serverIpv4' !== $action) {
                return $action;
            }
            if (XconfigApi::isDisabled($this->ID)) {
                return $action;
            }
            if (XconfigApi::isDisabled($this->FEATURE_SERVER_IP)) {
                return $action;
            }
            $response = new RestResponse();
            $response->setData(array(
                'ip' => UtilsServerIp::getV4(),
            ))->json()->end();
        });
    }
}
namespace InnStudio\Prober\Components\ServerInfo;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Utils\UtilsCpu;
use InnStudio\Prober\Components\Utils\UtilsDisk;
use InnStudio\Prober\Components\Utils\UtilsTime;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class Conf extends ServerInfoConstants
{
    public function __construct()
    {
        EventsApi::on('conf', function (array $conf) {
            if (XconfigApi::isDisabled($this->ID)) {
                return $conf;
            }
            $conf[$this->ID] = array(
                'serverName' => $this->getServerInfo('SERVER_NAME'),
                'serverUtcTime' => UtilsTime::getUtcTime(),
                'serverTime' => UtilsTime::getTime(),
                'serverUptime' => UtilsTime::getUptime(),
                'serverIp' => XconfigApi::isDisabled('serverIp') ? '-' : $this->getServerInfo('SERVER_ADDR'),
                'serverSoftware' => $this->getServerInfo('SERVER_SOFTWARE'),
                'phpVersion' => \PHP_VERSION,
                'cpuModel' => UtilsCpu::getModel(),
                'serverOs' => php_uname(),
                'scriptPath' => __FILE__,
                'diskUsage' => UtilsDisk::getItems(),
            );
            return $conf;
        });
    }
    private function getServerInfo($key)
    {
        return isset($_SERVER[$key]) ? $_SERVER[$key] : '';
    }
}
namespace InnStudio\Prober\Components\ServerInfo;
final class ServerInfo
{
    public function __construct()
    {
        new Conf();
        new Fetch();
        new ServerInitIpv4();
        new ServerInitIpv6();
        new ServerLocationIpv4();
    }
}
namespace InnStudio\Prober\Components\ServerInfo;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Rest\RestResponse;
use InnStudio\Prober\Components\Utils\UtilsServerIp;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class ServerInitIpv6 extends ServerInfoConstants
{
    public function __construct()
    {
        EventsApi::on('init', function ($action) {
            if ('serverIpv6' !== $action) {
                return $action;
            }
            if (XconfigApi::isDisabled($this->ID)) {
                return $action;
            }
            if (XconfigApi::isDisabled($this->FEATURE_SERVER_IP)) {
                return $action;
            }
            $response = new RestResponse();
            $response->setData(array(
                'ip' => UtilsServerIp::getV6(),
            ))->json()->end();
        });
    }
}
namespace InnStudio\Prober\Components\ServerInfo;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Rest\RestResponse;
use InnStudio\Prober\Components\Rest\StatusCode;
use InnStudio\Prober\Components\Utils\UtilsLocation;
use InnStudio\Prober\Components\Utils\UtilsServerIp;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class ServerLocationIpv4 extends ServerInfoConstants
{
    public function __construct()
    {
        EventsApi::on('init', function ($action) {
            if ('serverLocationIpv4' !== $action) {
                return $action;
            }
            if (XconfigApi::isDisabled($this->ID)) {
                return $action;
            }
            if (XconfigApi::isDisabled($this->FEATURE_SERVER_IP)) {
                return $action;
            }
            $response = new RestResponse();
            $ip = UtilsServerIp::getV4();
            if ( ! $ip) {
                $response->setStatus(StatusCode::$BAD_REQUEST)->json()->end();
            }
            $response->setData(UtilsLocation::getLocation($ip))->json()->end();
        });
    }
}
namespace InnStudio\Prober\Components\ServerInfo;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Utils\UtilsDisk;
use InnStudio\Prober\Components\Utils\UtilsTime;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class Fetch extends ServerInfoConstants
{
    public function __construct()
    {
        EventsApi::on('fetch', array($this, 'filter'));
        EventsApi::on('nodes', array($this, 'filter'));
    }
    public function filter(array $items)
    {
        if (XconfigApi::isDisabled($this->ID)) {
            return $items;
        }
        $items[$this->ID] = array(
            'serverUtcTime' => UtilsTime::getUtcTime(),
            'serverTime' => UtilsTime::getTime(),
            'serverUptime' => UtilsTime::getUptime(),
            'diskUsage' => UtilsDisk::getItems(),
        );
        return $items;
    }
}
namespace InnStudio\Prober\Components\ServerInfo;
class ServerInfoConstants
{
    protected $ID = 'serverInfo';
    protected $FEATURE_SERVER_IP = 'serverIp';
}
namespace InnStudio\Prober\Components\Utils;
final class UtilsDisk
{
    public static function getItems()
    {
        switch (\PHP_OS) {
            case 'Linux':
                return self::getLinuxItems();
            default:
                return;
        }
    }
    private static function getLinuxItems()
    {
        if ( ! \function_exists('shell_exec')) {
            return array(
                array(
                    'id' => __DIR__,
                    'free' => disk_free_space(__DIR__),
                    'total' => disk_total_space(__DIR__),
                ),
            );
        }
        $items = array();
        $dfLines = explode("\n", shell_exec('df -k'));
        if (\count($dfLines) <= 1) {
            return $items;
        }
        $dfLines = \array_slice($dfLines, 1);
        $fsExclude = array('tmpfs', 'run', 'dev');
        foreach ($dfLines as $dfLine) {
            $dfObj = explode(' ', preg_replace('/\\s+/', ' ', $dfLine));
            if (\count($dfObj) < 6) {
                continue;
            }
            $dfFs = $dfObj[0];
            $dfTotal = (int) $dfObj[1];
            $dfAvailable = (int) $dfObj[3];
            $dfMountedOn = $dfObj[5];
            if (\in_array($dfFs, $fsExclude, true)) {
                continue;
            }
            $free = $dfAvailable * 1024;
            $total = $dfTotal * 1024;
            $items[] = array(
                'id' => "{$dfFs}:{$dfMountedOn}",
                'free' => $free,
                'total' => $total,
            );
        }
        if ( ! $items) {
            return array();
        }
        // sort by total desc
        usort($items, function ($a, $b) {
            return $b['total'] - $a['total'];
        });
        return $items;
    }
}
namespace InnStudio\Prober\Components\Utils;
final class UtilsClientIp
{
    public static function getV4()
    {
        $keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        foreach ($keys as $key) {
            if ( ! isset($_SERVER[$key])) {
                continue;
            }
            $ip = array_filter(explode(',', $_SERVER[$key]));
            $ip = filter_var(end($ip), \FILTER_VALIDATE_IP);
            if ($ip) {
                return $ip;
            }
        }
        return '';
    }
}
namespace InnStudio\Prober\Components\Utils;
final class UtilsMemory
{
    public static function getMemoryUsage($key)
    {
        $key = ucfirst($key);
        if (UtilsApi::isWin()) {
            return 0;
        }
        static $memInfo = null;
        if (null === $memInfo) {
            $memInfoFile = '/proc/meminfo';
            if ( ! @is_readable($memInfoFile)) {
                $memInfo = 0;
                return 0;
            }
            $memInfo = file_get_contents($memInfoFile);
            $memInfo = str_replace(array(
                ' kB',
                '  ',
            ), '', $memInfo);
            $lines = array();
            foreach (explode("\n", $memInfo) as $line) {
                if ( ! $line) {
                    continue;
                }
                $line = explode(':', $line);
                $lines[$line[0]] = (float) $line[1] * 1024;
            }
            $memInfo = $lines;
        }
        if ( ! isset($memInfo['MemTotal'])) {
            return 0;
        }
        switch ($key) {
            case 'MemRealUsage':
                if (isset($memInfo['MemAvailable'])) {
                    return $memInfo['MemTotal'] - $memInfo['MemAvailable'];
                }
                if (isset($memInfo['MemFree'])) {
                    if (isset($memInfo['Buffers'], $memInfo['Cached'])) {
                        return $memInfo['MemTotal'] - $memInfo['MemFree'] - $memInfo['Buffers'] - $memInfo['Cached'];
                    }
                    return $memInfo['MemTotal'] - $memInfo['Buffers'];
                }
                return 0;
            case 'MemUsage':
                return isset($memInfo['MemFree']) ? $memInfo['MemTotal'] - $memInfo['MemFree'] : 0;
            case 'SwapUsage':
                if ( ! isset($memInfo['SwapTotal']) || ! isset($memInfo['SwapFree'])) {
                    return 0;
                }
                return $memInfo['SwapTotal'] - $memInfo['SwapFree'];
        }
        return isset($memInfo[$key]) ? $memInfo[$key] : 0;
    }
}
namespace InnStudio\Prober\Components\Utils;
final class UtilsLocation
{
    /**
     * Get IP location.
     *
     * @param [string] $ip
     *
     * @return array|null $args
     *                    $args['country'] string Country, e.g, China
     *                    $args['region'] string Region, e.g, Heilongjiang
     *                    $args['city'] string City, e.g, Mohe
     *                    $args['flag'] string Emoji string, e,g, 🇨🇳
     */
    public static function getLocation($ip)
    {
        $url = "http://api.ipstack.com/{$ip}?access_key=e4394fd12dbbefa08612306ca05baca3&format=1";
        $content = '';
        if (\function_exists('\\curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, array(
                \CURLOPT_URL => $url,
                \CURLOPT_RETURNTRANSFER => true,
            ));
            $content = curl_exec($ch);
            curl_close($ch);
        } else {
            $content = file_get_contents($url);
        }
        $item = json_decode($content, true) ?: null;
        if ( ! $item) {
            return;
        }
        return array(
            'country' => isset($item['country_name']) ? $item['country_name'] : '',
            'region' => isset($item['region_name']) ? $item['region_name'] : '',
            'city' => isset($item['city']) ? $item['city'] : '',
            'flag' => isset($item['location']['country_flag_emoji']) ? $item['location']['country_flag_emoji'] : '',
        );
    }
}
namespace InnStudio\Prober\Components\Utils;
final class UtilsTime
{
    public static function getTime()
    {
        return date('Y-m-d H:i:s');
    }
    public static function getUtcTime()
    {
        return gmdate('Y/m/d H:i:s');
    }
    public static function getUptime()
    {
        $filePath = '/proc/uptime';
        if ( ! @is_file($filePath)) {
            return array(
                'days' => 0,
                'hours' => 0,
                'mins' => 0,
                'secs' => 0,
            );
        }
        $str = file_get_contents($filePath);
        $num = (float) $str;
        $secs = (int) fmod($num, 60);
        $num = (int) ($num / 60);
        $mins = (int) $num % 60;
        $num = (int) ($num / 60);
        $hours = (int) $num % 24;
        $num = (int) ($num / 24);
        $days = (int) $num;
        return array(
            'days' => $days,
            'hours' => $hours,
            'mins' => $mins,
            'secs' => $secs,
        );
    }
}
namespace InnStudio\Prober\Components\Utils;
final class UtilsApi
{
    public static function jsonDecode($json, $depth = 512, $options = 0)
    {
        // search and remove comments like /* */ and //
        $json = preg_replace("#(/\\*([^*]|[\r\n]|(\\*+([^*/]|[\r\n])))*\\*+/)|([\\s\t]//.*)|(^//.*)#", '', $json);
        if (\PHP_VERSION_ID >= 50400) {
            return json_decode($json, true, $depth, $options);
        }
        if (\PHP_VERSION_ID >= 50300) {
            return json_decode($json, true, $depth);
        }
        return json_decode($json, true);
    }
    public static function setFileCacheHeader()
    {
        // 1 year expired
        $seconds = 3600 * 24 * 30 * 12;
        $ts = gmdate('D, d M Y H:i:s', (int) $_SERVER['REQUEST_TIME'] + $seconds) . ' GMT';
        header("Expires: {$ts}");
        header('Pragma: cache');
        header("Cache-Control: public, max-age={$seconds}");
    }
    public static function getErrNameByCode($code)
    {
        if (0 === (int) $code) {
            return '';
        }
        $levels = array(
            \E_ALL => 'E_ALL',
            \E_USER_DEPRECATED => 'E_USER_DEPRECATED',
            \E_DEPRECATED => 'E_DEPRECATED',
            \E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            \E_STRICT => 'E_STRICT',
            \E_USER_NOTICE => 'E_USER_NOTICE',
            \E_USER_WARNING => 'E_USER_WARNING',
            \E_USER_ERROR => 'E_USER_ERROR',
            \E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            \E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            \E_CORE_WARNING => 'E_CORE_WARNING',
            \E_CORE_ERROR => 'E_CORE_ERROR',
            \E_NOTICE => 'E_NOTICE',
            \E_PARSE => 'E_PARSE',
            \E_WARNING => 'E_WARNING',
            \E_ERROR => 'E_ERROR',
        );
        $result = '';
        foreach ($levels as $number => $name) {
            if (($code & $number) === $number) {
                $result .= ('' !== $result ? ', ' : '') . $name;
            }
        }
        return $result;
    }
    public static function isWin()
    {
        return \PHP_OS === 'WINNT';
    }
}
namespace InnStudio\Prober\Components\Utils;
final class UtilsNetwork
{
    public static function getStats()
    {
        $filePath = '/proc/net/dev';
        if ( ! @is_readable($filePath)) {
            return;
        }
        static $eths = null;
        if (null !== $eths) {
            return $eths;
        }
        $lines = file($filePath);
        unset($lines[0], $lines[1]);
        $eths = array();
        foreach ($lines as $line) {
            $line = preg_replace('/\\s+/', ' ', trim($line));
            $lineArr = explode(':', $line);
            $numberArr = explode(' ', trim($lineArr[1]));
            $rx = (float) $numberArr[0];
            $tx = (float) $numberArr[8];
            if ( ! $rx && ! $tx) {
                continue;
            }
            $eths[] = array(
                'id' => $lineArr[0],
                'rx' => $rx,
                'tx' => $tx,
            );
        }
        return $eths;
    }
}
namespace InnStudio\Prober\Components\Utils;
final class UtilsServerIp
{
    public static function getV4()
    {
        return self::getV4ViaInnStudioCom() ?: self::getV4ViaIpv6TestCom() ?: self::getV4Local();
    }
    public static function getV6()
    {
        return self::getV6ViaInnStudioCom() ?: self::getV6ViaIpv6TestCom() ?: self::getV6Local();
    }
    private static function getV4Local()
    {
        $content = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
        return filter_var($content, \FILTER_VALIDATE_IP, array(
            'flags' => \FILTER_FLAG_IPV4,
        )) ?: '';
    }
    private static function getV6Local()
    {
        $content = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
        return filter_var($content, \FILTER_VALIDATE_IP, array(
            'flags' => \FILTER_FLAG_IPV6,
        )) ?: '';
    }
    private static function getV4ViaInnStudioCom()
    {
        return self::getContent('https://ipv4.inn-studio.com/ip/', 4);
    }
    private static function getV6ViaInnStudioCom()
    {
        return self::getContent('https://ipv6.inn-studio.com/ip/', 6);
    }
    private static function getV4ViaIpv6TestCom()
    {
        return self::getContent('https://v4.ipv6-test.com/api/myip.php', 4);
    }
    private static function getV6ViaIpv6TestCom()
    {
        return self::getContent('https://v6.ipv6-test.com/api/myip.php', 6);
    }
    private static function getContent($url, $type)
    {
        $content = '';
        if (\function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, array(
                \CURLOPT_URL => $url,
                \CURLOPT_RETURNTRANSFER => true,
            ));
            $content = curl_exec($ch);
            curl_close($ch);
        } else {
            $content = file_get_contents($url);
        }
        return (string) filter_var($content, \FILTER_VALIDATE_IP, array(
            'flags' => 6 === $type ? \FILTER_FLAG_IPV6 : \FILTER_FLAG_IPV4,
        )) ?: '';
    }
}
namespace InnStudio\Prober\Components\Utils;
use COM;
final class UtilsCpu
{
    private static $HW_IMPLEMENTER = array(
        '0x41' => array(array(
            '0x810' => 'ARM810',
            '0x920' => 'ARM920',
            '0x922' => 'ARM922',
            '0x926' => 'ARM926',
            '0x940' => 'ARM940',
            '0x946' => 'ARM946',
            '0x966' => 'ARM966',
            '0xa20' => 'ARM1020',
            '0xa22' => 'ARM1022',
            '0xa26' => 'ARM1026',
            '0xb02' => 'ARM11 MPCore',
            '0xb36' => 'ARM1136',
            '0xb56' => 'ARM1156',
            '0xb76' => 'ARM1176',
            '0xc05' => 'Cortex-A5',
            '0xc07' => 'Cortex-A7',
            '0xc08' => 'Cortex-A8',
            '0xc09' => 'Cortex-A9',
            '0xc0d' => 'Cortex-A17/A12',
            '0xc0f' => 'Cortex-A15',
            '0xc0e' => 'Cortex-A17',
            '0xc14' => 'Cortex-R4',
            '0xc15' => 'Cortex-R5',
            '0xc17' => 'Cortex-R7',
            '0xc18' => 'Cortex-R8',
            '0xc20' => 'Cortex-M0',
            '0xc21' => 'Cortex-M1',
            '0xc23' => 'Cortex-M3',
            '0xc24' => 'Cortex-M4',
            '0xc27' => 'Cortex-M7',
            '0xc60' => 'Cortex-M0+',
            '0xd01' => 'Cortex-A32',
            '0xd02' => 'Cortex-A34',
            '0xd03' => 'Cortex-A53',
            '0xd04' => 'Cortex-A35',
            '0xd05' => 'Cortex-A55',
            '0xd06' => 'Cortex-A65',
            '0xd07' => 'Cortex-A57',
            '0xd08' => 'Cortex-A72',
            '0xd09' => 'Cortex-A73',
            '0xd0a' => 'Cortex-A75',
            '0xd0b' => 'Cortex-A76',
            '0xd0c' => 'Neoverse-N1',
            '0xd0d' => 'Cortex-A77',
            '0xd0e' => 'Cortex-A76AE',
            '0xd13' => 'Cortex-R52',
            '0xd15' => 'Cortex-R82',
            '0xd16' => 'Cortex-R52+',
            '0xd20' => 'Cortex-M23',
            '0xd21' => 'Cortex-M33',
            '0xd22' => 'Cortex-M55',
            '0xd23' => 'Cortex-M85',
            '0xd40' => 'Neoverse-V1',
            '0xd41' => 'Cortex-A78',
            '0xd42' => 'Cortex-A78AE',
            '0xd43' => 'Cortex-A65AE',
            '0xd44' => 'Cortex-X1',
            '0xd46' => 'Cortex-A510',
            '0xd47' => 'Cortex-A710',
            '0xd48' => 'Cortex-X2',
            '0xd49' => 'Neoverse-N2',
            '0xd4a' => 'Neoverse-E1',
            '0xd4b' => 'Cortex-A78C',
            '0xd4c' => 'Cortex-X1C',
            '0xd4d' => 'Cortex-A715',
            '0xd4e' => 'Cortex-X3',
            '0xd4f' => 'Neoverse-V2',
            '0xd80' => 'Cortex-A520',
            '0xd81' => 'Cortex-A720',
            '0xd82' => 'Cortex-X4',
        ), 'ARM'),
        '0x42' => array(array(
            '0x0f' => 'Brahma-B15',
            '0x100' => 'Brahma-B53',
            '0x516' => 'ThunderX2',
        ), 'Broadcom'),
        '0x43' => array(array(
            '0x0a0' => 'ThunderX',
            '0x0a1' => 'ThunderX-88XX',
            '0x0a2' => 'ThunderX-81XX',
            '0x0a3' => 'ThunderX-83XX',
            '0x0af' => 'ThunderX2-99xx',
            '0x0b0' => 'OcteonTX2',
            '0x0b1' => 'OcteonTX2-98XX',
            '0x0b2' => 'OcteonTX2-96XX',
            '0x0b3' => 'OcteonTX2-95XX',
            '0x0b4' => 'OcteonTX2-95XXN',
            '0x0b5' => 'OcteonTX2-95XXMM',
            '0x0b6' => 'OcteonTX2-95XXO',
            '0x0b8' => 'ThunderX3-T110',
        ), 'Cavium'),
        '0x44' => array(array(
            '0xa10' => 'SA110',
            '0xa11' => 'SA1100',
        ), 'DEC'),
        '0x46' => array(array(
            '0x001' => 'A64FX',
        ), 'FUJITSU'),
        '0x48' => array(array(
            '0xd01' => 'TaiShan-v110', // used in Kunpeng-920 SoC
            '0xd02' => 'TaiShan-v120', // used in Kirin 990A and 9000S SoCs
            '0xd40' => 'Cortex-A76', // HiSilicon uses this ID though advertises A76
            '0xd41' => 'Cortex-A77', // HiSilicon uses this ID though advertises A77
        ), 'HiSilicon'),
        '0x49' => array(null, 'Infineon'),
        '0x4d' => array(null, 'Motorola/Freescale'),
        '0x4e' => array(array(
            '0x000' => 'Denver',
            '0x003' => 'Denver 2',
            '0x004' => 'Carmel',
        ), 'NVIDIA'),
        '0x50' => array(array(
            '0x000' => 'X-Gene',
        ), 'APM'),
        '0x51' => array(array(
            '0x00f' => 'Scorpion',
            '0x02d' => 'Scorpion',
            '0x04d' => 'Krait',
            '0x06f' => 'Krait',
            '0x201' => 'Kryo',
            '0x205' => 'Kryo',
            '0x211' => 'Kryo',
            '0x800' => 'Falkor-V1/Kryo',
            '0x801' => 'Kryo-V2',
            '0x802' => 'Kryo-3XX-Gold',
            '0x803' => 'Kryo-3XX-Silver',
            '0x804' => 'Kryo-4XX-Gold',
            '0x805' => 'Kryo-4XX-Silver',
            '0xc00' => 'Falkor',
            '0xc01' => 'Saphira',
        ), 'Qualcomm'),
        '0x53' => array(array(
            '0x001' => 'exynos-m1',
            '0x002' => 'exynos-m3',
            '0x003' => 'exynos-m4',
            '0x004' => 'exynos-m5',
        ), 'Samsung'),
        '0x56' => array(array(
            '0x131' => 'Feroceon-88FR131',
            '0x581' => 'PJ4/PJ4b',
            '0x584' => 'PJ4B-MP',
        ), 'Marvell'),
        '0x61' => array(array(
            '0x000' => 'Swift',
            '0x001' => 'Cyclone',
            '0x002' => 'Typhoon',
            '0x003' => 'Typhoon/Capri',
            '0x004' => 'Twister',
            '0x005' => 'Twister/Elba/Malta',
            '0x006' => 'Hurricane',
            '0x007' => 'Hurricane/Myst',
            '0x008' => 'Monsoon',
            '0x009' => 'Mistral',
            '0x00b' => 'Vortex',
            '0x00c' => 'Tempest',
            '0x00f' => 'Tempest-M9',
            '0x010' => 'Vortex/Aruba',
            '0x011' => 'Tempest/Aruba',
            '0x012' => 'Lightning',
            '0x013' => 'Thunder',
            '0x020' => 'Icestorm-A14',
            '0x021' => 'Firestorm-A14',
            '0x022' => 'Icestorm-M1',
            '0x023' => 'Firestorm-M1',
            '0x024' => 'Icestorm-M1-Pro',
            '0x025' => 'Firestorm-M1-Pro',
            '0x026' => 'Thunder-M10',
            '0x028' => 'Icestorm-M1-Max',
            '0x029' => 'Firestorm-M1-Max',
            '0x030' => 'Blizzard-A15',
            '0x031' => 'Avalanche-A15',
            '0x032' => 'Blizzard-M2',
            '0x033' => 'Avalanche-M2',
            '0x034' => 'Blizzard-M2-Pro',
            '0x035' => 'Avalanche-M2-Pro',
            '0x036' => 'Sawtooth-A16',
            '0x037' => 'Everest-A16',
            '0x038' => 'Blizzard-M2-Max',
            '0x039' => 'Avalanche-M2-Max',
        ), 'Apple'),
        '0x66' => array(array(
            '0x526' => 'FA526',
            '0x626' => 'FA626',
        ), 'Faraday'),
        '0x69' => array(array(
            '0x200' => 'i80200',
            '0x210' => 'PXA250A',
            '0x212' => 'PXA210A',
            '0x242' => 'i80321-400',
            '0x243' => 'i80321-600',
            '0x290' => 'PXA250B/PXA26x',
            '0x292' => 'PXA210B',
            '0x2c2' => 'i80321-400-B0',
            '0x2c3' => 'i80321-600-B0',
            '0x2d0' => 'PXA250C/PXA255/PXA26x',
            '0x2d2' => 'PXA210C',
            '0x411' => 'PXA27x',
            '0x41c' => 'IPX425-533',
            '0x41d' => 'IPX425-400',
            '0x41f' => 'IPX425-266',
            '0x682' => 'PXA32x',
            '0x683' => 'PXA930/PXA935',
            '0x688' => 'PXA30x',
            '0x689' => 'PXA31x',
            '0xb11' => 'SA1110',
            '0xc12' => 'IPX1200',
        ), 'Intel'),
        '0x6d' => array(array(
            '0xd49' => 'Azure-Cobalt-100',
        ), 'Microsoft'),
        '0x70' => array(array(
            '0x303' => 'FTC310',
            '0x660' => 'FTC660',
            '0x661' => 'FTC661',
            '0x662' => 'FTC662',
            '0x663' => 'FTC663',
            '0x664' => 'FTC664',
            '0x862' => 'FTC862',
        ), 'Phytium'),
        '0xc0' => array(array(
            '0xac3' => 'Ampere-1',
            '0xac4' => 'Ampere-1a',
        ), 'Ampere'),
    );
    public static function getLoadAvg()
    {
        if (UtilsApi::isWin()) {
            return array(0, 0, 0);
        }
        return array_map(function ($load) {
            return (float) sprintf('%.2f', $load);
        }, sys_getloadavg());
    }
    public static function isArm($content)
    {
        return false !== stripos($content, 'CPU architecture');
    }
    public static function match($content, $search)
    {
        preg_match_all("/{$search}\\s*:\\s*(.+)/i", $content, $matches);
        return 2 === \count($matches) ? $matches[1] : array();
    }
    public static function getArmCpu($content)
    {
        $searchImplementer = self::match($content, 'CPU implementer');
        $implementer = \count($searchImplementer) ? $searchImplementer[0] : '';
        $implementer = isset(self::$HW_IMPLEMENTER[$implementer]) ? self::$HW_IMPLEMENTER[$implementer] : '';
        if ( ! $implementer) {
            return array();
        }
        $searchPart = self::match($content, 'CPU part');
        $part = \count($searchPart) ? $searchPart[0] : '';
        if ( ! $part) {
            return array($implementer);
        }
        $parts = $implementer[0];
        $partName = isset($parts[$part]) ? " {$parts[$part]}" : '';
        // features
        $searchFeatures = self::match($content, 'Features');
        $features = \count($searchFeatures) ? " ({$searchFeatures[0]})" : '';
        return array("{$implementer[1]}{$partName}{$features}");
    }
    public static function getModel()
    {
        $filePath = '/proc/cpuinfo';
        if ( ! is_readable($filePath)) {
            return '';
        }
        $content = file_get_contents($filePath);
        if ( ! $content) {
            return '';
        }
        if (self::isArm($content)) {
            $cores = substr_count($content, 'processor');
            if ( ! $cores) {
                return '';
            }
            return "{$cores} x " . implode(' / ', array_filter(self::getArmCpu($content)));
        }
        // cpu cores
        $cores = \count(self::match($content, 'cpu cores')) ?: substr_count($content, 'vendor_id');
        // cpu model name
        $searchModelName = self::match($content, 'model name');
        // cpu MHz
        $searchMHz = self::match($content, 'cpu MHz');
        // cache size
        $searchCache = self::match($content, 'cache size');
        if ( ! $cores) {
            return '';
        }
        return "{$cores} x " . implode(' / ', array_filter(array(
            \count($searchModelName) ? $searchModelName[0] : '',
            \count($searchMHz) ? "{$searchMHz[0]}MHz" : '',
            \count($searchCache) ? "{$searchCache[0]} cache" : '',
        )));
    }
    public static function getWinUsage()
    {
        $usage = array(
            'idle' => 100,
            'user' => 0,
            'sys' => 0,
            'nice' => 0,
        );
        // com
        if (class_exists('COM')) {
            // need help
            $wmi = new COM('Winmgmts://');
            $server = $wmi->execquery('SELECT LoadPercentage FROM Win32_Processor');
            $total = 0;
            foreach ($server as $cpu) {
                $total += (int) $cpu->loadpercentage;
            }
            $total = (float) $total / \count($server);
            $usage['idle'] = 100 - $total;
            $usage['user'] = $total;
        // exec
        } else {
            if ( ! \function_exists('exec')) {
                return $usage;
            }
            $p = array();
            exec('wmic cpu get LoadPercentage', $p);
            if (isset($p[1])) {
                $percent = (int) $p[1];
                $usage['idle'] = 100 - $percent;
                $usage['user'] = $percent;
            }
        }
        return $usage;
    }
    public static function getUsage()
    {
        static $cpu = null;
        if (null !== $cpu) {
            return $cpu;
        }
        if (UtilsApi::isWin()) {
            $cpu = self::getWinUsage();
            return $cpu;
        }
        $filePath = '/proc/stat';
        if ( ! @is_readable($filePath)) {
            $cpu = array();
            return array(
                'user' => 0,
                'nice' => 0,
                'sys' => 0,
                'idle' => 100,
            );
        }
        $stat1 = file($filePath);
        sleep(1);
        $stat2 = file($filePath);
        $info1 = explode(' ', preg_replace('!cpu +!', '', $stat1[0]));
        $info2 = explode(' ', preg_replace('!cpu +!', '', $stat2[0]));
        $dif = array();
        $dif['user'] = $info2[0] - $info1[0];
        $dif['nice'] = $info2[1] - $info1[1];
        $dif['sys'] = $info2[2] - $info1[2];
        $dif['idle'] = $info2[3] - $info1[3];
        $total = array_sum($dif);
        $cpu = array();
        foreach ($dif as $x => $y) {
            $cpu[$x] = round($y / $total * 100, 1);
        }
        return $cpu;
    }
}
namespace InnStudio\Prober\Components\ServerBenchmark;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class Conf extends ServerBenchmarkConstants
{
    public function __construct()
    {
        EventsApi::on('conf', function (array $conf) {
            $conf[$this->ID] = array(
                'disabledMyServerBenchmark' => XconfigApi::isDisabled('myServerBenchmark'),
            );
            return $conf;
        });
    }
}
namespace InnStudio\Prober\Components\ServerBenchmark;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Rest\RestResponse;
use InnStudio\Prober\Components\Rest\StatusCode;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
final class Init extends ServerBenchmarkApi
{
    public function __construct()
    {
        EventsApi::on('init', function ($action) {
            if (XconfigApi::isDisabled('myServerBenchmark')) {
                return $action;
            }
            if ('benchmark' !== $action) {
                return $action;
            }
            $this->render();
        });
    }
    private function render()
    {
        $remainingSeconds = $this->getRemainingSeconds();
        $response = new RestResponse();
        if ($remainingSeconds) {
            $response->setStatus(StatusCode::$TOO_MANY_REQUESTS);
            $response->setData(array(
                'seconds' => $remainingSeconds,
            ))->json()->end();
        }
        set_time_limit(0);
        $this->setExpired();
        $this->setIsRunning(true);
        // start benchmark
        $marks = $this->getPoints();
        // end benchmark
        $this->setIsRunning(false);
        $response->setData(array(
            'marks' => $marks,
        ))->json()->end();
    }
}
namespace InnStudio\Prober\Components\ServerBenchmark;
final class ServerBenchmark
{
    public function __construct()
    {
        new Init();
        new Conf();
        new FetchBefore();
    }
}
namespace InnStudio\Prober\Components\ServerBenchmark;
use InnStudio\Prober\Components\Events\EventsApi;
final class FetchBefore extends ServerBenchmarkApi
{
    public function __construct()
    {
        EventsApi::on('fetchBefore', array($this, 'filter'));
        EventsApi::on('fetchNodesBefore', array($this, 'filter'));
        EventsApi::on('fetchNodeBefore', array($this, 'filter'));
    }
    public function filter()
    {
        while ($this->isRunning()) {
            sleep(2);
        }
    }
}
namespace InnStudio\Prober\Components\ServerBenchmark;
use InnStudio\Prober\Components\Xconfig\XconfigApi;
class ServerBenchmarkApi
{
    public function getTmpRecorderPath()
    {
        return sys_get_temp_dir() . \DIRECTORY_SEPARATOR . 'xproberBenchmarkCool';
    }
    public function setRecorder(array $data)
    {
        return (bool) file_put_contents($this->getTmpRecorderPath(), json_encode(array_merge($this->getRecorder(), $data)));
    }
    public function setExpired()
    {
        return (bool) $this->setRecorder(array(
            'expired' => (int) $_SERVER['REQUEST_TIME'] + $this->cooldown(),
        ));
    }
    public function setIsRunning($isRunning)
    {
        return (bool) $this->setRecorder(array(
            'isRunning' => true === (bool) $isRunning ? 1 : 0,
        ));
    }
    public function isRunning()
    {
        $recorder = $this->getRecorder();
        return isset($recorder['isRunning']) ? 1 === (int) $recorder['isRunning'] : false;
    }
    public function getRemainingSeconds()
    {
        $recorder = $this->getRecorder();
        $expired = isset($recorder['expired']) ? (int) $recorder['expired'] : 0;
        if ( ! $expired) {
            return 0;
        }
        return $expired > (int) $_SERVER['REQUEST_TIME'] ? $expired - (int) $_SERVER['REQUEST_TIME'] : 0;
    }
    public function getPointsByTime($time)
    {
        return pow(10, 3) - (int) ($time * pow(10, 3));
    }
    public function getCpuPoints()
    {
        $data = 'inn-studio.com';
        $hash = array('md5', 'sha512', 'sha256', 'crc32');
        $start = microtime(true);
        $i = 0;
        while (microtime(true) - $start < .5) {
            foreach ($hash as $v) {
                hash($v, $data);
            }
            ++$i;
        }
        return $i;
    }
    public function getWritePoints()
    {
        $tmpDir = sys_get_temp_dir();
        if ( ! is_writable($tmpDir)) {
            return 0;
        }
        $i = 0;
        $start = microtime(true);
        while (microtime(true) - $start < .5) {
            $filePath = "{$tmpDir}/innStudioWriteBenchmark:{$i}";
            clearstatcache(true, $filePath);
            file_put_contents($filePath, $filePath);
            unlink($filePath);
            ++$i;
        }
        return $i;
    }
    public function getReadPoints()
    {
        $tmpDir = sys_get_temp_dir();
        if ( ! is_readable($tmpDir)) {
            return 0;
        }
        $i = 0;
        $start = microtime(true);
        $filePath = "{$tmpDir}/innStudioIoBenchmark";
        if ( ! file_exists($filePath)) {
            file_put_contents($filePath, 'innStudioReadBenchmark');
        }
        while (microtime(true) - $start < .5) {
            clearstatcache(true, $filePath);
            file_get_contents($filePath);
            ++$i;
        }
        return $i;
    }
    public function getPoints()
    {
        return array(
            'cpu' => $this->getMedian(array(
                $this->getCpuPoints(),
                $this->getCpuPoints(),
                $this->getCpuPoints(),
            )),
            'write' => $this->getMedian(array(
                $this->getWritePoints(),
                $this->getWritePoints(),
                $this->getWritePoints(),
            )),
            'read' => $this->getMedian(array(
                $this->getReadPoints(),
                $this->getReadPoints(),
                $this->getReadPoints(),
            )),
        );
    }
    private function cooldown()
    {
        return (int) XconfigApi::get('serverBenchmarkCd') ?: 60;
    }
    private function getRecorder()
    {
        $path = $this->getTmpRecorderPath();
        $defaults = array(
            'expired' => 0,
            'running' => 0,
        );
        if ( ! @is_readable($path)) {
            return $defaults;
        }
        $data = (string) file_get_contents($path);
        if ( ! $data) {
            return $defaults;
        }
        $data = json_decode($data, true);
        if ( ! $data) {
            return $defaults;
        }
        return array_merge($defaults, $data);
    }
    private function getMedian(array $arr)
    {
        $count = \count($arr);
        sort($arr);
        $mid = floor(($count - 1) / 2);
        return ($arr[$mid] + $arr[$mid + 1 - $count % 2]) / 2;
    }
}
namespace InnStudio\Prober\Components\ServerBenchmark;
class ServerBenchmarkConstants
{
    protected $ID = 'serverBenchmark';
}
namespace InnStudio\Prober\Components\Rest;
final class RestResponse
{
    private $data;
    private $headers = array();
    private $status = 200;
    public function __construct(array $data = null, $status = 200, array $headers = array())
    {
        $this->setData($data);
        $this->setStatus($status);
        $this->setHeaders($headers);
    }
    public function setHeader($key, $value, $replace = true)
    {
        if ($replace || ! isset($this->headers[$key])) {
            $this->headers[$key] = $value;
        } else {
            $this->headers[$key] .= ", {$value}";
        }
    }
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }
    public function getHeaders()
    {
        return $this->headers;
    }
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
    public function getStatus()
    {
        return $this->status;
    }
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }
    public function getData()
    {
        return $this->data;
    }
    public function json()
    {
        $this->httpResponseCode($this->status);
        header('Content-Type: application/json');
        header('Expires: 0');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        echo $this->toJson();
        return $this;
    }
    public function end()
    {
        exit;
    }
    private function toJson()
    {
        $data = $this->getData();
        if (null === $data) {
            return '';
        }
        return json_encode($data);
    }
    private function httpResponseCode($code)
    {
        if (\function_exists('http_response_code')) {
            return http_response_code($code);
        }
        $statusCode = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Unused)',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            418 => "I'm a teapot",
            419 => 'Authentication Timeout',
            420 => 'Enhance Your Calm',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            424 => 'Method Failure',
            425 => 'Unordered Collection',
            426 => 'Upgrade Required',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            431 => 'Request Header Fields Too Large',
            444 => 'No Response',
            449 => 'Retry With',
            450 => 'Blocked by Windows Parental Controls',
            451 => 'Unavailable For Legal Reasons',
            494 => 'Request Header Too Large',
            495 => 'Cert Error',
            496 => 'No Cert',
            497 => 'HTTP to HTTPS',
            499 => 'Client Closed Request',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage',
            508 => 'Loop Detected',
            509 => 'Bandwidth Limit Exceeded',
            510 => 'Not Extended',
            511 => 'Network Authentication Required',
            598 => 'Network read timeout error',
            599 => 'Network connect timeout error',
        );
        $msg = isset($statusCode[$code]) ? $statusCode[$code] : 'Unknow error';
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
        header("{$protocol} {$code} {$msg}");
    }
}
namespace InnStudio\Prober\Components\Rest;
final class StatusCode
{
    public static $__default = 200;
    public static $CONTINUE = 100;
    public static $SWITCHING_PROTOCOLS = 101;
    public static $PROCESSING = 102; // WEBDAV;_RFC_2518
    public static $OK = 200;
    public static $CREATED = 201;
    public static $ACCEPTED = 202;
    public static $NON_AUTHORITATIVE_INFORMATION = 203; // SINCE_HTTP/1.1
    public static $NO_CONTENT = 204;
    public static $RESET_CONTENT = 205;
    public static $PARTIAL_CONTENT = 206;
    public static $MULTI_STATUS = 207; // WEBDAV;_RFC_4918
    public static $ALREADY_REPORTED = 208; // WEBDAV;_RFC_5842
    public static $IM_USED = 226; // RFC_3229
    public static $MULTIPLE_CHOICES = 300;
    public static $MOVED_PERMANENTLY = 301;
    public static $FOUND = 302;
    public static $SEE_OTHER = 303; // SINCE_HTTP/1.1
    public static $NOT_MODIFIED = 304;
    public static $USE_PROXY = 305; // SINCE_HTTP/1.1
    public static $SWITCH_PROXY = 306;
    public static $TEMPORARY_REDIRECT = 307; // SINCE_HTTP/1.1
    public static $PERMANENT_REDIRECT = 308; // APPROVED_AS_EXPERIMENTAL_RFC
    public static $BAD_REQUEST = 400;
    public static $UNAUTHORIZED = 401;
    public static $PAYMENT_REQUIRED = 402;
    public static $FORBIDDEN = 403;
    public static $NOT_FOUND = 404;
    public static $METHOD_NOT_ALLOWED = 405;
    public static $NOT_ACCEPTABLE = 406;
    public static $PROXY_AUTHENTICATION_REQUIRED = 407;
    public static $REQUEST_TIMEOUT = 408;
    public static $CONFLICT = 409;
    public static $GONE = 410;
    public static $LENGTH_REQUIRED = 411;
    public static $PRECONDITION_FAILED = 412;
    public static $REQUEST_ENTITY_TOO_LARGE = 413;
    public static $REQUEST_URI_TOO_LONG = 414;
    public static $UNSUPPORTED_MEDIA_TYPE = 415;
    public static $REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    public static $EXPECTATION_FAILED = 417;
    public static $I_AM_A_TEAPOT = 418;
    public static $AUTHENTICATION_TIMEOUT = 419; // NOT_IN_RFC_2616
    public static $ENHANCE_YOUR_CALM = 420; // TWITTER
    public static $METHOD_FAILURE = 420; // SPRING_FRAMEWORK
    public static $UNPROCESSABLE_ENTITY = 422; // WEBDAV;_RFC_4918
    public static $LOCKED = 423; // WEBDAV;_RFC_4918
    public static $FAILED_DEPENDENCY = 424; // WEBDAV
    public static $UNORDERED_COLLECTION = 425; // INTERNET_DRAFT
    public static $UPGRADE_REQUIRED = 426; // RFC_2817
    public static $PRECONDITION_REQUIRED = 428; // RFC_6585
    public static $TOO_MANY_REQUESTS = 429; // RFC_6585
    public static $REQUEST_HEADER_FIELDS_TOO_LARGE = 431; // RFC_6585
    public static $NO_RESPONSE = 444; // NGINX
    public static $RETRY_WITH = 449; // MICROSOFT
    public static $BLOCKED_BY_WINDOWS_PARENTAL_CONTROLS = 450; // MICROSOFT
    public static $REDIRECT = 451; // MICROSOFT
    public static $UNAVAILABLE_FOR_LEGAL_REASONS = 451; // INTERNET_DRAFT
    public static $REQUEST_HEADER_TOO_LARGE = 494; // NGINX
    public static $CERT_ERROR = 495; // NGINX
    public static $NO_CERT = 496; // NGINX
    public static $HTTP_TO_HTTPS = 497; // NGINX
    public static $CLIENT_CLOSED_REQUEST = 499; // NGINX
    public static $INTERNAL_SERVER_ERROR = 500;
    public static $NOT_IMPLEMENTED = 501;
    public static $BAD_GATEWAY = 502;
    public static $SERVICE_UNAVAILABLE = 503;
    public static $GATEWAY_TIMEOUT = 504;
    public static $HTTP_VERSION_NOT_SUPPORTED = 505;
    public static $VARIANT_ALSO_NEGOTIATES = 506; // RFC_2295
    public static $INSUFFICIENT_STORAGE = 507; // WEBDAV;_RFC_4918
    public static $LOOP_DETECTED = 508; // WEBDAV;_RFC_5842
    public static $BANDWIDTH_LIMIT_EXCEEDED = 509; // APACHE_BW/LIMITED_EXTENSION
    public static $NOT_EXTENDED = 510; // RFC_2774
    public static $NETWORK_AUTHENTICATION_REQUIRED = 511; // RFC_6585
    public static $NETWORK_READ_TIMEOUT_ERROR = 598; // UNKNOWN
    public static $NETWORK_CONNECT_TIMEOUT_ERROR = 599; // Unknown
}new \InnStudio\Prober\Components\Database\Database();
new \InnStudio\Prober\Components\DiskUsage\DiskUsage();
new \InnStudio\Prober\Components\Fetch\Fetch();
new \InnStudio\Prober\Components\Footer\Footer();
new \InnStudio\Prober\Components\MyInfo\MyInfo();
new \InnStudio\Prober\Components\NetworkStats\NetworkStats();
new \InnStudio\Prober\Components\Nodes\Nodes();
new \InnStudio\Prober\Components\PhpExtensions\PhpExtensions();
new \InnStudio\Prober\Components\PhpInfo\PhpInfo();
new \InnStudio\Prober\Components\PhpInfoDetail\PhpInfoDetail();
new \InnStudio\Prober\Components\Ping\Ping();
new \InnStudio\Prober\Components\Script\Script();
new \InnStudio\Prober\Components\ServerBenchmark\ServerBenchmark();
new \InnStudio\Prober\Components\ServerInfo\ServerInfo();
new \InnStudio\Prober\Components\ServerStatus\ServerStatus();
new \InnStudio\Prober\Components\Style\Style();
new \InnStudio\Prober\Components\TemperatureSensor\TemperatureSensor();
new \InnStudio\Prober\Components\Timezone\Timezone();
new \InnStudio\Prober\Components\Updater\Updater();
new \InnStudio\Prober\Components\Bootstrap\Bootstrap();