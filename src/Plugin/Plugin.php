<?php
namespace Yurun\Util\Swoole\Guzzle\Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Yurun\Util\Swoole\Guzzle\Plugin\FileGenerator;
use Composer\EventDispatcher\EventSubscriberInterface;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var \Composer\Composer
     */
    protected $composer;

    /**
     * @var \Composer\IO\IOInterface
     */
    protected $io;

    /**
     * 是否为开发模式
     *
     * @var boolean
     */
    protected $dev = false;

    public function __construct($dev = false)
    {
        $this->dev = $dev;
    }

    /**
     * Apply plugin modifications to Composer
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'post-autoload-dump' => 'dumpFiles',
        );
    }

    public function dumpFiles()
    {
        $this->parseGuzzle();

        if(!$this->dev)
        {
            $this->appendIncludeFiles();
        }
    }

    public static function dev(\Composer\Script\Event $event)
    {
        $plugin = new static(true);
        $plugin->activate($event->getComposer(), $event->getIO());
        $plugin->dumpFiles();
    }

    /**
     * 处理Guzzle代码
     *
     * @return void
     */
    protected function parseGuzzle()
    {
        $loadFilePath = dirname(__DIR__) . '/load.php';
        $config = $this->composer->getConfig();

        $filesystem = new Filesystem();
        $filesystem->ensureDirectoryExists($config->get('vendor-dir'));
        $vendorPath = $filesystem->normalizePath(realpath(realpath($config->get('vendor-dir'))));
        $autoloadFilesFile = $vendorPath.'/composer/autoload_files.php';

        $lockData = $this->composer->getLocker()->getLockData();
        if(!isset($lockData['packages']))
        {
            throw new \RuntimeException('Cannot found packages');
        }
        foreach($lockData['packages'] as $item)
        {
            if('guzzlehttp/guzzle' === $item['name'])
            {
                $guzzleVersion = $item['version'];
                break;
            }
        }
        if(!isset($guzzleVersion))
        {
            throw new \RuntimeException('Not found guzzlehttp/guzzle');
        }

        $files = include $autoloadFilesFile;

        foreach($files as $fileName)
        {
            if(preg_match('/^(.+guzzlehttp\/guzzle)\//', $fileName, $matches) > 0)
            {
                $guzzlePath = $matches[1];
                break;
            }
        }
        if(!isset($guzzlePath))
        {
            throw new \RuntimeException('Not found guzzlehttp/guzzle path');
        }

        [$guzzleBigVersion] = explode('.', $guzzleVersion);

        switch($guzzleBigVersion)
        {
            case '6':
                $path = $guzzlePath . '/src/functions.php';
                if(!function_exists('GuzzleHttp\choose_handler'))
                {
                    include $path;
                }
                $refFunction = new \ReflectionFunction('GuzzleHttp\choose_handler');
                $content = file_get_contents($path);
                $eol = $this->getEOL($content);
                $contents = explode($eol, $content);
                for($i = $refFunction->getStartLine() - 1; $i < $refFunction->getEndLine(); ++$i)
                {
                    unset($contents[$i]);
                }
                $content = implode($eol, $contents);
                file_put_contents($loadFilePath, $content);
                break;
            case '7':
                $path = $guzzlePath . '/src/Utils.php';
                if(!method_exists('GuzzleHttp\Utils', 'chooseHandler'))
                {
                    include $path;
                }
                $refMethod = new \ReflectionMethod('GuzzleHttp\Utils', 'chooseHandler');
                $content = file_get_contents($path);
                $eol = $this->getEOL($content);
                $contents = explode($eol, $content);
                array_splice($contents, $refMethod->getStartLine() - 1, $refMethod->getEndLine() - $refMethod->getStartLine() + 1, <<<CODE
    public static function chooseHandler(): callable
    {
        return \Yurun\Util\Swoole\Guzzle\choose_handler();
    }
CODE
                );
                $content = implode($eol, $contents);
                file_put_contents($loadFilePath, $content);
                break;
            default:
                throw new \RuntimeException(sprintf('Unsupport guzzle version %s', $guzzleVersion));
        }
    }

    /**
     * 追加 include 文件
     *
     * @return void
     */
    protected function appendIncludeFiles()
    {
        $generator = new \ComposerIncludeFiles\Composer\AutoloadGenerator($this->composer->getEventDispatcher(), $this->io);

        $config = $this->composer->getConfig();
        $filesystem = new Filesystem();
        $filesystem->ensureDirectoryExists($config->get('vendor-dir'));
        $vendorPath = $filesystem->normalizePath(realpath(realpath($config->get('vendor-dir'))));

        $generator->dumpFiles($this->composer, [
            $vendorPath . "/yurunsoft/guzzle-swoole/src/load_include.php",
            $vendorPath . "/yurunsoft/guzzle-swoole/src/functions.php"
        ]);
    }
    
    /**
     * 字符串是否以另一个字符串结尾
     * @param string $string
     * @param string $compare
     * @return string
     */
    protected function stringEndwith($string, $compare)
    {
        return substr($string, -strlen($compare)) === $compare;
    }

    /**
     * 获取换行符
     *
     * @param string $content
     * @return string
     */
    protected function getEOL($content)
    {
        static $eols = [
            "\r\n",
            "\n",
            "\r",
        ];
        foreach($eols as $eol)
        {
            if(strpos($content, $eol))
            {
                return $eol;
            }
        }
        return PHP_EOL;
    }
}