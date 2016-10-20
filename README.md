Arc Extensions
==============

(Because we couldn't find a better name!)


# Installation

From command line, run:

```
$ ./scripts/install.sh install
```

It will request your password to run with `sudo` the few steps it needs.

After execution, the library will be installed under `/usr/local/opt/arc-lint`,
and the `install.sh` script will be accesible under `/usr/local/bin/arc-extensions`
and `/usr/local/bin/arclintstaller`

## Upgrading

Once installed, the library can be easily upgraded by running from command line:

```
$ arc-extensions update [version]
```

By default, it will get the latest stable release, but you can specify any version number.


## Using the library with other projects

In your arcanist project of choice, add the following to your `.arcconfig`:

    {
    ...
        "load": [
            "/usr/local/opt/arc-lint"
        ]
    ...
    }

# Available Linters

## ESLint

[ESLint](http://eslint.org/) is a highly customizable, flexible javascript linter. It depends on
Esprima for AST parsing and nodejs as the runtime.

## Sass (scss-lint)

[SCSS Lint](https://github.com/brigade/scss-lint) is a ruby gem that checks both the style and common mistakes in your
Sass files. It can also check some Compass extensions too!

## CPD

[PMD's Copy Paste Detector](https://pmd.github.io/) is available as a stand alone tool for over a dozen different languages including Swift, Objective-C, Java, Python, Go, Javascript, PHP, Ruby and many more.

## Maven & Gradle projects

We support Maven and Ggradle projects performing a single run to generate a bunch of different reports. The extension efficiently performs the tasks needed just for the required lint providers.

Adding new providers to extend this feature set is easy.

Supported providers:

#### Checkstyle

[Checkstyle](http://checkstyle.sourceforge.net/) is a generic style checker for Java.

#### PMD

[PMD](https://pmd.github.io/) is a source code analyzer. It finds common programming flaws like unused variables, empty catch blocks, unnecessary object creation, and so forth. It supports Java, JavaScript, Salesforce.com Apex, PLSQL, Apache Velocity, XML, XSL. 

#### FindBugs

[FindBugs](http://findbugs.sourceforge.net/) is a tool to analyze JVM bytecode in search of errors and inneficient idioms. Applicable to any project compiling into JVM bytecode (Scala, Groovy, Java, and so on).

#### CPD

[PMD's Copy Paste Detector](https://pmd.github.io/). Besides being supported as stand-alone, it can also be used as part of a Gradle / Maven build.

#### Codenarc

[Codenarc](http://codenarc.sourceforge.net/) nalyzes Groovy code for defects, bad practices, inconsistencies, style issues and more.

#### Android Lint - Gradle only

[Android Lint](https://developer.android.com/studio/write/lint.html) can help you to easily identify and correct problems with the structural quality of your code, without having to execute the app or write any test cases. 


## OCLint

[OCLint](http://oclint.org/) is an Objective-C linter and style checker. Integrated with [clang's static anayzer](http://clang-analyzer.llvm.org/).


## ShellCheck

[ShellCheck](https://www.shellcheck.net/) finds bugs in your shell scripts.

## TSLint

[TSLint](https://palantir.github.io/tslint/) checks your [TypeScript](http://www.typescriptlang.org/) code for readability, maintainability, and functionality errors.

## Filename Regexp

Runs a custom regular expression against filenames, to apply arbitrary file naming rules (ie: using a given preffix).

## Regexp

Runs a custom regular expression against file contents, searching for invalid strings and optionally providing an autofix.

## Indentation

A generic indentation analyzer. Currently only supports json and xml files.


# Available Unit Engines

## GradleUnitTestEngine

Runs gradle with the `test` target, obtaining XUnit results from `build/test-results/`

## MavenUnitTestEngine

Runs maven with the `test` target, obtaining XUnit results from `target/surefire-reports/`

## XcodebuildUnitTestEngine

Runs unit tests as configured from XCode


