<?php

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package redaxo\install
 *
 * @internal
 */
class rex_command_install_update extends rex_console_command
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Updates an AddOn from redaxo.org')
            ->addArgument('addonkey', InputArgument::REQUIRED, 'AddOn key, e.g. "yform"')
            ->addArgument('version', InputArgument::OPTIONAL, 'Version, e.g. "3.2.1"');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = $this->getStyle($input, $output);

        $addonKey = $input->getArgument('addonkey');

        if (!rex_addon::exists($addonKey)) {
            $io->error(sprintf('AddOn "%s" does not exist!', $addonKey));
            return 1;
        }

        $packages = rex_install_packages::getUpdatePackages();

        if (!isset($packages[$addonKey])) {
            $io->error(sprintf('No Updates available for AddOn "%s"!', $addonKey));
            return 1;
        }
        $package = $packages[$addonKey];
        $files = $package['files'];

        $version = $input->getArgument('version');

        if (null === $version) {
            $versions = [];
            foreach ($files as $fileMeta) {
                $versions[] = $fileMeta['version'];
            }

            $version = (string) $io->choice('Please choose a version', $versions);
        }

        // search fileId by version
        $fileId = null;
        foreach ($files as $fId => $fileMeta) {
            if ($fileMeta['version'] !== $version) {
                continue;
            }
            $fileId = $fId;
            break;
        }

        if (!$fileId || !isset($files[$fileId])) {
            $io->error(sprintf('Version "%s" does not exist or is below the current version!', $version));
            return 1;
        }

        $install = new rex_install_package_update();
        try {
            $message = $install->run($addonKey, $fileId);
        } catch (rex_exception $exception) {
            $io->error($this->decodeMessage($exception->getMessage()));
            return 1;
        }

        if ('' !== $message) {
            $io->error($this->decodeMessage($message));
            return 1;
        }

        $io->success(sprintf('AddOn "%s" successfully updated to version "%s".', $addonKey, $version));
        return 0;
    }
}
