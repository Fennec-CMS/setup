<?php
/**
 ************************************************************************
 * @copyright 2016 David Lima
 * @license Apache 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 ************************************************************************
 */
namespace Fennec\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Command line installer for Fennec CMS
 * 
 * @author David Lima
 * @version v0.3
 */
class SetupCommand extends Command
{
    /**
     * Fennec CMS version
     * 
     * @var string
     */
    const VERSION = '0.3';
    
    /**
     * Github base URL for release downloads
     * 
     * @var string
     */
    const RELEASE_URL = 'https://github.com/Fennec-CMS/fennec/archive/';
    
    /**
     * Target directory to install CMS
     * 
     * @var string
     */
    private $target;
    
    /**
     * @var InputInterface
     */
    private $input;
    
    /**
     * @var OutputInterface
     */
    private $output;
    
    protected function configure()
    {
        $this
        ->setName('cms:setup')
        ->setDescription('Download and setup Fennec CMS')
        ->addOption(
            'target',
            '-t',
            InputOption::VALUE_REQUIRED
        );
    }

    /**
     * Run all methods needed to setup a basic Fennec CMS application
     * 
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<fg=green>This program will help you to install Fennec CMS v." . self::VERSION . "</>");
        $fs = new Filesystem();
        
        $this->target = $input->getOption('target');
        $this->input = $input;
        $this->output = $output;
        
        if (! $fs->exists($this->target)) {
            $fs->mkdir($this->target);
        }
        
        $this->runAction('downloadZipFile', 'Downloading release file...');
        $this->runAction('extractZipFile', "Extracting Fennec CMS to {$this->target}");        
    }
    
    /**
     * Get release file from GitHub repository
     * 
     * @throws \Exception
     */
    private function downloadZipFile()
    {
        if (! is_writable('/tmp/')) {
            throw new \Exception("Unable to download release: /tmp/ dir is not writable");
        }
        
        $ch = curl_init(self::RELEASE_URL . 'v' . self::VERSION . '.zip');
        curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        $zipData = curl_exec($ch);
        $curlInfo = curl_getinfo($ch);
        if ($curlInfo['http_code'] != 200) {
            throw new \Exception("Unable to download release: server returned code " . $curlInfo['http_code']);
        }
        
        $zipFile = fopen('/tmp/' . self::VERSION, 'w+');
        fwrite($zipFile, $zipData);
        fclose($zipFile);
    }
    
    /**
     * Extract downloaded release file to $this->terget
     * 
     * @throws \Exception
     */
    private function extractZipFile()
    {
        if (! file_exists('/tmp/' . self::VERSION)) {
            throw new \Exception("Release file not found");
        }
        
        $file = new \ZipArchive();
        $file->open('/tmp/' . self::VERSION);
        
        if (! $file->extractTo($this->target)) {
            throw new \Exception("Cannot extract Fennec CMS to {$this->target}");
        }
    }
    
    /**
     * Basic action wrapper.
     * This method tries to execute another method and output success messages
     * 
     * @param callable $method Method to call
     * @param string $message Init message
     */
    private function runAction($method, $message)
    {
        $this->output->write($message . ' ');
        try {
            $this->{$method}();
            $this->output->write("<fg=green>Success</>" . PHP_EOL);
        } catch (\Exception $e) {
            $this->output->write("<fg=red>Error</>" . PHP_EOL);
            $this->output->writeln("<error>{$e->getMessage()}</error>");
            exit();
        }
    }
}
