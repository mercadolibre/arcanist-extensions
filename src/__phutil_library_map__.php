<?php

/**
 * This file is automatically generated. Use 'arc liberate' to rebuild it.
 *
 * @generated
 * @phutil-library-version 2
 */
phutil_register_library_map(array(
  '__library_version__' => 2,
  'class' => array(
    'AbstractFileParser' => 'lint/parser/AbstractFileParser.php',
    'AbstractMetaLinter' => 'lint/linter/AbstractMetaLinter.php',
    'AndroidGradleLintProvider' => 'lint/provider/gradle/AndroidGradleLintProvider.php',
    'AndroidParser' => 'lint/parser/AndroidParser.php',
    'ArcanistAndroidLinter' => 'lint/linter/ArcanistAndroidLinter.php',
    'ArcanistCheckstyleLinter' => 'lint/linter/JavaCheckstyleLinter.php',
    'ArcanistESLintLinter' => 'lint/linter/ESLintLinter.php',
    'ArcanistFindBugsLinter' => 'lint/linter/FindBugsLinter.php',
    'ArcanistGradleFindBugsLinter' => 'lint/linter/GradleFindBugsLinter.php',
    'ArcanistOCLintLinter' => 'lint/linter/OCLintLinter.php',
    'ArcanistSassLinter' => 'lint/linter/SassLinter.php',
    'ArcanistSingleRunLinter' => 'lint/linter/ArcanistSingleRunLinter.php',
    'CheckstyleGradleLintProvider' => 'lint/provider/gradle/CheckstyleGradleLintProvider.php',
    'CheckstyleMavenLintProvider' => 'lint/provider/maven/CheckstyleMavenLintProvider.php',
    'CheckstyleParser' => 'lint/parser/CheckstyleParser.php',
    'ConfigPathLinter' => 'lint/linter/ConfigPathLinter.php',
    'CpdGradleLintProvider' => 'lint/provider/gradle/CpdGradleLintProvider.php',
    'CpdMavenLintProvider' => 'lint/provider/maven/CpdMavenLintProvider.php',
    'CpdParser' => 'lint/parser/CpdParser.php',
    'FindbugsGradleLintProvider' => 'lint/provider/gradle/FindbugsGradleLintProvider.php',
    'FindbugsMavenLintProvider' => 'lint/provider/maven/FindbugsMavenLintProvider.php',
    'FindbugsParser' => 'lint/parser/FindbugsParser.php',
    'GradleCheckstyleLinter' => 'lint/linter/GradleCheckstyleLinter.php',
    'GradleLintProvider' => 'lint/provider/gradle/GradleLintProvider.php',
    'GradleLinter' => 'lint/linter/GradleLinter.php',
    'GradlePmdLinter' => 'lint/linter/GradlePmdLinter.php',
    'GradleUnitTestEngine' => 'unit/engine/GradleUnitTestEngine.php',
    'LintProvider' => 'lint/provider/LintProvider.php',
    'MavenLintProvider' => 'lint/provider/maven/MavenLintProvider.php',
    'MavenLinter' => 'lint/linter/MavenLinter.php',
    'MavenUnitTestEngine' => 'unit/engine/MavenUnitTestEngine.php',
    'PmdGradleLintProvider' => 'lint/provider/gradle/PmdGradleLintProvider.php',
    'PmdLinter' => 'lint/linter/PmdLinter.php',
    'PmdMavenLintProvider' => 'lint/provider/maven/PmdMavenLintProvider.php',
    'PmdParser' => 'lint/parser/PmdParser.php',
    'RegexpLinter' => 'lint/linter/RegexpLinter.php',
  ),
  'function' => array(),
  'xmap' => array(
    'AbstractMetaLinter' => 'ArcanistSingleRunLinter',
    'AndroidGradleLintProvider' => 'GradleLintProvider',
    'AndroidParser' => 'AbstractFileParser',
    'ArcanistAndroidLinter' => 'ArcanistLinter',
    'ArcanistCheckstyleLinter' => 'ArcanistSingleRunLinter',
    'ArcanistESLintLinter' => 'ConfigPathLinter',
    'ArcanistFindBugsLinter' => 'ArcanistSingleRunLinter',
    'ArcanistGradleFindBugsLinter' => 'ArcanistFindBugsLinter',
    'ArcanistOCLintLinter' => 'ArcanistLinter',
    'ArcanistSassLinter' => 'ConfigPathLinter',
    'ArcanistSingleRunLinter' => 'ArcanistLinter',
    'CheckstyleGradleLintProvider' => 'GradleLintProvider',
    'CheckstyleMavenLintProvider' => 'MavenLintProvider',
    'CheckstyleParser' => 'AbstractFileParser',
    'ConfigPathLinter' => 'ArcanistExternalLinter',
    'CpdGradleLintProvider' => 'GradleLintProvider',
    'CpdMavenLintProvider' => 'MavenLintProvider',
    'CpdParser' => 'AbstractFileParser',
    'FindbugsGradleLintProvider' => 'GradleLintProvider',
    'FindbugsMavenLintProvider' => 'MavenLintProvider',
    'FindbugsParser' => 'AbstractFileParser',
    'GradleCheckstyleLinter' => 'ArcanistCheckstyleLinter',
    'GradleLintProvider' => 'LintProvider',
    'GradleLinter' => 'AbstractMetaLinter',
    'GradlePmdLinter' => 'PmdLinter',
    'GradleUnitTestEngine' => 'ArcanistUnitTestEngine',
    'MavenLintProvider' => 'LintProvider',
    'MavenLinter' => 'AbstractMetaLinter',
    'MavenUnitTestEngine' => 'ArcanistUnitTestEngine',
    'PmdGradleLintProvider' => 'GradleLintProvider',
    'PmdLinter' => 'ArcanistSingleRunLinter',
    'PmdMavenLintProvider' => 'MavenLintProvider',
    'PmdParser' => 'AbstractFileParser',
    'RegexpLinter' => 'ArcanistLinter',
  ),
));
