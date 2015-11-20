<?php

abstract class ArcanistSingleRunLinter extends ArcanistLinter {

    private $linterDidRun = false;
    private $versionRequirement = null;

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

    /**
     * Return a human-readable string describing how to upgrade the linter.
     *
     * @return string Human readable upgrade instructions
     * @task bin
     */
    public function getUpgradeInstructions() {
        return null;
    }


    /**
     * Set the binary's version requirement.
     *
     * @param string Version requirement.
     * @return this
     * @task bin
     */
    final public function setVersionRequirement($version) {
        $this->versionRequirement = trim($version);
        return $this;
    }

    public function getLinterConfigurationOptions() {
        $options = array(
            'version' => array(
                'type' => 'optional string',
                'help' => pht(
                    'Specify a version requirement for the binary. The version number '.
                    'may be prefixed with <, <=, >, >=, or = to specify the version '.
                    'comparison operator (default: =).'),
                ),
        );

        return $options + parent::getLinterConfigurationOptions();
    }

    public function setLinterConfigurationValue($key, $value) {
        switch ($key) {
            case 'version':
                $this->setVersionRequirement($value);
                return;
        }

        return parent::setLinterConfigurationValue($key, $value);
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
        $this->checkBinaryVersion($this->getVersion());
        $binary = $this->getDefaultBinary();
        $args = implode(' ', array_merge(
            $this->getMandatoryFlags(), $this->getDefaultFlags()));
        $paths = $this->getPathsArgumentForLinter($paths);
        return "$binary $args $paths";
    }

    public function getVersion() {
        list($stdout) = execx('%C --version', $this->getDefaultBinary());
        $matches = array();
        $regex = '/(?P<version>\d+\.\d+(\.\d+)?)/';
        if (preg_match($regex, $stdout, $matches)) {
            return $matches['version'];
        } else {
            return false;
        }
    }

    /**
     * If a binary version requirement has been specified, compare the version
     * of the configured binary to the required version, and if the binary's
     * version is not supported, throw an exception.
     *
     * @param  string   Version string to check.
     * @return void
     */
    final protected function checkBinaryVersion($version) {
        if (!$this->versionRequirement) {
            return;
        }
        if (!$version) {
            $message = pht(
                'Linter %s requires %s version %s. Unable to determine the version '.
                'that you have installed.',
                get_class($this),
                $this->getDefaultBinary(),
                $this->versionRequirement);
            $instructions = $this->getUpgradeInstructions();
            if ($instructions) {
                $message .= "\n".pht('TO UPGRADE: %s', $instructions);
            }
            throw new ArcanistMissingLinterException($message);
        }
        $operator = '==';
        $compare_to = $this->versionRequirement;
        $matches = null;
        if (preg_match('/^([<>]=?|=)\s*(.*)$/', $compare_to, $matches)) {
            $operator = $matches[1];
            $compare_to = $matches[2];
            if ($operator === '=') {
                $operator = '==';
            }
        }
        if (!version_compare($version, $compare_to, $operator)) {
            $message = pht(
                'Linter %s requires %s version %s. You have version %s.',
                get_class($this),
                $this->getDefaultBinary(),
                $this->versionRequirement,
                $version);
            $instructions = $this->getUpgradeInstructions();
            if ($instructions) {
                $message .= "\n".pht('TO UPGRADE: %s', $instructions);
            }
            throw new ArcanistMissingLinterException($message);
        }
    }
}
