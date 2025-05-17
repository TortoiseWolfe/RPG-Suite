import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

function CharacterSwitcher() {
  const [characters, setCharacters] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [switching, setSwitching] = useState(false);
  const userId = window.rpgSuiteData?.currentUser;
  
  useEffect(() => {
    if (!userId) return;
    
    setIsLoading(true);
    apiFetch({ path: `/rpg-suite/v1/users/${userId}/characters` })
      .then(data => {
        setCharacters(data);
        setIsLoading(false);
      })
      .catch(err => {
        setError(err);
        setIsLoading(false);
      });
  }, [userId]);
  
  const handleSwitch = async (characterId) => {
    setSwitching(true);
    try {
      await apiFetch({
        path: '/rpg-suite/v1/characters/switch',
        method: 'POST',
        data: { character_id: characterId }
      });
      // Reload page to show updated character
      window.location.reload();
    } catch (error) {
      console.error('Error switching character:', error);
      alert('Failed to switch character. Please try again.');
    }
    setSwitching(false);
  };
  
  if (isLoading) return <div>Loading characters...</div>;
  if (error) return <div>Error loading characters: {error.message}</div>;
  if (!characters || characters.length === 0) return <div>No characters found</div>;
  
  return (
    <div className="rpg-character-switcher">
      <h4>Switch Character</h4>
      <ul className="rpg-character-list">
        {characters.map(character => (
          <li 
            key={character.id}
            className={character.active ? 'active' : ''}
            onClick={() => !character.active && !switching && handleSwitch(character.id)}
            style={{ 
              cursor: character.active || switching ? 'default' : 'pointer',
              opacity: switching ? 0.7 : 1
            }}
          >
            <strong>{character.title}</strong>
            {character.class && <span> - {character.class}</span>}
            {character.active && <span> (Active)</span>}
          </li>
        ))}
      </ul>
      {switching && <p>Switching character...</p>}
    </div>
  );
}

export default CharacterSwitcher;