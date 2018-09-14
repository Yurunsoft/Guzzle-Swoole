<?php
namespace Yurun\Util\Swoole\Guzzle\Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
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
	 * @var \Yurun\Util\Swoole\Guzzle\Plugin\FileGenerator
	 */
	protected $generator;

	/**
	 * Apply plugin modifications to Composer
	 *
	 * @param Composer    $composer
	 * @param IOInterface $io
	 */
	public function activate(Composer $composer, IOInterface $io)
	{
		$this->composer = $composer;
		$this->generator = new FileGenerator($composer->getEventDispatcher(), $io);
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
		$this->generator->dumpLoadFile($this->composer, dirname(__DIR__) . '/load.php');
	}

	public static function dev(\Composer\Script\Event $event)
	{
		$plugin = new static;
		$plugin->activate($event->getComposer(), $event->getIO());
		$plugin->dumpFiles();
	}
}