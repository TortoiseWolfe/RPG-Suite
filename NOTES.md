# RPG Suite Development Notes

## Repository Information
- **GitHub Repository**: https://github.com/TortoiseWolfe/RPG-Suite
- **Branch Strategy**: 
  - `main` for stable releases
  - `develop` for ongoing development
  - Feature branches for new subsystems

## Architecture Notes

### Core Architecture Decisions

1. **Single Plugin vs Multiple Plugins**
   - Decision: Single plugin with toggle-able subsystems
   - Rationale: Easier installation, centralized configuration, and simplified dependency management
   - Implementation: Dashboard toggle UI, conditional subsystem loading

2. **Event System Design**
   - Decision: Symfony-style event dispatcher wrapping WordPress actions
   - Rationale: Type safety, IDE autocomplete support, familiarity for modern PHP developers
   - Implementation: Event, Event_Dispatcher, and Event_Subscriber classes

3. **Autoloading Strategy**
   - Decision: PSR-4 autoloading with Composer
   - Rationale: Modern PHP standards, compatibility with existing tools
   - Implementation: Class files follow PSR-4 naming conventions

### Subsystem Implementation Plan

#### Phase 1: Foundation (MVP)

1. **Core Subsystem**
   - [x] Event system implementation
   - [x] Plugin infrastructure (activation, deactivation, etc.)
   - [x] Capabilities and role management
   - [ ] Admin dashboard
   - [ ] Settings API integration
   - [ ] REST API foundations

2. **Health Subsystem**
   - [ ] HP field creation in BuddyPress profiles
   - [ ] Visual HP bar display
   - [ ] HP modification API
   - [ ] Status effects system (poison, stun, etc.)
   - [ ] Admin interface for HP management

3. **Geo Subsystem**
   - [ ] Location storage in user meta
   - [ ] Privacy controls
   - [ ] Map integration
   - [ ] Geofencing for game zones
   - [ ] Distance calculations

#### Phase 2: Mechanics

4. **Dice Subsystem**
   - [ ] Virtual dice rolling engine
   - [ ] Different dice types (d4, d6, d8, d10, d12, d20, etc.)
   - [ ] Complex roll formulas (2d6+3)
   - [ ] Advantage/disadvantage mechanics
   - [ ] Critical success/failure handling

5. **Inventory Subsystem**
   - [ ] Item custom post type
   - [ ] Character-item relationships
   - [ ] Equipment slots
   - [ ] Weight/encumbrance calculations
   - [ ] Item attributes and effects

6. **Combat Subsystem**
   - [ ] Initiative tracking
   - [ ] Turn-based mechanics
   - [ ] Attack/defense formulas
   - [ ] Combat logging
   - [ ] Integration with Health and Dice subsystems

#### Phase 3: Game Structure

7. **Quest Subsystem**
   - [ ] Quest creation UI for Game Masters
   - [ ] Progress tracking
   - [ ] Branching storylines
   - [ ] Reward distribution
   - [ ] GamiPress integration (optional)

## Technical Considerations

### BuddyPress Integration
- Leverage xprofile fields for character attributes
- Use BuddyPress activity stream for game events
- Build on top of friends system for party mechanics

### Performance Considerations
- Cache expensive calculations
- Use transients for short-lived data
- Implement database denormalization where appropriate
- Object caching for RPG state data

### Security Measures
- Capability checks for all actions
- Data validation and sanitization
- Prevention of meta value manipulation
- Rate limiting for dice rolls and combat actions

## Development Workflow

1. **New Features**
   - Create feature branch from `develop`
   - Implement feature with tests
   - Open PR to `develop`
   - Code review and merge

2. **Releases**
   - Merge `develop` to `main`
   - Tag with version number
   - Generate release assets

3. **Hotfixes**
   - Create hotfix branch from `main`
   - Implement fix with tests
   - Open PR to both `main` and `develop`

## Code Standards
- Follow WordPress Coding Standards
- PSR-12 for areas not covered by WPCS
- PHPDoc for all functions, methods, and classes
- Type hints and return type declarations where possible

## Testing Strategy
- Unit tests for core functionality
- Integration tests for WordPress/BuddyPress integration
- Acceptance tests for critical user flows
- Manual testing for UI components

## Documentation
1. **Code Documentation**
   - PHPDoc for all code
   - README for installation and basic usage
   - CONTRIBUTING.md for developer guidelines

2. **User Documentation**
   - Admin documentation in the dashboard
   - Tooltips for complex UI elements
   - Video tutorials for key features