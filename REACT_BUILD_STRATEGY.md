# React Build Strategy for RPG-Suite

## The Problem
We need to determine how React will be built and distributed with our WordPress plugin.

## Options Analysis

### Option 1: Pre-built React Bundle (Current Approach)
**How it works:**
- Build React locally with webpack
- Commit the built files to the repository
- Deploy includes pre-built JavaScript

**Pros:**
- No build step needed on deployment
- Works immediately after plugin activation
- Consistent across all installations

**Cons:**
- Larger repository size
- Built files in version control
- Need to rebuild before each commit
- Potential merge conflicts in built files

### Option 2: Build During Deployment
**How it works:**
- Only source files in repository
- Build step during deployment process
- Each server builds its own bundle

**Pros:**
- Smaller repository (no built files)
- Cleaner version control
- No merge conflicts on built files

**Cons:**
- Requires Node.js on deployment server
- Deployment is more complex
- Build must succeed for plugin to work
- Different servers might have different builds

### Option 3: Use WordPress's Built-in React
**How it works:**
- Use wp-scripts and @wordpress/scripts
- Leverage WordPress's existing React/Block Editor infrastructure
- No custom webpack needed

**Pros:**
- Consistent with WordPress ecosystem
- No duplicate React versions
- Automatic dependency management
- Better integration with block editor

**Cons:**
- Limited to WordPress's React version
- Must follow WordPress's build conventions
- Less flexibility in tooling

### Option 4: CDN-based React
**How it works:**
- Load React from CDN
- Only ship our custom components
- No React in our bundle

**Pros:**
- Smallest plugin size
- Cached across sites
- No build complexity for React itself

**Cons:**
- External dependency
- Requires internet connection
- Version management challenges
- Potential conflicts with other plugins

## Recommendation: Option 3 - WordPress Scripts

For RPG-Suite, we should use WordPress's built-in React infrastructure because:

1. **It's the WordPress Way**: Following WordPress conventions ensures better compatibility
2. **No Duplicate React**: Avoids loading React multiple times
3. **Automatic Updates**: When WordPress updates React, our plugin benefits
4. **Better Integration**: Works seamlessly with Gutenberg and other React-based features
5. **Simplified Build**: WordPress handles the complex webpack configuration

## Implementation Steps

1. Convert to @wordpress/scripts
2. Use wp.element instead of React directly
3. Build with wp-scripts build
4. Enqueue with proper WordPress dependencies
5. Commit only source files, not builds
6. Add build step to deployment process

## Build Location Decision

- **Development**: Build locally for testing
- **Repository**: Only source files (no build directory)
- **Deployment**: Build during deployment or CI/CD
- **Distribution**: Pre-built for WordPress.org repository

This approach provides the best balance of:
- Clean version control
- WordPress compatibility
- Deployment flexibility
- User experience