<?php

abstract class ArcanistSingleRunLinter extends ArcanistLinter {

    final public function lintPath($path) {
    }

    final public function willLintPaths(array $paths) {
        $cmd = $this->buildCommand($paths);

        $result = exec_manual($this->buildCommand($paths));
        $messages = $this->parseLinterOutput($paths, $result[0], $result[1], $result[2]);

        foreach ($messages as $message) {
            $this->addLintMessage($message);
        }
    }

    abstract protected function getPathsArgumentForLinter($path);
    abstract protected function getDefaultBinary();
    abstract protected function getMandatoryFlags();
    abstract protected function getDefaultFlags();
    abstract protected function parseLinterOutput($paths, $err, $stdout, $stderr);

    final protected function buildCommand($paths) {
        $binary = $this->getDefaultBinary();
        $args = implode(' ', $this->getMandatoryFlags());
        $args = $args . implode(' ', $this->getDefaultFlags());
        $paths = $this->getPathsArgumentForLinter($paths);
        return "$binary $args $paths";
    }


    final public function didRunLinters() {
        foreach ($this->paths as $path) {
            $this->willLintPath($path);
        }
    }

}
