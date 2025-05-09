# RPG-Suite System Requirements

## Overview
RPG-Suite is a WordPress plugin designed to add role-playing game functionality to WordPress sites using BuddyPress. The system allows users to create and manage characters with a unique d7-based system in a steampunk world with airballoons and zeppelins.

## Core Requirements

### User and Character Management
1. Users can create multiple characters (default limit: 2)
2. Only one character can be active at a time
3. Users can switch between their characters
4. Character ownership is tied to WordPress user accounts

### Character Data
1. Characters are stored as custom post types (`rpg_character`)
2. Characters have d7-based attributes (Fortitude, Precision, Intellect, Charisma)
3. Skills use d7 die codes (e.g., "3d7+2")
4. Characters can create and manage inventions/gadgets
5. Active status is tracked in character metadata
6. Characters have ownership relationship with WordPress users

### d7 System
1. Attributes and skills use d7 dice codes
2. Digital dice rolling implementation for d7 system
3. Invention mechanics for creating gadgets
4. Character classes representing different professions

### BuddyPress Integration
1. Active character displays on user's BuddyPress profile
2. Character information appears in profile header
3. Character switching interface is accessible from profile
4. Compatible with BuddyX theme
5. Character inventions displayed on profile

### Plugin Architecture
1. Modular design with clear separation of concerns
2. PSR-4 compliant autoloading
3. Event-based communication between components
4. Global accessibility via `$rpg_suite` variable
5. Proper hook registration and priority handling
6. Die code utility for handling d7 system

## Future Requirements (Post-MVP)
1. Character health and stat management
2. Character progression systems
3. Advanced invention creation mechanics
4. Enhanced character display options
5. Admin configuration for character limits and features