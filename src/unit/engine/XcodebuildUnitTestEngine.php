<?php

final class XcodebuildUnitTestEngine extends AbstractXUnitTestEngine {

    public function getInfoDescription() {
        return 'This uses xcodebuild, xcpretty and oclint.'.PHP_EOL
            .'It assumes you use cocoapods for project organization. You can configure the following keys:'.PHP_EOL
            .XcodebuildConfiguration::getOptionsDescription();
    }

    protected function supportsRunAllTests() {
        return true;
    }

    protected function findTestXmlFiles() {
        $base = getcwd();
        return array($base.'/build/reports/junit.xml');
    }

    private function getXCPrettyPath() {
        $config = $this->getConfigurationManager();
        $xcpretty_bin = $config->getConfigFromAnySource('bin.xcpretty', 'xcpretty');

        list($err, $stdout) = exec_manual('which %s', $xcpretty_bin);
        if ($err) {
            throw new ArcanistUsageException("can't find xcpretty");
        }

        return trim($stdout);
    }

    protected function buildCommand($paths) {
        $configuration_manager = $this->getConfigurationManager();

        $destinations = $configuration_manager->getProjectConfig('xctest.destination');
        if (!$destinations) {
            throw new ArcanistUsageException('You must set \'xctest.destination\'');
        }

        if (is_string($destinations)) {
            $destinations = array($destinations);
        }

        $configuration = new XcodebuildConfiguration($configuration_manager);

        $build_flags = array('test');

        foreach ($destinations as $destination) {
            $build_flags[] = '-destination';
            $build_flags[] = '"'.$destination.'"';
        }

        return $configuration->buildCommand($build_flags)
            .'| '.$this->getXCPrettyPath().' '
            .'--report junit '
            .'--output build/reports/junit.xml ';
    }

    public function getDefaultBinary() {}

    public function getMandatoryFlags() {}

    public function getDefaultFlags() {}
}
