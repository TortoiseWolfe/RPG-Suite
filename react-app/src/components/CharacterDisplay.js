import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import HealthControls from './HealthControls';

function CharacterDisplay() {
  const [characters, setCharacters] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [activeCharacter, setActiveCharacter] = useState(null);
  
  // Get user ID from window or data attribute
  const userId = window.rpgSuiteData?.currentUser || 
                 document.getElementById('rpg-suite-character')?.dataset.userId;
  
  useEffect(() => {
    // Show React container and hide PHP fallback
    const container = document.getElementById('rpg-suite-character');
    const phpFallback = document.querySelector('.rpg-php-fallback');
    
    if (container) container.style.display = 'block';
    if (phpFallback) phpFallback.style.display = 'none';
    
    if (!userId) return;
    
    setIsLoading(true);
    apiFetch({ path: `/rpg-suite/v1/users/${userId}/characters` })
      .then(data => {
        setCharacters(data);
        const active = data?.find(char => char.active);
        setActiveCharacter(active);
        setIsLoading(false);
      })
      .catch(err => {
        setError(err);
        setIsLoading(false);
      });
  }, [userId]);
  
  const handleHealthChange = (newHealth) => {
    if (activeCharacter) {
      setActiveCharacter({
        ...activeCharacter,
        health: newHealth
      });
    }
  };
  
  if (isLoading) return <div>Loading character...</div>;
  if (error) return <div>Error loading character: {error.message}</div>;
  
  if (!activeCharacter) {
    return <div>No active character</div>;
  }
  
  return (
    <div className="rpg-character-display">
      <h3>{activeCharacter.title}</h3>
      {activeCharacter.class && (
        <p><strong>Class:</strong> {activeCharacter.class}</p>
      )}
      <div className="rpg-attributes">
        <p><strong>Fortitude:</strong> {activeCharacter.attributes.fortitude}</p>
        <p><strong>Precision:</strong> {activeCharacter.attributes.precision}</p>
        <p><strong>Intellect:</strong> {activeCharacter.attributes.intellect}</p>
        <p><strong>Charisma:</strong> {activeCharacter.attributes.charisma}</p>
      </div>
      {activeCharacter.health && (
        <div className="rpg-health">
          <p><strong>Health:</strong> {activeCharacter.health.current}/{activeCharacter.health.max} ({activeCharacter.health.percentage}%)</p>
          <p><strong>Status:</strong> {activeCharacter.health.status}</p>
          <div className="rpg-health-bar" style={{
            width: '100%',
            height: '20px',
            backgroundColor: '#ccc',
            borderRadius: '10px',
            overflow: 'hidden',
            marginTop: '5px'
          }}>
            <div style={{
              width: `${activeCharacter.health.percentage}%`,
              height: '100%',
              backgroundColor: activeCharacter.health.percentage > 50 ? '#4caf50' : 
                              activeCharacter.health.percentage > 25 ? '#ffeb3b' : '#f44336',
              transition: 'width 0.3s ease'
            }}></div>
          </div>
        </div>
      )}
      
      {/* Only show health controls for the character owner */}
      {userId == window.rpgSuiteData?.currentUser && (
        <HealthControls 
          character={activeCharacter} 
          onHealthChange={handleHealthChange}
        />
      )}
    </div>
  );
}

export default CharacterDisplay;