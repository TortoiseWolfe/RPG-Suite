# React Architecture for RPG-Suite

**Author:** TurtleWolfe
**Repository:** https://github.com/TortoiseWolfe/RPG-Suite

## Overview

The React architecture for RPG-Suite provides a modern, responsive frontend for character sheets while maintaining full integration with WordPress and BuddyPress. This document outlines the component structure, state management, and integration patterns.

## Component Hierarchy

```
App.js
├── providers/
│   ├── UserProvider.js          // WordPress user data, not auth
│   ├── CharacterProvider.js
│   └── ThemeProvider.js
├── components/
│   ├── CharacterSheet/
│   │   ├── CharacterSheet.js
│   │   ├── CharacterHeader.js
│   │   ├── CharacterStats.js
│   │   ├── CharacterClass.js
│   │   ├── CharacterInventory.js
│   │   └── CharacterActions.js
│   ├── CharacterSwitcher/
│   │   ├── CharacterSwitcher.js
│   │   ├── CharacterCard.js
│   │   └── SwitchButton.js
│   ├── DiceRoller/
│   │   ├── DiceRoller.js
│   │   ├── DiceVisualizer.js
│   │   └── RollHistory.js
│   └── common/
│       ├── LoadingSpinner.js
│       ├── ErrorBoundary.js
│       └── Toast.js
├── hooks/
│   ├── useCharacter.js
│   ├── useCharacters.js
│   ├── useCurrentUser.js      // Gets WP user data from window object
│   ├── useCache.js
│   └── useRealTimeUpdates.js
├── api/
│   ├── client.js
│   ├── characters.js
│   └── dice.js               // No auth.js - WP handles auth
├── store/
│   ├── store.js
│   ├── characterSlice.js
│   ├── userSlice.js
│   └── uiSlice.js
└── utils/
    ├── constants.js
    ├── helpers.js
    └── validators.js
```

## State Management

### Redux Store Structure

```javascript
{
  characters: {
    byId: {
      '123': {
        id: '123',
        name: 'Character Name',
        class: 'Aeronaut',
        attributes: {...},
        isActive: true,
        meta: {
          revision_id: 'uuid',
          last_modified: '2025-05-17T12:00:00Z'
        }
      }
    },
    allIds: ['123', '456'],
    activeCharacterId: '123',
    loading: false,
    error: null
  },
  user: {
    currentUser: {
      id: 1,
      name: 'Player Name',
      capabilities: ['edit_rpg_character']
    }
    // No isAuthenticated - WordPress handles session
  },
  ui: {
    isEditing: false,
    isSwitching: false,
    notifications: []
  }
}
```

### Context API Usage

```javascript
// User Context - WordPress user data passed via localized script
const UserContext = React.createContext();

export const UserProvider = ({ children }) => {
  // User data is provided by WordPress via wp_localize_script
  const userData = window.rpgSuiteData.user || {
    id: 0,
    name: 'Guest',
    capabilities: []
  };
  
  return (
    <UserContext.Provider value={userData}>
      {children}
    </UserContext.Provider>
  );
};

// Character Context for component-level state
const CharacterContext = React.createContext();

export const CharacterProvider = ({ children }) => {
  const [selectedCharacter, setSelectedCharacter] = useState(null);
  const [isEditing, setIsEditing] = useState(false);
  
  return (
    <CharacterContext.Provider value={{
      selectedCharacter,
      setSelectedCharacter,
      isEditing,
      setIsEditing
    }}>
      {children}
    </CharacterContext.Provider>
  );
};
```

## API Integration

### REST Client Configuration

