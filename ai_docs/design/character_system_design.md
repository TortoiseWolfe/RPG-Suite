# Character Management System Design

**Author:** TurtleWolfe
**Repository:** https://github.com/TortoiseWolfe/RPG-Suite

## Overview
The character management system is the core component of RPG-Suite that provides dynamic, real-time character display and management through a React-based frontend while maintaining WordPress backend integration. The system features a unique d7-based dice system designed specifically for a steampunk world.

## Architecture Overview

The character system uses a hybrid architecture:
- **Backend**: WordPress custom post types and REST API for data storage
- **Frontend**: React-based character sheets for dynamic, responsive UI
- **Caching**: Multi-layer caching for optimal performance
- **Real-time**: WebSocket support for live updates (future phase)

## Implementation Approach

Based on lessons learned, the implementation follows these priorities:

1. Fix the character post type capability issues
2. Implement core REST API endpoints
3. Build React-based character sheet components
4. Add real-time update capabilities
5. Integrate with BuddyPress profiles

## Data Model

### Character Post Type
Characters are stored as a custom post type with enhanced capability mapping:

| Field | Description |
|-------|-------------|
| ID | WordPress post ID, unique identifier |
| post_title | Character name |
| post_content | Character description/biography |
| post_author | WordPress user ID (character owner) |
| post_status | Publication status (publish, draft, etc.) |

### Character Meta with React Support
Core character data stored in post meta with REST API visibility:

| Meta Key | Type | Description |
|----------|------|-------------|
| _rpg_active | boolean | Whether this character is the user's active character |
| _rpg_attributes | array | Basic attributes for the d7 system |
| _rpg_class | string | Character class (Aeronaut, Mechwright, etc.) |
| _rpg_last_modified | string | ISO timestamp for cache invalidation |
| _rpg_revision_id | string | Unique ID for conflict resolution |
| _rpg_cache_version | int | Version for cache busting |

## Post Type Registration (Fixed)

The character post type is registered with proper capability mapping:

```php
register_post_type('rpg_character', [
    'labels' => [
        'name' => __('Characters', 'rpg-suite'),
        'singular_name' => __('Character', 'rpg-suite'),
        'add_new' => __('Add New Character', 'rpg-suite'),
        'add_new_item' => __('Add New Character', 'rpg-suite'),
        'edit_item' => __('Edit Character', 'rpg-suite'),
        'new_item' => __('New Character', 'rpg-suite'),
        'view_item' => __('View Character', 'rpg-suite'),
        'search_items' => __('Search Characters', 'rpg-suite'),
        'not_found' => __('No characters found', 'rpg-suite'),
        'not_found_in_trash' => __('No characters found in trash', 'rpg-suite'),
    ],
    'public' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,  // Enable REST API support
    'rest_base' => 'characters',
    'rest_controller_class' => 'RPG_Suite_Character_REST_Controller',
    'supports' => ['title', 'editor', 'thumbnail', 'revisions'],
    'has_archive' => false,
    'capability_type' => 'rpg_character',
    'map_meta_cap' => true,
    'capabilities' => [
        'publish_posts' => 'publish_rpg_characters',
        'edit_posts' => 'edit_rpg_characters',
        'edit_others_posts' => 'edit_others_rpg_characters',
        'delete_posts' => 'delete_rpg_characters',
        'delete_others_posts' => 'delete_others_rpg_characters',
        'read_private_posts' => 'read_private_rpg_characters',
        'edit_post' => 'edit_rpg_character',
        'delete_post' => 'delete_rpg_character',
        'read_post' => 'read_rpg_character',
    ],
]);
```

## REST API Design

### Character Endpoints

```php
// Get character data
GET /wp-json/rpg-suite/v1/characters/{id}

// Update character field
PATCH /wp-json/rpg-suite/v1/characters/{id}

// Switch active character
POST /wp-json/rpg-suite/v1/characters/switch

// Get user's characters
GET /wp-json/rpg-suite/v1/users/{id}/characters

// Create new character
POST /wp-json/rpg-suite/v1/characters
```

### API Response Format

