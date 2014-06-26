#!/bin/bash

set -e -o pipefail -o nounset;

OPTS="hp:";
DEFAULT_INSTALL_PATH="/opt"
DEFAULT_TARGET_NAME="arc-lint"
DEFAULT_REPO_HOST="git@monits.com"
DEFAULT_REPO_PATH="monits/arc-lint"

COPY="Monits 2014";
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
    echo "Copyright $COPY"
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
    git tag --sort='-v:refname' | grep -E "$1" -m 1;
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
            ;;

        "rc")
            tag=$(find_tag "^.+-RC[0-9]+$");
            ;;
        "edge")
            tag="staging";
            ;;
        *)
            ;;
    esac;
    git checkout "$tag" > /dev/null 2>&1;
    git pull origin "$tag" > /dev/null 2>&1;
    cd -
}

function _repo_is_installed() {
    test -d "$REPO_TARGET_PATH" 
    return $?
}


function install_posix() {
    echo "installing $1 version at $TARGET_PATH";
    if _repo_is_installed; then
        echo "$TARGET_PATH exists. Aborting";
        exit 1;
    fi

    sudo mkdir -p "$REPO_TARGET_PATH"
    sudo chown "$USER:$USER" -R "$REPO_TARGET_PATH"
    
    clone_repo;
    checkout "$1";

    sudo ln -s "$REPO_TARGET_PATH/src" "$TARGET_PATH";
    sudo ln -s "$REPO_TARGET_PATH/scripts/install.sh" "$SCRIPT_PATH";
    sudo chmod uga+x "$SCRIPT_PATH";
    sudo chown "$USER:$USER" -R "$TARGET_PATH";

    echo "Done."
}


function update_posix() {
    echo "updating $1 at $TARGET_PATH";
    checkout "$1"
    echo "Done."
}


function remove_posix() {
    echo "Removing $1 at $TARGET_PATH";
    sudo rm -rf "$TARGET_PATH";
    sudo rm -rf "$REPO_TARGET_PATH";
    sudo rm -f "$SCRIPT_PATH";
    echo "Done."
}

function main() {
    parse_options $@;
    shift $((OPTIND - 1));
   
    CMD=${1:-"install"}; 
    TAG=${2:-"stable"};

    INSTALL_PATH=${INSTALL_PATH:-$DEFAULT_INSTALL_PATH};
    TARGET_NAME=${TARGET_NAME:-$DEFAULT_TARGET_NAME};
    DEFAULT_TARGET_PATH="$INSTALL_PATH/$TARGET_NAME";
    TARGET_PATH=${TARGET_PATH:-$DEFAULT_TARGET_PATH};
    REPO_TARGET_PATH="$(dirname "$TARGET_PATH")/.$(basename "$TARGET_PATH")";
    SCRIPT_PATH="/usr/bin/arclintstaller"
    
    case "$CMD" in
        "install")
            install_posix $TAG;
            ;;

        "update")
            update_posix $TAG;
            ;;

        "remove")
            remove_posix $TAG;
            ;;
    esac;
}

main $@;