```javascript
// api/client.js
import axios from 'axios';

const client = axios.create({
  baseURL: window.rpgSuiteData.api.root,
  headers: {
    'X-WP-Nonce': window.rpgSuiteData.api.nonce,
    'Content-Type': 'application/json',
  },
});

// WordPress nonce is already included in headers above
// No additional auth needed - WordPress session handles it

// Response interceptor for caching
client.interceptors.response.use((response) => {
  const cacheVersion = response.headers['x-rpg-cache-version'];
  if (cacheVersion) {
    localStorage.setItem('rpg_cache_version', cacheVersion);
  }
  return response;
});

export default client;
```

### Character API Service

```javascript
// api/characters.js
import client from './client';

export const characterApi = {
  getCharacter: async (id) => {
    const response = await client.get(`/characters/${id}`);
    return response.data;
  },
  
  updateCharacter: async (id, updates) => {
    const response = await client.patch(`/characters/${id}`, updates);
    return response.data;
  },
  
  switchCharacter: async (characterId) => {
    const response = await client.post('/characters/switch', { 
      character_id: characterId 
    });
    return response.data;
  },
  
  getUserCharacters: async (userId) => {
    const response = await client.get(`/users/${userId}/characters`);
    return response.data;
  }
};
```

## Custom Hooks

### useCharacter Hook

```javascript
// hooks/useCharacter.js
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { characterApi } from '../api/characters';

export const useCharacter = (characterId) => {
  const queryClient = useQueryClient();
  
  const query = useQuery(
    ['character', characterId],
    () => characterApi.getCharacter(characterId),
    {
      staleTime: 5 * 60 * 1000, // 5 minutes
      cacheTime: 10 * 60 * 1000, // 10 minutes
      enabled: !!characterId,
    }
  );
  
  const updateMutation = useMutation(
    (updates) => characterApi.updateCharacter(characterId, updates),
    {
      onMutate: async (updates) => {
        // Cancel any outgoing refetches
        await queryClient.cancelQueries(['character', characterId]);
        
        // Snapshot the previous value
        const previousCharacter = queryClient.getQueryData(['character', characterId]);
        
        // Optimistically update
        queryClient.setQueryData(['character', characterId], (old) => ({
          ...old,
          ...updates,
          meta: {
            ...old.meta,
            revision_id: generateUUID(),
            last_modified: new Date().toISOString(),
          }
        }));
        
        // Return context with snapshot
        return { previousCharacter };
      },
      onError: (err, updates, context) => {
        // Rollback on error
        queryClient.setQueryData(
          ['character', characterId],
          context.previousCharacter
        );
      },
      onSettled: () => {
        // Always refetch after error or success
        queryClient.invalidateQueries(['character', characterId]);
      }
    }
  );
  
  return {
    character: query.data,
    loading: query.isLoading,
    error: query.error,
    updateCharacter: updateMutation.mutate,
    isUpdating: updateMutation.isLoading,
  };
};
```

### useRealTimeUpdates Hook

```javascript
// hooks/useRealTimeUpdates.js
import { useEffect } from 'react';
import { useQueryClient } from 'react-query';

export const useRealTimeUpdates = (characterId) => {
  const queryClient = useQueryClient();
  
  useEffect(() => {
    // Listen for cache invalidation events from WordPress
    const handleInvalidation = (event) => {
      if (event.detail.characterId === characterId) {
        queryClient.invalidateQueries(['character', characterId]);
      }
    };
    
    window.addEventListener('rpg-character-updated', handleInvalidation);
    
    // WebSocket connection for future implementation
    // const ws = new WebSocket(wsUrl);
    // ws.on('character:update', handleUpdate);
    
    return () => {
      window.removeEventListener('rpg-character-updated', handleInvalidation);
      // ws.close();
    };
  }, [characterId, queryClient]);
};
```

## BuddyPress Integration

### Mount Point Strategy