```json
{
    "id": 123,
    "name": "Character Name",
    "class": "Aeronaut",
    "attributes": {
        "fortitude": "3d7+1",
        "precision": "2d7",
        "intellect": "4d7",
        "charisma": "2d7+2"
    },
    "active": true,
    "meta": {
        "revision_id": "uuid-here",
        "last_modified": "2025-05-17T12:00:00Z",
        "cache_version": 2
    },
    "owner": {
        "id": 1,
        "name": "Player Name",
        "avatar_url": "https://..."
    }
}
```

## React Character Sheet

### Component Architecture

```javascript
// Main character sheet component
const CharacterSheet = ({ characterId }) => {
    const { character, loading, error, updateCharacter } = useCharacter(characterId);
    const { isEditing, setIsEditing } = useState(false);
    
    if (loading) return <LoadingSpinner />;
    if (error) return <ErrorMessage error={error} />;
    
    return (
        <div className="rpg-character-sheet">
            <CharacterHeader character={character} onEdit={() => setIsEditing(true)} />
            <CharacterStats 
                attributes={character.attributes}
                isEditing={isEditing}
                onUpdate={updateCharacter}
            />
            <CharacterClass 
                class={character.class}
                isEditing={isEditing}
                onUpdate={updateCharacter}
            />
            <CharacterInventory items={character.inventory} />
            <CharacterActions 
                character={character}
                onSwitch={() => handleCharacterSwitch(character.id)}
            />
        </div>
    );
};
```

### Custom Hooks

```javascript
// Hook for character data management
const useCharacter = (characterId) => {
    const queryClient = useQueryClient();
    
    // Fetch character data
    const { data, isLoading, error } = useQuery(
        ['character', characterId],
        () => api.getCharacter(characterId),
        {
            staleTime: 5 * 60 * 1000,
            cacheTime: 10 * 60 * 1000,
        }
    );
    
    // Update character mutation
    const updateMutation = useMutation(
        (updates) => api.updateCharacter(characterId, updates),
        {
            onMutate: async (updates) => {
                await queryClient.cancelQueries(['character', characterId]);
                const previous = queryClient.getQueryData(['character', characterId]);
                
                // Optimistic update
                queryClient.setQueryData(['character', characterId], (old) => ({
                    ...old,
                    ...updates,
                    meta: {
                        ...old.meta,
                        revision_id: generateUUID(),
                        last_modified: new Date().toISOString(),
                    }
                }));
                
                return { previous };
            },
            onError: (err, updates, context) => {
                queryClient.setQueryData(['character', characterId], context.previous);
            },
            onSettled: () => {
                queryClient.invalidateQueries(['character', characterId]);
            }
        }
    );
    
    return {
        character: data,
        loading: isLoading,
        error,
        updateCharacter: updateMutation.mutate,
    };
};
```

## Steampunk d7 System

### Attributes
Core attributes for our unique d7 system:

- **Fortitude**: Physical strength and endurance
- **Precision**: Dexterity and hand-eye coordination
- **Intellect**: Intelligence and technical knowledge
- **Charisma**: Social ability and leadership

Each attribute uses a die code notation (e.g., "3d7+2").

### Character Classes

Four steampunk character classes:

1. **Aeronaut**: Airship pilots and navigators
2. **Mechwright**: Engineers who create mechanical devices
3. **Aethermancer**: Scientists who manipulate aether energy
4. **Diplomat**: Negotiators who navigate politics

### Die Roll Visualization

```javascript
// React component for die rolls
const DieRollVisualizer = ({ dieCode, onRoll }) => {
    const [rolling, setRolling] = useState(false);
    const [result, setResult] = useState(null);
    
    const handleRoll = async () => {
        setRolling(true);
        const rollResult = await api.rollDice(dieCode);
        
        // Animate dice
        setTimeout(() => {
            setResult(rollResult);
            setRolling(false);
        }, 1000);
    };
    
    return (
        <div className="die-roll-visualizer">
            <button onClick={handleRoll} disabled={rolling}>
                Roll {dieCode}
            </button>
            {rolling && <DiceAnimation />}
            {result && !rolling && (
                <div className="roll-result">
                    <span className="total">{result.total}</span>
                    <span className="breakdown">{result.breakdown}</span>
                </div>
            )}
        </div>
    );
};
```

