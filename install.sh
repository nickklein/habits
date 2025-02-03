#!/bin/bash

TARGET_DIR="../core/resources/js/Pages/Habits"
SOURCE_DIR="$(pwd)/resources/js"

if [ "$1" == "dev" ]; then
    echo "Setting up development symlink..."
    rm -rf "$TARGET_DIR"
    ln -s "$SOURCE_DIR" "$TARGET_DIR"
    echo "Symlink created: $TARGET_DIR -> $SOURCE_DIR"

elif [ "$1" == "build" ]; then
    echo "Preparing production build..."
    rm -rf "$TARGET_DIR"
    mkdir -p "$(dirname "$TARGET_DIR")"
    cp -r "$SOURCE_DIR" "$TARGET_DIR"
    echo "Files copied to $TARGET_DIR"

else
    echo "Usage: $0 {dev|build}"
    exit 1
fi
