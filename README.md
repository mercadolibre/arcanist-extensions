Arc checkstyle
==============


(Because we couldn't find a better name!)


# Documentation

## Building the docs

The docs should come bundled with this repo. If you suspect such magnificent
work of literary art might be outdated, don't despair: we use PHPDoc to help
us compile this fine words into a beautiful book, and the good news is that
you can run it yourself.

First, you're gonna need phpdoc:

    apt-get install php-pear
    pear channel-discover pear.phpdoc.org
    pear install --alldeps phpdoc/phpdocumentor

Then just run `make docs`. Easy, see?


## Reading the docs

1. Open the docs.
2. Read the docs.
3. ???
4. Profit!

The docs are in docs/api/index.html, but you already figured that out.


# Installation

## Using the library with other projects

In your arcanist project of choice, add the following to your `.arcconfig`:

    {
    ...
        "load": [
            "arc-checkstyle/src"
        ]
    ...
    }

You might be wondering: arc looks for arc-checkstyle wherever .arcconfig is
located, and then in the parent folder. And you would be right.

## Adding linters to the project

Arcanist uses a configuration file `.arclint` located alongside the good old
`.arcconfig` to tune most of the linter parameters. This shouldn't be highly
specific, but it does vary from project to project. You might use this
project's `.arclint` as a start, though.

Most linters require a configuration file located at the root of the project.
You will find the most common configurations for your language or platform
under `configs/`.

# Custom Linters

## Available Linters

### ESLint

ESLint is a highly customizable, flexible javascript linter. It depends on
Esprima for AST parsing and nodejs as the runtime.

### Sass (scss-lint)

SCSS Lint is a ruby gem that checks both the style and common mistakes in your
Sass files. It can also check some Compass extensions too!

### Java (Checkstyle)

Checkstyle is a generic style checker for C-like languages.

### OCLint

[OCLint](oclint.org) is an Objective-C linter and style checker. Though not as
flexible or easy to use as most linters for other languages, it is the most
stable option at the time. It can be integrated with XCode and other tools via
built-in hooks or standard output.

## Documentation on the Linters

You can find more about each linter in the attached README.md files under
`configs`.

# Building

## Updating Symbols

Run `make symbols`, which just runs `arc liberate src/`.
