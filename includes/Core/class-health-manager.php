<?php
/**
 * Health Manager
 *
 * Manages character health with percentage-based system
 *
 * @package RPG_Suite
 * @subpackage Core
 */

/**
 * Class RPG_Suite_Health_Manager
 *
 * Handles health tracking where everyone has 100 max HP
 */
class RPG_Suite_Health_Manager {

	/**
	 * Maximum health points (constant for all characters)
	 *
	 * @var int
	 */
	const MAX_HP = 100;

	/**
	 * Get character's current health
	 *
	 * @param int $character_id Character post ID.
	 * @return int Current health value (0-100).
	 */
	public function get_current_health( $character_id ) {
		$current_hp = get_post_meta( $character_id, '_rpg_current_hp', true );
		
		// If no health set, default to max
		if ( $current_hp === '' ) {
			$current_hp = self::MAX_HP;
			update_post_meta( $character_id, '_rpg_current_hp', $current_hp );
		}
		
		// Ensure health is within valid range
		return max( 0, min( self::MAX_HP, intval( $current_hp ) ) );
	}

	/**
	 * Get character's health percentage
	 *
	 * @param int $character_id Character post ID.
	 * @return float Health percentage (0-100).
	 */
	public function get_health_percentage( $character_id ) {
		$current_hp = $this->get_current_health( $character_id );
		return ( $current_hp / self::MAX_HP ) * 100;
	}

	/**
	 * Set character's current health
	 *
	 * @param int $character_id Character post ID.
	 * @param int $health New health value.
	 * @return bool True on success, false on failure.
	 */
	public function set_current_health( $character_id, $health ) {
		// Validate character exists
		if ( get_post_type( $character_id ) !== 'rpg_character' ) {
			return false;
		}
		
		// Ensure health is within valid range
		$health = max( 0, min( self::MAX_HP, intval( $health ) ) );
		
		// Store the value
		update_post_meta( $character_id, '_rpg_current_hp', $health );
		
		// Trigger an event for health change
		do_action( 'rpg_suite_health_changed', $character_id, $health );
		
		return true;
	}

	/**
	 * Damage a character
	 *
	 * @param int $character_id Character post ID.
	 * @param int $damage Amount of damage to deal.
	 * @return int New health value.
	 */
	public function damage_character( $character_id, $damage ) {
		$current_hp = $this->get_current_health( $character_id );
		$new_hp = $current_hp - abs( $damage );
		
		$this->set_current_health( $character_id, $new_hp );
		
		return $this->get_current_health( $character_id );
	}

	/**
	 * Heal a character
	 *
	 * @param int $character_id Character post ID.
	 * @param int $healing Amount of healing to apply.
	 * @return int New health value.
	 */
	public function heal_character( $character_id, $healing ) {
		$current_hp = $this->get_current_health( $character_id );
		$new_hp = $current_hp + abs( $healing );
		
		$this->set_current_health( $character_id, $new_hp );
		
		return $this->get_current_health( $character_id );
	}

	/**
	 * Check if character is unconscious
	 *
	 * @param int $character_id Character post ID.
	 * @return bool True if character has 0 HP.
	 */
	public function is_unconscious( $character_id ) {
		return $this->get_current_health( $character_id ) === 0;
	}

	/**
	 * Check if character is injured
	 *
	 * @param int $character_id Character post ID.
	 * @return bool True if character has less than max HP.
	 */
	public function is_injured( $character_id ) {
		return $this->get_current_health( $character_id ) < self::MAX_HP;
	}

	/**
	 * Reset character to full health
	 *
	 * @param int $character_id Character post ID.
	 * @return bool True on success.
	 */
	public function reset_health( $character_id ) {
		return $this->set_current_health( $character_id, self::MAX_HP );
	}

	/**
	 * Initialize health for new characters
	 *
	 * @param int $post_id Post ID.
	 */
	public function init_character_health( $post_id ) {
		// Check if this is a character post type
		if ( get_post_type( $post_id ) !== 'rpg_character' ) {
			return;
		}

		// Check if health is already set
		$current_hp = get_post_meta( $post_id, '_rpg_current_hp', true );
		
		// If not set, initialize to max health
		if ( $current_hp === '' ) {
			update_post_meta( $post_id, '_rpg_current_hp', self::MAX_HP );
		}
	}

	/**
	 * Get health status description
	 *
	 * @param int $character_id Character post ID.
	 * @return string Health status description.
	 */
	public function get_health_status( $character_id ) {
		$percentage = $this->get_health_percentage( $character_id );
		
		if ( $percentage >= 100 ) {
			return 'Healthy';
		} elseif ( $percentage >= 75 ) {
			return 'Slightly Injured';
		} elseif ( $percentage >= 50 ) {
			return 'Injured';
		} elseif ( $percentage >= 25 ) {
			return 'Badly Injured';
		} elseif ( $percentage > 0 ) {
			return 'Critical';
		} else {
			return 'Unconscious';
		}
	}
}