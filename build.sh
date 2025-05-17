#!/bin/bash

echo "Building RPG-Suite plugin..."

# Navigate to React app directory
cd react-app

# Install dependencies if needed
if [ ! -d "node_modules" ]; then
    echo "Installing dependencies..."
    npm install
fi

# Build with WordPress scripts
echo "Building with WordPress scripts..."
npm run build

# The build output goes to ../build automatically with wp-scripts
echo "Build complete!"