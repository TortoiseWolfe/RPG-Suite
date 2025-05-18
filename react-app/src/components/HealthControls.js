import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Button } from '@wordpress/components';

function HealthControls({ character, onHealthChange }) {
  const [isLoading, setIsLoading] = useState(false);
  const [damageAmount, setDamageAmount] = useState(5);
  const [healAmount, setHealAmount] = useState(5);
  
  const handleDamage = async () => {
    setIsLoading(true);
    try {
      const response = await apiFetch({
        path: `/rpg-suite/v1/characters/${character.id}/damage`,
        method: 'POST',
        data: { amount: damageAmount }
      });
      
      if (response.success && response.health) {
        onHealthChange(response.health);
      }
    } catch (error) {
      console.error('Error applying damage:', error);
      alert('Failed to apply damage. Please try again.');
    }
    setIsLoading(false);
  };
  
  const handleHeal = async () => {
    setIsLoading(true);
    try {
      const response = await apiFetch({
        path: `/rpg-suite/v1/characters/${character.id}/heal`,
        method: 'POST',
        data: { amount: healAmount }
      });
      
      if (response.success && response.health) {
        onHealthChange(response.health);
      }
    } catch (error) {
      console.error('Error applying healing:', error);
      alert('Failed to apply healing. Please try again.');
    }
    setIsLoading(false);
  };
  
  const rollDamage = async () => {
    setIsLoading(true);
    try {
      const rollResponse = await apiFetch({
        path: `/rpg-suite/v1/characters/${character.id}/roll`,
        method: 'POST',
        data: { type: 'damage' }
      });
      
      if (rollResponse.success && rollResponse.result) {
        const damageAmount = rollResponse.result;
        
        // Apply the rolled damage
        const damageResponse = await apiFetch({
          path: `/rpg-suite/v1/characters/${character.id}/damage`,
          method: 'POST',
          data: { amount: damageAmount }
        });
        
        if (damageResponse.success && damageResponse.health) {
          onHealthChange(damageResponse.health);
          alert(`Rolled ${damageAmount} damage!`);
        }
      }
    } catch (error) {
      console.error('Error rolling damage:', error);
      alert('Failed to roll damage. Please try again.');
    }
    setIsLoading(false);
  };
  
  const rollHeal = async () => {
    setIsLoading(true);
    try {
      const rollResponse = await apiFetch({
        path: `/rpg-suite/v1/characters/${character.id}/roll`,
        method: 'POST',
        data: { type: 'heal' }
      });
      
      if (rollResponse.success && rollResponse.result) {
        const healAmount = rollResponse.result;
        
        // Apply the rolled healing
        const healResponse = await apiFetch({
          path: `/rpg-suite/v1/characters/${character.id}/heal`,
          method: 'POST',
          data: { amount: healAmount }
        });
        
        if (healResponse.success && healResponse.health) {
          onHealthChange(healResponse.health);
          alert(`Rolled ${healAmount} healing!`);
        }
      }
    } catch (error) {
      console.error('Error rolling healing:', error);
      alert('Failed to roll healing. Please try again.');
    }
    setIsLoading(false);
  };
  
  return (
    <div className="rpg-health-controls">
      <h4>Health Controls</h4>
      
      <div className="rpg-damage-controls">
        <label>
          Damage: 
          <input 
            type="number" 
            value={damageAmount} 
            onChange={(e) => setDamageAmount(parseInt(e.target.value) || 0)}
            min="1"
            max="100"
            disabled={isLoading}
          />
        </label>
        <Button 
          isPrimary
          onClick={handleDamage}
          disabled={isLoading || damageAmount <= 0}
        >
          Apply Damage
        </Button>
        <Button 
          isSecondary
          onClick={rollDamage}
          disabled={isLoading}
        >
          Roll Damage (d7 + Fortitude)
        </Button>
      </div>
      
      <div className="rpg-heal-controls">
        <label>
          Heal: 
          <input 
            type="number" 
            value={healAmount} 
            onChange={(e) => setHealAmount(parseInt(e.target.value) || 0)}
            min="1"
            max="100"
            disabled={isLoading}
          />
        </label>
        <Button 
          isPrimary
          onClick={handleHeal}
          disabled={isLoading || healAmount <= 0}
        >
          Apply Healing
        </Button>
        <Button 
          isSecondary
          onClick={rollHeal}
          disabled={isLoading}
        >
          Roll Healing (d7 + Intellect)
        </Button>
      </div>
      
      {isLoading && <p>Processing...</p>}
    </div>
  );
}

export default HealthControls;