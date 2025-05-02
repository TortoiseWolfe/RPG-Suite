#!/bin/bash

# Initialize a new Git repository for RPG Suite
echo "Initializing Git repository for RPG Suite..."
cd "$(dirname "$0")"

# Initialize the repository
git init

# Add all files to the repository
git add .

# Create initial commit
git commit -m "Initial commit of RPG Suite"

# Instructions for setting up GitHub repository
echo ""
echo "Repository initialized successfully!"
echo ""
echo "Next steps to set up GitHub repository:"
echo "1. Create a new repository on GitHub (without README, .gitignore, or license)"
echo "2. Run the following commands to push to GitHub:"
echo "   git remote add origin https://github.com/YOUR-USERNAME/RPG-Suite.git"
echo "   git push -u origin main"
echo ""
echo "Replace 'YOUR-USERNAME' with your actual GitHub username."