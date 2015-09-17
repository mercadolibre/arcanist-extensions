<?php

abstract class AbstractXUnitTestEngine extends ArcanistUnitTestEngine {

    protected function buildCommand($paths) {
        $binary = $this->getDefaultBinary();
        $args = implode(' ', array_merge(
            $this->getMandatoryFlags(), $this->getDefaultFlags()));
        $paths = implode(' ', $paths);
        return "$binary $args $paths";
    }

    final protected function extractErrors($files) {
        $errors = array();

        foreach ($files as $report) {
            $results = $this->parseReport($report);
            foreach ($results as $error) {
                $errors[] = $error;
            }
        }

        return $errors;

    }

    final protected function parseReport($report) {
        $parser = new ArcanistXUnitTestResultParser();
        $results = $parser->parseTestResults(
            Filesystem::readFile($report));
        return $results;
    }

    final public function run() {
        $working_copy = $this->getWorkingCopy();
        $root = $working_copy->getProjectRoot();
        chdir($root);

        // exec test runner
        $result = exec_manual($this->buildCommand(array()));
        return $this->processCommandResult($result);
    }

    protected function processCommandResult($result) {
        // find files and parse results
        $files = $this->findTestXmlFiles();

        return $this->extractErrors($files);
    }

    abstract public function getDefaultBinary();

    abstract public function getMandatoryFlags();

    abstract public function getDefaultFlags();

    abstract protected function findTestXmlFiles();
}
