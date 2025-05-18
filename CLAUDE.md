# Claude Code Notes for RPG-Suite

## Development Workflow
1. Check previous session results in debug.log to understand current state
2. Review any feedback from testing department
3. Implement code changes, bug fixes, or new features
4. Build React components using build.sh to create testing artifacts
5. Log development progress to debug.log

## Development Logging

### Debug Log Management
- Development progress is logged to `/home/turtle_wolfe/repos/two_Tubes/RPG-Suite/debug.log`
- ALWAYS OVERWRITE the file with only the most recent development session
- Do not append to existing content; each session gets a fresh log
- Use timestamp format: `[YYYY-MM-DD HH:MM:SS]` 
- Include tags like BUILD, FIX, UPDATE, ERROR, SOLUTION, etc.
- Document all development changes and rationale

## Current Development Focus

### Implementation Status
- RPG-Suite uses a modular architecture with React frontend
- Character system is implemented with custom post type
- BuddyPress integration displays characters on profiles
- React components handle character display and switching
- REST API provides character data endpoints

### Key Development Areas
1. React component improvements and bug fixes
2. Character Manager backend enhancements
3. API endpoint optimization
4. BuddyPress profile integration refinements
5. Performance and caching improvements

### Code Structure
- Main plugin file: rpg-suite.php
- React app: react-app/ directory
- Specifications: spec/ directory
- AI documentation: ai_docs/ directory
- Build script: build.sh (creates build artifacts after development)

### Development Commands
```bash
# Check what needs work
cat debug.log

# After making changes, build for testing
./build.sh

# View current React components
ls react-app/src/components/
```

### Build Process
- Development happens in react-app/src/
- After code changes, run build.sh to create artifacts
- Build outputs to react-app/build/
- Built files are what testing department receives

### Notes from Previous Sessions
- Redis cache fixes have been implemented
- Character switching works via REST API
- PHP fallback display ensures immediate visibility
- React progressive enhancement when available