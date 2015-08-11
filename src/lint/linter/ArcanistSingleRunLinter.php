<?php

abstract class ArcanistSingleRunLinter extends ArcanistLinter {

    private $linterDidRun = false;

    final public function lintPath($path) {
        // We implement this one to stop any subclasses from doing individual ops
        // This is a single run linter after all.
    }

    final public function willLintPaths(array $paths) {
        // The engine will attempt to run the linter on chunks of paths,
        // but we will ignore that and run for ALL paths, so we need to
        // make sure to run just once.
        if ($this->linterDidRun) {
            return;
        }
        $this->linterDidRun = true;

        // Ignore passed paths, use all paths to be linted by the engine
        $paths = $this->getPaths();

        $working_copy = $this->getEngine()->getWorkingCopy();
        $root = $working_copy->getProjectRoot();
        chdir($root);

        $this->prepareToLintPaths($paths);

        $result = exec_manual($this->buildCommand($paths));
        $messages = $this->parseLinterOutput($paths,
            $result[0], $result[1], $result[2]);

        foreach ($messages as $message) {
            $this->addLintMessage($message);
        }
    }

    final public function didLintPaths(array $paths) {}

    abstract protected function getPathsArgumentForLinter($path);
    abstract protected function getDefaultBinary();
    abstract protected function getMandatoryFlags();
    abstract protected function getDefaultFlags();
    abstract protected function parseLinterOutput(
        $paths, $err, $stdout, $stderr);

    protected function prepareToLintPaths(array $paths) {}

    final protected function buildCommand($paths) {
        $binary = $this->getDefaultBinary();
        $args = implode(' ', array_merge(
            $this->getMandatoryFlags(), $this->getDefaultFlags()));
        $paths = $this->getPathsArgumentForLinter($paths);
        return "$binary $args $paths";
    }


}
