symbols:
    arc liberate src/

docs:
    mkdir -p docs/api
    phpdoc -d ./src -t ./docs/api