## Caching Strategy

### Multi-Layer Cache Implementation

```php
class RPG_Character_Cache {
    private $cache_group = 'rpg_characters';
    
    public function get_character($character_id) {
        // Level 1: Object cache
        $character = wp_cache_get($character_id, $this->cache_group);
        
        if (false === $character) {
            // Level 2: Transient cache
            $character = get_transient('rpg_character_' . $character_id);
            
            if (false === $character) {
                // Level 3: Database query
                $character = $this->fetch_from_database($character_id);
                
                if ($character) {
                    // Store in both caches
                    $this->set_character_cache($character_id, $character);
                }
            }
        }
        
        return $character;
    }
    
    public function set_character_cache($character_id, $character_data) {
        // Add cache metadata
        $character_data['cache_time'] = time();
        $character_data['cache_version'] = get_option('rpg_suite_cache_version', 1);
        
        // Store in object cache
        wp_cache_set($character_id, $character_data, $this->cache_group, HOUR_IN_SECONDS);
        
        // Store in transient
        set_transient('rpg_character_' . $character_id, $character_data, HOUR_IN_SECONDS);
        
        // Update cache index
        $this->update_cache_index($character_id);
    }
    
    public function invalidate_character($character_id) {
        // Clear all cache layers
        wp_cache_delete($character_id, $this->cache_group);
        delete_transient('rpg_character_' . $character_id);
        
        // Increment revision
        update_post_meta($character_id, '_rpg_revision_id', wp_generate_uuid4());
        
        // Notify React frontend
        do_action('rpg_character_cache_invalidated', $character_id);
    }
}
```

## BuddyPress Integration

### React Component in BuddyPress Profile

```php
// Add React mount point to BuddyPress profile
add_action('bp_before_member_body', function() {
    if (!bp_is_user()) return;
    
    $user_id = bp_displayed_user_id();
    $character = rpg_get_active_character($user_id);
    
    if ($character) {
        echo '<div id="rpg-character-sheet-mount" 
                   data-character-id="' . esc_attr($character->ID) . '"
                   data-user-id="' . esc_attr($user_id) . '"
                   data-can-edit="' . esc_attr(current_user_can('edit_rpg_character', $character->ID) ? 'true' : 'false') . '">
              </div>';
        
        // Enqueue React app
        wp_enqueue_script('rpg-suite-character-sheet');
        wp_enqueue_style('rpg-suite-character-sheet');
        
        // Pass data to React
        wp_localize_script('rpg-suite-character-sheet', 'rpgSuiteData', [
            'api' => [
                'root' => rest_url('rpg-suite/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
            ],
            'character' => [
                'id' => $character->ID,
                'canEdit' => current_user_can('edit_rpg_character', $character->ID),
            ],
            'user' => [
                'id' => get_current_user_id(),
                'isOwner' => get_current_user_id() === $user_id,
            ],
        ]);
    }
}, 20);
```

### Character Switcher Widget

```javascript
const CharacterSwitcher = () => {
    const { userId } = useContext(UserContext);
    const { characters, activeCharacterId, switchCharacter } = useCharacters(userId);
    const [switching, setSwitching] = useState(false);
    
    const handleSwitch = async (characterId) => {
        setSwitching(true);
        try {
            await switchCharacter(characterId);
            // Reload the page or update React state
            window.location.reload();
        } catch (error) {
            console.error('Failed to switch character:', error);
            alert('Failed to switch character. Please try again.');
        } finally {
            setSwitching(false);
        }
    };
    
    return (
        <div className="rpg-character-switcher">
            <h3>My Characters</h3>
            <div className="character-list">
                {characters.map(char => (
                    <div 
                        key={char.id}
                        className={`character-item ${char.id === activeCharacterId ? 'active' : ''}`}
                    >
                        <img src={char.avatar} alt={char.name} />
                        <div className="character-info">
                            <h4>{char.name}</h4>
                            <p>{char.class}</p>
                        </div>
                        {char.id !== activeCharacterId && (
                            <button 
                                onClick={() => handleSwitch(char.id)}
                                disabled={switching}
                            >
                                Switch
                            </button>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
};
```

