<?php

abstract class ArcanistSingleRunLinter extends ArcanistLinter {

    final public function lintPath($path) {
        // We implement this one to stop any subclasses from doing individual ops
        // This is a single run linter after all.
    }

    final public function willLintPaths(array $paths) {
        $working_copy = $this->getEngine()->getWorkingCopy();
        $root = $working_copy->getProjectRoot();
        chdir($root);

        $result = exec_manual($this->buildCommand($paths));
        $messages = $this->parseLinterOutput($paths,
            $result[0], $result[1], $result[2]);

        foreach ($messages as $message) {
            $this->addLintMessage($message);
        }
    }

    final public function didLintPaths(array $paths) {
    }

    abstract protected function getPathsArgumentForLinter($path);
    abstract protected function getDefaultBinary();
    abstract protected function getMandatoryFlags();
    abstract protected function getDefaultFlags();
    abstract protected function parseLinterOutput(
        $paths, $err, $stdout, $stderr);

    final protected function buildCommand($paths) {
        $binary = $this->getDefaultBinary();
        $args = implode(' ', array_merge(
            $this->getMandatoryFlags(), $this->getDefaultFlags()));
        $paths = $this->getPathsArgumentForLinter($paths);
        return "$binary $args $paths";
    }


}
