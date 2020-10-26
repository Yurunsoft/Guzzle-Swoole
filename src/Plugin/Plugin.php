<?php

namespace Yurun\Util\Swoole\Guzzle\Plugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Util\Filesystem;

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
     * 是否为开发模式.
     *
     * @var bool
     */
    protected $dev = false;

    public function __construct(bool $dev = false)
    {
        $this->dev = $dev;
    }

    /**
     * Apply plugin modifications to Composer.
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
     * Remove any hooks from Composer.
     *
     * This will be called when a plugin is deactivated before being
     * uninstalled, but also before it gets upgraded to a new version
     * so the old one can be deactivated and the new one activated.
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    /**
     * Prepare the plugin to be uninstalled.
     *
     * This will be called after deactivate.
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'post-autoload-dump' => 'dumpFiles',
        ];
    }

    public function dumpFiles(): void
    {
        $this->parseGuzzle();

        if (!$this->dev)
        {
            $this->appendIncludeFiles();
        }
    }

    public static function dev(\Composer\Script\Event $event): void
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
    protected function parseGuzzle(): void
    {
        $loadFilePath = \dirname(__DIR__) . '/load.php';
        $config = $this->composer->getConfig();

        $filesystem = new Filesystem();
        $filesystem->ensureDirectoryExists($config->get('vendor-dir'));
        $vendorPath = $filesystem->normalizePath(realpath(realpath($config->get('vendor-dir'))));
        $autoloadFilesFile = $vendorPath . '/composer/autoload_files.php';

        $lockData = $this->composer->getLocker()->getLockData();
        if (!isset($lockData['packages']))
        {
            throw new \RuntimeException('Cannot found packages');
        }
        foreach ($lockData['packages'] as $item)
        {
            if ('guzzlehttp/guzzle' === $item['name'])
            {
                $guzzleVersion = $item['version'];
                break;
            }
        }
        if (!isset($guzzleVersion))
        {
            throw new \RuntimeException('Not found guzzlehttp/guzzle');
        }

        $files = include $autoloadFilesFile;

        foreach ($files as $fileName)
        {
            if (preg_match('/^(.+guzzlehttp\/guzzle)\//', $fileName, $matches) > 0)
            {
                $guzzlePath = $matches[1];
                break;
            }
        }
        if (!isset($guzzlePath))
        {
            throw new \RuntimeException('Not found guzzlehttp/guzzle path');
        }

        [$guzzleBigVersion] = explode('.', $guzzleVersion);

        switch ($guzzleBigVersion)
        {
            case '6':
                $path = $guzzlePath . '/src/functions.php';
                if (!\function_exists('GuzzleHttp\choose_handler'))
                {
                    include $path;
                }
                $refFunction = new \ReflectionFunction('GuzzleHttp\choose_handler');
                $content = file_get_contents($path);
                $eol = $this->getEOL($content);
                $contents = explode($eol, $content);
                for ($i = $refFunction->getStartLine() - 1; $i < $refFunction->getEndLine(); ++$i)
                {
                    unset($contents[$i]);
                }
                $content = implode($eol, $contents);
                file_put_contents($loadFilePath, $content);
                break;
            case '7':
                $path = $guzzlePath . '/src/Utils.php';
                if (!method_exists('GuzzleHttp\Utils', 'chooseHandler'))
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
     * 追加 include 文件.
     *
     * @return void
     */
    protected function appendIncludeFiles(): void
    {
        $generator = new \ComposerIncludeFiles\Composer\AutoloadGenerator($this->composer->getEventDispatcher(), $this->io);

        $config = $this->composer->getConfig();
        $filesystem = new Filesystem();
        $filesystem->ensureDirectoryExists($config->get('vendor-dir'));
        $vendorPath = $filesystem->normalizePath(realpath(realpath($config->get('vendor-dir'))));

        $generator->dumpFiles($this->composer, [
            $vendorPath . '/yurunsoft/guzzle-swoole/src/load_include.php',
            $vendorPath . '/yurunsoft/guzzle-swoole/src/functions.php',
        ]);
    }

    /**
     * 字符串是否以另一个字符串结尾.
     *
     * @param string $string
     * @param string $compare
     *
     * @return bool
     */
    protected function stringEndwith(string $string, string $compare): bool
    {
        return substr($string, -\strlen($compare)) === $compare;
    }

    /**
     * 获取换行符.
     *
     * @param string $content
     *
     * @return string
     */
    protected function getEOL(string $content): string
    {
        static $eols = [
            "\r\n",
            "\n",
            "\r",
        ];
        foreach ($eols as $eol)
        {
            if (strpos($content, $eol))
            {
                return $eol;
            }
        }

        return \PHP_EOL;
    }
}