```javascript
// integration/buddypress.js
export const mountCharacterSheet = () => {
  const mountPoint = document.getElementById('rpg-character-sheet-mount');
  
  if (mountPoint) {
    const { characterId, userId, canEdit } = mountPoint.dataset;
    
    const root = createRoot(mountPoint);
    root.render(
      <Provider store={store}>
        <QueryClientProvider client={queryClient}>
          <App 
            characterId={characterId}
            userId={userId}
            canEdit={canEdit === 'true'}
          />
        </QueryClientProvider>
      </Provider>
    );
  }
};

// Auto-mount on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mountCharacterSheet);
} else {
  mountCharacterSheet();
}
```

### WordPress Data Localization

```php
// PHP side - passing user data to React
wp_localize_script('rpg-suite-character-sheet', 'rpgSuiteData', [
    'api' => [
        'root' => rest_url('rpg-suite/v1/'),
        'nonce' => wp_create_nonce('wp_rest'),
    ],
    'user' => [
        'id' => get_current_user_id(),
        'name' => wp_get_current_user()->display_name,
        'capabilities' => array_keys(wp_get_current_user()->allcaps),
        'avatar' => get_avatar_url(get_current_user_id()),
    ],
    'character' => [
        'id' => $character->ID,
        'canEdit' => current_user_can('edit_rpg_character', $character->ID),
    ],
]);
```

### Theme Compatibility

