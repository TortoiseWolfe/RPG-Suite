# Deployment Guide for RPG-Suite

## Build Strategy Overview

RPG-Suite uses WordPress's built-in React infrastructure (`@wordpress/scripts`) to ensure compatibility and avoid duplicating React across plugins.

## Repository Structure

```
RPG-Suite/
├── rpg-suite.php         # Main plugin file
├── assets/               # CSS and static assets
├── react-app/           # React source code
│   ├── src/             # Source files (committed)
│   ├── build/           # Built files (gitignored)
│   └── package.json     # Dependencies
├── build/               # Final build output (gitignored)
└── docs/                # Documentation
```

## What Gets Committed

**COMMIT:**
- All PHP files
- Source JavaScript/React files (`react-app/src/`)
- Configuration files (`package.json`, etc.)
- Documentation
- Assets (CSS, images)

**DO NOT COMMIT:**
- Built JavaScript files (`build/`, `react-app/build/`)
- Node modules (`node_modules/`)
- Temporary files

## Build Process

### Local Development
```bash
# Install dependencies
cd react-app
npm install

# Development mode (watch for changes)
npm run start

# Production build
npm run build
```

### For Deployment
```bash
# Run the build script
./build.sh
```

## Deployment Options

### Option 1: Build on Deployment Server
```bash
# On the deployment server
git pull
./build.sh
# Copy plugin files to WordPress plugins directory
```

### Option 2: CI/CD Pipeline
```yaml
# Example GitHub Actions workflow
name: Build and Deploy
on:
  push:
    branches: [main]
jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: actions/setup-node@v2
      - run: cd react-app && npm install
      - run: cd react-app && npm run build
      - run: # Deploy to server
```

### Option 3: Pre-built for Distribution
For WordPress.org repository or direct downloads:
1. Build locally
2. Create a distribution package including built files
3. Exclude development files

## WordPress.org Submission

If submitting to WordPress.org:
1. Build files locally
2. Include built JavaScript in submission
3. Exclude development files (src/, node_modules/)
4. Follow WordPress plugin guidelines

## Important Notes

1. **Different React for Different Contexts**:
   - Development: Source files only
   - Production deployment: Built during deployment
   - Distribution (WordPress.org): Pre-built files included

2. **Why WordPress Scripts?**:
   - Avoids duplicate React instances
   - Ensures compatibility with WordPress updates
   - Follows WordPress best practices
   - Integrates with block editor

3. **Build Location**:
   - Development builds are temporary
   - Production builds happen on deployment
   - Distribution builds are included in package

## Deployment Checklist

- [ ] Code reviewed and tested
- [ ] Dependencies updated
- [ ] Build process successful
- [ ] No console errors
- [ ] Character functionality works
- [ ] BuddyPress integration tested
- [ ] REST API endpoints responding
- [ ] React components rendering correctly

## Troubleshooting

### Build Fails
- Check Node.js version (16+)
- Clear node_modules and reinstall
- Verify package.json is correct

### React Not Loading
- Check if build files exist
- Verify script enqueue in PHP
- Check browser console for errors
- Ensure WordPress dependencies loaded

### API Errors
- Verify REST API is enabled
- Check nonce configuration
- Ensure permalinks are set
- Test endpoints directly