<?php

final class XcodebuildConfiguration {

    private $workspace;

    private $scheme;

    private $configuration = 'Debug';

    private $sdk = 'iphonesimulator';

    private $xcodebuildBin = 'xcodebuild';

    private $configurationManager;

    public function __construct($configuration_manager) {
        $this->configurationManager = $configuration_manager;
    }

    public static function getOptionsDescription() {
        return ' - xcodebuild.workspace: The .xcworkspace to build on.'.PHP_EOL
            .' - xcodebuild.scheme: The workspace scheme to build.'.PHP_EOL
            .' - xcodebuild.configuration: The .xcworkspace to build on. Default: \'Debug\''.PHP_EOL
            .' - xcodebuild.sdk: SDK to build against to. Default: \'iphonesimulator\''.PHP_EOL
            .' - bin.xcodebuild: Path to xcodebuild binary. Default \'xcodebuild\'.'.PHP_EOL;
    }

    private function getXcodebuildPath() {
        $config = $this->configurationManager;
        $xcodebuild_bin = $config->getConfigFromAnySource('bin.xcodebuild', $this->xcodebuildBin);

        list($err, $stdout) = exec_manual('which %s', $xcodebuild_bin);
        if ($err) {
            throw new ArcanistUsageException("can't find xcodebuild");
        }

        return trim($stdout);
    }

    private function getConfigValue($key, $default) {
        $config = $this->configurationManager;
        $value = $config->getConfigFromAnySource($key, $default);

        if ($value) {
            return $value;
        } else {
            throw new ArcanistUsageException('A value for '.$key.' must be provided');
        }
    }

    private function loadConfig() {
        $this->workspace = $this->getConfigValue('xcodebuild.workspace', $this->workspace);
        $this->sdk = $this->getConfigValue('xcodebuild.sdk', $this->sdk);
        $this->configuration = $this->getConfigValue('xcodebuild.configuration', $this->configuration);
        $this->scheme = $this->getConfigValue('xcodebuild.scheme', $this->scheme);
        $this->xcodebuildBin = $this->getXcodebuildPath();
    }

    public function buildCommand(array $flags) {
        $this->loadConfig();

        $command = new PhutilCommandString(array('%s '
            .'-workspace %s '
            .'-scheme %s '
            .'-configuration %s '
            .'-sdk %s '
            .implode(' ', $flags),
            $this->xcodebuildBin,
            $this->workspace,
            $this->scheme,
            $this->configuration,
            $this->sdk,
        ));

        return $command->getMaskedString();
    }
}