## Performance Optimization

### React Performance

1. **Component Memoization**:
```javascript
const CharacterStats = React.memo(({ attributes, isEditing, onUpdate }) => {
    // Component implementation
}, (prevProps, nextProps) => {
    return prevProps.attributes === nextProps.attributes && 
           prevProps.isEditing === nextProps.isEditing;
});
```

2. **Lazy Loading**:
```javascript
const CharacterInventory = React.lazy(() => import('./CharacterInventory'));
const CharacterAchievements = React.lazy(() => import('./CharacterAchievements'));
```

3. **Debounced Updates**:
```javascript
const useDebouncedUpdate = (updateFn, delay = 500) => {
    const debouncedUpdate = useMemo(
        () => debounce(updateFn, delay),
        [updateFn, delay]
    );
    
    useEffect(() => {
        return () => debouncedUpdate.cancel();
    }, [debouncedUpdate]);
    
    return debouncedUpdate;
};
```

### API Performance

1. **Batch Updates**:
```php
register_rest_route('rpg-suite/v1', '/characters/(?P<id>\d+)/batch', [
    'methods' => 'PATCH',
    'callback' => 'rpg_batch_update_character',
    'permission_callback' => 'rpg_can_edit_character',
    'args' => [
        'updates' => [
            'type' => 'array',
            'required' => true,
            'items' => [
                'type' => 'object',
                'properties' => [
                    'field' => ['type' => 'string'],
                    'value' => ['type' => ['string', 'number', 'boolean', 'array']],
                ],
            ],
        ],
    ],
]);
```

2. **Field Selection**:
```php
// API supports field selection
GET /wp-json/rpg-suite/v1/characters/123?fields=id,name,class,attributes
```

## Testing Strategy

### React Component Tests

```javascript
describe('CharacterSheet', () => {
    it('displays character information', async () => {
        const mockCharacter = {
            id: 1,
            name: 'Test Character',
            class: 'Aeronaut',
            attributes: {
                fortitude: '3d7',
                precision: '2d7+1',
                intellect: '4d7',
                charisma: '2d7',
            },
        };
        
        render(<CharacterSheet characterId={1} />);
        
        await waitFor(() => {
            expect(screen.getByText('Test Character')).toBeInTheDocument();
            expect(screen.getByText('Aeronaut')).toBeInTheDocument();
        });
    });
    
    it('handles character updates', async () => {
        const user = userEvent.setup();
        render(<CharacterSheet characterId={1} />);
        
        const editButton = await screen.findByText('Edit');
        await user.click(editButton);
        
        const nameInput = screen.getByLabelText('Character Name');
        await user.clear(nameInput);
        await user.type(nameInput, 'Updated Name');
        
        const saveButton = screen.getByText('Save');
        await user.click(saveButton);
        
        await waitFor(() => {
            expect(screen.getByText('Updated Name')).toBeInTheDocument();
        });
    });
});
```

### API Tests

```php
class Test_Character_REST_API extends WP_UnitTestCase {
    public function test_update_character_field() {
        $user_id = $this->factory->user->create();
        $character_id = $this->create_test_character($user_id);
        
        wp_set_current_user($user_id);
        
        $request = new WP_REST_Request('PATCH', '/rpg-suite/v1/characters/' . $character_id);
        $request->set_body_params([
            'attributes' => [
                'fortitude' => '4d7+1',
            ],
        ]);
        
        $response = rest_do_request($request);
        $data = $response->get_data();
        
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('4d7+1', $data['attributes']['fortitude']);
        $this->assertNotEmpty($data['meta']['revision_id']);
    }
}
```

## Future Enhancements

1. **WebSocket Support**: Real-time character updates across multiple sessions
2. **Offline Mode**: Service worker for offline character viewing
3. **Mobile App**: React Native companion app
4. **Advanced Dice System**: Visual dice rolling with physics
5. **Character Sheets PDF**: Export character sheets as PDFs
6. **Campaign Integration**: Link characters to campaign sessions