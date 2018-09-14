<?php

namespace Yurun\Util\Swoole\Guzzle\Plugin;

use Composer\Autoload\AutoloadGenerator as ComposerAutoloadGenerator;
use Composer\Composer;
use Composer\Installer\InstallationManager;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;

class FileGenerator extends ComposerAutoloadGenerator
{
	public function dumpLoadFile(Composer $composer, $filePath)
	{
		$config = $composer->getConfig();

		$filesystem = new Filesystem();
		$filesystem->ensureDirectoryExists($config->get('vendor-dir'));
		$vendorPath = $filesystem->normalizePath(realpath(realpath($config->get('vendor-dir'))));
		$autoloadFilesFile = $vendorPath.'/composer/autoload_files.php';

		$files = include $autoloadFilesFile;
		
		foreach($files as $fileName)
		{
			if($this->stringEndwith($fileName, '/guzzlehttp/guzzle/src/functions_include.php'))
			{
				$path = dirname($fileName) . '/functions.php';
				include $path;
				$refFunction = new \ReflectionFunction('GuzzleHttp\choose_handler');
				$content = file_get_contents($path);
				$eol = $this->getEOL($content);
				$contents = explode($eol, $content);
				for($i = $refFunction->getStartLine() - 1; $i < $refFunction->getEndLine(); ++$i)
				{
					unset($contents[$i]);
				}
				$content = implode($eol, $contents);
				file_put_contents($filePath, $content);
				break;
			}
		}
	}

	/**
	 * 字符串是否以另一个字符串结尾
	 * @param string $string
	 * @param string $compare
	 * @return string
	 */
	public function stringEndwith($string, $compare)
	{
		return substr($string, -strlen($compare)) === $compare;
	}

	public function getEOL($content)
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
