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

    protected function getPathArguments($paths) {
        $args = array();
        foreach($paths as $path) {
            $args[] = $this->getPathArgumentForLinter($path);
        }
        return implode(' ', $args);
    }

    abstract protected function getPathArgumentForLinter($path);
    abstract protected function getDefaultBinary();
    abstract protected function getMandatoryFlags();
    abstract protected function getDefaultFlags();
    abstract protected function parseLinterOutput($paths, $err, $stdout, $stderr);

    final protected function buildCommand($paths) {
        $binary = $this->getDefaultBinary();
        $args = implode(' ', $this->getMandatoryFlags());
        $args = $args . implode(' ', $this->getDefaultFlags());
        $paths = $this->getPathArguments($paths);
        return "$binary $args $paths";
    }


}
