<?php

abstract class ArcanistCommandLinter extends ArcanistLinter {

    private $versionRequirement = null;
    private $versionVerified = false;

/* -(  Abstract requirements  )--------------------------------------------- */

    abstract protected function getDefaultBinary();
    abstract protected function getPathsArgumentForLinter($path);
    abstract protected function getMandatoryFlags();
    abstract protected function getDefaultFlags();

    /**
     * Return a human-readable string describing how to upgrade the linter.
     *
     * @return string Human readable upgrade instructions
     * @task bin
     */
    public function getUpgradeInstructions() {
        return null;
    }

/* -(  Helpers  )---------------------------------------------------------- */

    final protected function buildCommand($paths) {
        $binary = $this->getDefaultBinary();
        $args = implode(' ', array_merge(
            $this->getMandatoryFlags(), $this->getDefaultFlags()));
        $paths = $this->getPathsArgumentForLinter($paths);
        return "$binary $args $paths";
    }

/* -(  Version check  )----------------------------------------------------- */

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

    /**
     * If a binary version requirement has been specified, compare the version
     * of the configured binary to the required version, and if the binary's
     * version is not supported, throw an exception.
     *
     * @return void
     */
    final protected function assertBinaryVersion() {
        if (!$this->versionRequirement || $this->versionVerified) {
            return;
        }

        $this->versionVerified = true;

        $version = $this->getVersion();

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

/* -(  Overrides  )--------------------------------------------------------- */

    public function willLintPaths(array $paths) {
        parent::willLintPaths($paths);
        $this->assertBinaryVersion();
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
}
