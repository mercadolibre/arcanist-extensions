<?php

final class GradleCheckstyleLinter extends ArcanistCheckstyleLinter {

  private $_srcPaths;
  private $_aggregate = true;
  private $outputPath = '';


  public function getLinterName() {
    return 'checkstyle-gradle';
  }

  public function getLinterConfigurationName() {
    return 'checkstyle-gradle';
  }

  public function getLinterConfigurationOptions() {
    $options = parent::getLinterConfigurationOptions();

    // Remove maven-only flags
    unset($options['paths']);
    unset($options['aggregate']);

    return $options;
  }

  public function getMandatoryFlags() {
    return array(
      'build'
    );
  }

  public function getInstallInstructions() {
    return pht('Just have a gradle project with checkstyle configured');
  }

  public function getDefaultBinary() {
    $config = $this->getEngine()->getConfigurationManager();
    return $config->getConfigFromAnySource('bin.gradle', 'gradle');
  }

  protected function buildOutputPaths() {
     $base = getcwd();
     $directory = new RecursiveDirectoryIterator($base);
     $iterator = new RecursiveIteratorIterator($directory);
     $regex = new RegexIterator($iterator,
         '/^.+build\/reports\/checkstyle\/checkstyle.xml$/i',
         RecursiveRegexIterator::GET_MATCH);
     $matches = iterator_to_array($regex);
     $files = array();
     foreach ($matches as $match) {
       $files[] = $match[0];
     }
     if (!count($files)) {
       throw new ArcanistUsageException('Could not find any checkstyle'
           . ' output files. Check this project is correctly configured'
           . ' and actually a Java project.');
     }
     return $files;
  }

  protected function getPathsArgumentForLinter($paths) {
    return '';
  }
}