```javascript
// components/CharacterSheet/CharacterSheet.js
import styled from 'styled-components';

const SheetContainer = styled.div`
  /* Inherit BuddyX theme variables */
  color: var(--buddyx-text-color, #333);
  background: var(--buddyx-bg-color, #fff);
  font-family: var(--buddyx-font-family, inherit);
  
  /* Responsive design */
  @media (max-width: 768px) {
    padding: 1rem;
  }
  
  /* Dark mode support */
  @media (prefers-color-scheme: dark) {
    background: var(--buddyx-dark-bg, #1a1a1a);
    color: var(--buddyx-dark-text, #f0f0f0);
  }
`;
```

## Performance Optimization

### Code Splitting

```javascript
// Lazy load heavy components
const CharacterInventory = React.lazy(() => 
  import(/* webpackChunkName: "inventory" */ './CharacterInventory')
);

const DiceRoller = React.lazy(() => 
  import(/* webpackChunkName: "dice" */ '../DiceRoller')
);

// Usage with Suspense
<Suspense fallback={<LoadingSpinner />}>
  <CharacterInventory items={character.inventory} />
</Suspense>
```

### Memoization

```javascript
// Memoize expensive computations
const CharacterStats = React.memo(({ attributes, bonuses }) => {
  const calculatedStats = useMemo(() => {
    return Object.entries(attributes).reduce((acc, [key, value]) => {
      acc[key] = calculateStatWithBonuses(value, bonuses[key]);
      return acc;
    }, {});
  }, [attributes, bonuses]);
  
  return (
    <StatsGrid>
      {Object.entries(calculatedStats).map(([stat, value]) => (
        <StatDisplay key={stat} stat={stat} value={value} />
      ))}
    </StatsGrid>
  );
});
```

### Debounced Updates

```javascript
// Debounce frequent updates
const useDebounceUpdate = (value, delay = 500) => {
  const [debouncedValue, setDebouncedValue] = useState(value);
  
  useEffect(() => {
    const handler = setTimeout(() => {
      setDebouncedValue(value);
    }, delay);
    
    return () => clearTimeout(handler);
  }, [value, delay]);
  
  return debouncedValue;
};

// Usage in component
const CharacterNameInput = ({ initialName, onUpdate }) => {
  const [name, setName] = useState(initialName);
  const debouncedName = useDebounceUpdate(name);
  
  useEffect(() => {
    if (debouncedName !== initialName) {
      onUpdate({ name: debouncedName });
    }
  }, [debouncedName]);
  
  return (
    <input
      type="text"
      value={name}
      onChange={(e) => setName(e.target.value)}
    />
  );
};
```

## Build Configuration

### Webpack Configuration

```javascript
// webpack.config.js
module.exports = {
  entry: './src/index.js',
  output: {
    path: path.resolve(__dirname, 'build'),
    filename: 'rpg-suite-react.[contenthash].js',
  },
  module: {
    rules: [
      {
        test: /\.(js|jsx)$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-react', '@babel/preset-env'],
            plugins: ['@babel/plugin-syntax-dynamic-import']
          }
        }
      },
      {
        test: /\.css$/,
        use: ['style-loader', 'css-loader', 'postcss-loader']
      }
    ]
  },
  optimization: {
    splitChunks: {
      chunks: 'all',
      cacheGroups: {
        vendor: {
          test: /[\\/]node_modules[\\/]/,
          name: 'vendors',
          priority: 10
        }
      }
    }
  },
  plugins: [
    new HtmlWebpackPlugin({
      template: './public/index.html'
    }),
    new MiniCssExtractPlugin({
      filename: 'rpg-suite-react.[contenthash].css'
    })
  ]
};
```

## Testing Strategy

### Component Testing

```javascript
// __tests__/CharacterSheet.test.js
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { CharacterSheet } from '../components/CharacterSheet';

describe('CharacterSheet', () => {
  it('displays character information', async () => {
    const mockCharacter = {
      id: 1,
      name: 'Test Character',
      class: 'Aeronaut',
      attributes: {
        fortitude: '3d7',
        precision: '2d7+1',
      }
    };
    
    render(
      <MockProviders>
        <CharacterSheet character={mockCharacter} />
      </MockProviders>
    );
    
    expect(screen.getByText('Test Character')).toBeInTheDocument();
    expect(screen.getByText('Aeronaut')).toBeInTheDocument();
  });
  
  it('handles character updates', async () => {
    const user = userEvent.setup();
    const onUpdate = jest.fn();
    
    render(
      <MockProviders>
        <CharacterSheet 
          character={mockCharacter}
          onUpdate={onUpdate}
          canEdit={true}
        />
      </MockProviders>
    );
    
    const editButton = screen.getByText('Edit');
    await user.click(editButton);
    
    const nameInput = screen.getByLabelText('Character Name');
    await user.clear(nameInput);
    await user.type(nameInput, 'New Name');
    
    const saveButton = screen.getByText('Save');
    await user.click(saveButton);
    
    await waitFor(() => {
      expect(onUpdate).toHaveBeenCalledWith(
        expect.objectContaining({ name: 'New Name' })
      );
    });
  });
});
```

## Security Considerations

### Authentication
- **WordPress handles all authentication** - React never manages login/logout
- User session is managed by WordPress cookies
- Nonce verification ensures requests come from authenticated users
- React receives user data via `wp_localize_script`

### XSS Prevention

```javascript
// Always sanitize user input
import DOMPurify from 'dompurify';

const CharacterBio = ({ bio }) => {
  const sanitizedBio = DOMPurify.sanitize(bio);
  
  return (
    <div 
      className="character-bio"
      dangerouslySetInnerHTML={{ __html: sanitizedBio }}
    />
  );
};
```

### Permission Checking

```javascript
// Use WordPress capabilities passed from server
const useCharacterUpdate = (characterId) => {
  const user = useContext(UserContext); // WP user data
  const canEdit = user?.capabilities?.includes('edit_rpg_character');
  
  const updateCharacter = useCallback(async (updates) => {
    if (!canEdit) {
      throw new Error('Insufficient permissions');
    }
    
    // WordPress REST API will also verify permissions server-side
    return await characterApi.updateCharacter(characterId, updates);
  }, [characterId, canEdit]);
  
  return { updateCharacter, canEdit };
};
```

## Future Enhancements

1. **WebSocket Integration**: Real-time updates across sessions
2. **Service Worker**: Offline capability and caching
3. **React Native**: Mobile app development
4. **GraphQL**: More efficient data fetching
5. **Internationalization**: Multi-language support
6. **Accessibility**: WCAG 2.1 AA compliance