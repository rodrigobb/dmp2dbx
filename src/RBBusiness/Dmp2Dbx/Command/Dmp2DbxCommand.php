<?php

namespace RBBusiness\Dmp2Dbx\Command;

use Dropbox\AppInfo;
use Dropbox\Client as DbxClient;
use Dropbox\WebAuthNoRedirect;
use Dropbox\WriteMode;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Webmozart\Json\FileNotFoundException;
use Webmozart\Json\JsonDecoder;
use Webmozart\Json\JsonEncoder;

class Dmp2DbxCommand extends Command
{
    /** @var  InputInterface */
    protected $in;

    /** @var  OutputInterface */
    protected $out;

    protected $configFilePath;
    protected $config;

    /** @var  AppInfo */
    protected $appInfo;

    protected function configure()
    {
        $this
            ->setName('dmp2dbx:upload')
            ->setDescription('Uploads DB dump file to Dropbox')
            ->addArgument(
                'source',
                InputArgument::REQUIRED,
                'file to upload'
                // TODO: 'file or folder to upload'
            )
            ->addOption(
                'configFile',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Configuration file path',
                $this->getDefaultConfigFilepath()
            )
            ->addOption(
                'accessToken',
                't',
                InputOption::VALUE_OPTIONAL,
                'Dropbox app access token',
                null
            )
            ->addOption(
                'no-update',
                null,
                InputOption::VALUE_NONE,
                'If set, configuration values won\'t be updated to config file'
            )
        ;
    }

    /**
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->in = $input;
        $this->out = $output;

        if ($this->in->hasOption('configFile') && file_exists($this->in->getOption('configFile'))) {
            $this->configFilePath = $this->in->getOption('configFile');
        } else {
            $this->configFilePath = $this->getDefaultConfigFilepath();
        }

        $this->config = $this->loadCondiguration($this->configFilePath);
    }

    /**
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->getAuthorizationCode();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = $input->getArgument('source');
        if (!file_exists($source)) {
            throw new FileNotFoundException();
        }

        $this->uploadSource($source);

        $this->updateConfiguration();
    }

    protected function getDefaultConfigFilepath()
    {
        return __DIR__.'/../Resources/config/config.json';
    }

    protected function loadCondiguration($configFilePath)
    {
        $decoder = new JsonDecoder();
        $this->config = $decoder->decodeFile($configFilePath);

        if (property_exists($this->config, 'appInfo') && $this->config->appInfo) {
            $this->appInfo = AppInfo::loadFromJson(get_object_vars($this->config->appInfo));
        } else {
            $this->appInfo = AppInfo::loadFromJsonFile(__DIR__.'/../Resources/config/app-def.json');
        }

        if ($this->in->getOption('accessToken')) {
            $this->config->accessToken = $this->in->getOption('accessToken');
        }

        return $this->config;
    }

    protected function getAuthorizationCode() {
        if (null == $this->config->accessToken) {
            $webAuth = new WebAuthNoRedirect($this->appInfo, $this->getApplication()->getName());
            $authorizeUrl = $webAuth->start();

            $this->out->writeln("1. Go to: " . $authorizeUrl);
            $this->out->writeln("2. Click \"Allow\" (you might have to log in first).");
            $this->out->writeln("3. Copy the authorization code.");

            $helper = $this->getHelper('question');
            $question = new Question('Enter the authorization code: ', null);

            $authCode = $helper->ask($this->in, $this->out, $question);

            list($this->config->accessToken, $dropboxUserId) = $webAuth->finish($authCode);

            unset($dropboxUserId); //Avoid unused variable
        }

        return $this->config->accessToken;
    }

    protected function uploadSource($sourcePath)
    {
        $dbxClient = new DbxClient($this->config->accessToken, $this->getApplication()->getName());

//        $accountInfo = $dbxClient->getAccountInfo();
//        $this->out->writeln(print_r($accountInfo, true));

        //TODO: Upload folders
        if (!is_file($sourcePath)) {
            throw new \UnexpectedValueException("'$sourcePath' must be an existing file");
        }

        $f = fopen($sourcePath, "rb");
        //TODO: Support big files using $dbxClient->chunkedUploadStart();
        $result = $dbxClient->uploadFile("/".basename($sourcePath), WriteMode::force(), $f);
        fclose($f);

        if ($this->out->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $this->out->writeln(print_r($result, true));
        }

        $this->out->writeln("Uploaded ".$result['path']." (".$result['size']." - last time modified: ".$result['client_mtime'].")");
    }

    protected function updateConfiguration()
    {
        if ($this->in->getOption('no-update')) {
            $this->out->writeln("Exiting without updating configuration");
            return;
        }

        $encoder = new JsonEncoder();
        $encoder->encodeFile($this->config, $this->configFilePath);
    }
}
