import { render } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import CharacterDisplay from './components/CharacterDisplay';
import CharacterSwitcher from './components/CharacterSwitcher';

// Configure API fetch with nonce
if (window.rpgSuiteData?.api?.nonce) {
  apiFetch.use(apiFetch.createNonceMiddleware(window.rpgSuiteData.api.nonce));
}

// Function to mount components
function mountRPGSuite() {
  // Mount character display
  const characterDisplay = document.getElementById('rpg-suite-character');
  if (characterDisplay) {
    render(
      <CharacterDisplay />,
      characterDisplay
    );
  }

  // Mount character switcher
  const characterSwitcher = document.getElementById('rpg-suite-character-switcher');
  if (characterSwitcher) {
    render(
      <CharacterSwitcher />,
      characterSwitcher
    );
  }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mountRPGSuite);
} else {
  mountRPGSuite();
}