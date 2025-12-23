#!/bin/bash

# Remove previous compiled information
rm languages/*.json
rm myclub-sections.zip
rm -fR blocks/build

# Install all node.js dependencies
npm install

# Build Gutenberg blocks
npm run build

# Extract and rename translations
wp i18n make-json languages --no-purge
python3 tools/update_translation_files.py

# Create a zip file for the plugin
zip -r myclub-sections.zip . -x "tools/*" -x ".idea/*" -x "*.git*" -x "node_modules/*" -x "compile-plugin.sh" -x "myclub-sections.scss" -x ".distignore" -x ".gitignore"
