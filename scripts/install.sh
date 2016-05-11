#!/bin/bash

set -e -o pipefail -o nounset;

OPTS="h";

DEFAULT_INSTALL_PATH="/usr/local/opt";
DEFAULT_TARGET_NAME="arc-lint";
DEFAULT_REPO_HOST="git@monits.com";
DEFAULT_REPO_PATH="monits/arc-lint";
DEFAULT_PREFIX="/usr/local"
BIN_PATH="${INSTALL_PREFIX:-$DEFAULT_PREFIX}/bin"

HELP=$(cat << _EOF
Usage: $0 [OPTIONS] [ARGUMENTS]

OPTIONS
    -h          Display this message.

ARGUMENTS
    install     [version tag or commit]
    update      [version tag or commit]
    remove
_EOF
);

function print_help() {
    echo
    echo "$HELP"
    echo
    echo
}

function parse_options() {
    while getopts "$OPTS" opt; do
        case "$opt" in
            "h" | "?")
                print_help;
                exit 1;
                ;;

            *)
                break;
                ;;
        esac;
    done;
}

function find_tag() {
    # 4) sort by dev, RC and final
    # 3) sort by revision
    # 2) sort by minor
    # 1) sort by major
    # all sorts are stable, and in reverse order so they stack up.
    git tag \
        | sort -drs -t'-' -k2,2     \
        | sort -nrs -t'.' -k3,3     \
        | sort -nrs -t'.' -k2,2     \
        | sort -nrs -t'.' -k1,1     \
        | grep -E "$1" -m 1         \
        || echo ""                  \
        ;
}

function clone_repo() {
    local host=${REPO_HOST:-$DEFAULT_REPO_HOST};
    local path=${REPO_PATH:-$DEFAULT_REPO_PATH};
    local src="$host:$path";
    git clone "$src" "$REPO_TARGET_PATH" > /dev/null 2>&1;
}

function checkout() {
   cd "$REPO_TARGET_PATH"
   local tag="$1";
   case "$tag" in
       "stable")
            tag=$(find_tag "^[^-]+$");
            if [ -z "$tag" ]; then
                echo "Couldn't find a stable version for this repo.";
                return 4;
            fi
            ;;

        "rc")
            tag=$(find_tag "^.+-RC[0-9]+$");
            if [ -z "$tag" ]; then
                echo "Couldn't find a release candidate for this repo.";
                return 4;
            fi
            ;;
        "edge")
            tag="staging";
            ;;
        *)
            ;;
    esac;
    git fetch;
    git checkout "$tag" > /dev/null 2>&1;
    git pull origin "$tag" > /dev/null 2>&1;
    cd - > /dev/null
}

function install_posix() {
    # clean everything up so we can start afresh.
    remove_posix;

    echo "Installing $1 version at $TARGET_PATH";

    sudo mkdir -p "$REPO_TARGET_PATH"
    sudo chown -R "$USER" "$REPO_TARGET_PATH"

    sudo mkdir -p "$BIN_PATH"

    clone_repo;
    checkout "$1";

    sudo ln -fs "$REPO_TARGET_PATH/src" "$TARGET_PATH";
    sudo ln -fs "$REPO_TARGET_PATH/scripts/install.sh" "$SCRIPT_PATH";
    sudo chmod uga+x "$SCRIPT_PATH";
    sudo ln -fs "$REPO_TARGET_PATH/scripts/install.sh" "$SCRIPT_PATH_ALT";
    sudo chmod uga+x "$SCRIPT_PATH_ALT";

    sudo chown -R "$USER" "$TARGET_PATH";

    if [[ ! "$PATH" =~ ^(.*:)?"$BIN_PATH"(:.*)?$ ]]; then
        echo "You should add $BIN_PATH to your PATH in your .bashrc:"
        echo "echo \"export PATH=\\\"\\\$PATH:$BIN_PATH\\\"\" >> ~/.bashrc"
    fi

    echo "Done."
}


function update_posix() {
    echo "updating $1 at $TARGET_PATH";
    checkout "$1"
    echo "Done."
}


function remove_posix() {
    sudo rm -rf "$TARGET_PATH";
    sudo rm -rf "$REPO_TARGET_PATH";
    sudo rm -f "$SCRIPT_PATH";
    sudo rm -f "$SCRIPT_PATH_ALT";
}

function main() {
    parse_options "$@";
    shift $((OPTIND - 1));

    CMD=${1:-"install"};
    TAG=${2:-"stable"};

    INSTALL_PATH=${INSTALL_PATH:-$DEFAULT_INSTALL_PATH};
    TARGET_NAME=${TARGET_NAME:-$DEFAULT_TARGET_NAME};
    DEFAULT_TARGET_PATH="$INSTALL_PATH/$TARGET_NAME";
    TARGET_PATH=${TARGET_PATH:-$DEFAULT_TARGET_PATH};
    REPO_TARGET_PATH="$(dirname "$TARGET_PATH")/.$(basename "$TARGET_PATH")";
    SCRIPT_PATH="$BIN_PATH/arclintstaller"
    SCRIPT_PATH_ALT="$BIN_PATH/arc-extensions"

    case "$CMD" in
        "install")
            install_posix "$TAG";
            ;;

        "update")
            update_posix "$TAG";
            ;;

        "remove")
            echo "Removing $TARGET_PATH";
            remove_posix;
            echo "Done."
            ;;
    esac;
}

main "$@";
