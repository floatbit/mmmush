#!/bin/bash

# Check if all required parameters are provided
if [ "$#" -ne 3 ]; then
    echo "Usage: $0  <Block category e.g. kudosnyc> <Block id e.g. grid-people> <Block name e.g. \"Grid of People\">"
    exit 1
fi

# Assign parameters to variables
category=$1
id=$2
name=$3

# Create a copy of the 'template' folder with the new id as the folder name
cp -r blocks/template "blocks/$id"

# Update the JSON file
sed "s|<id>|$id|g; s|<category>|$category|g; s|<name>|$name|g" "blocks/$id/block.json" > "blocks/$id/block_temp.json"
mv "blocks/$id/block_temp.json" "blocks/$id/block.json"

# Update the PHP file
sed "s|<id>|$id|g; s|<category>|$category|g; s|<name>|$name|g" "blocks/$id/block.php" > "blocks/$id/block_temp.php"
mv "blocks/$id/block_temp.php" "blocks/$id/block.php"

# Create a _$id.scss file in src/scss/blocks folder
echo ".block-$id {}" > "src/scss/blocks/_$id.scss"

# Add import statement to the bottom of src/scss/blocks/_index.scss
echo "" >> "src/scss/blocks/_index.scss"
echo "@import '$id';" >> "src/scss/blocks/_index.scss"

echo "$id block created successfully! "
