<?php
/**
 * Dice Roller for d7 System
 *
 * Handles all dice rolling mechanics for the RPG Suite
 *
 * @package RPG_Suite
 * @subpackage Core
 */

/**
 * Class RPG_Suite_Dice_Roller
 *
 * Implements d7 dice rolling with attribute modifiers
 */
class RPG_Suite_Dice_Roller {

	/**
	 * Roll a single d7
	 *
	 * @return int The result of the roll (1-7).
	 */
	public function roll_d7() {
		// Using mt_rand for better randomness
		return mt_rand(1, 7);
	}

	/**
	 * Roll multiple d7 dice
	 *
	 * @param int $count Number of dice to roll.
	 * @return array Array of individual roll results.
	 */
	public function roll_multiple_d7( $count = 1 ) {
		$results = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$results[] = $this->roll_d7();
		}
		return $results;
	}

	/**
	 * Roll d7 with attribute modifier
	 *
	 * @param int $character_id Character post ID.
	 * @param string $attribute The attribute to use as modifier.
	 * @return array Result with roll details.
	 */
	public function roll_with_attribute( $character_id, $attribute ) {
		// Validate attribute name
		$valid_attributes = array( 'fortitude', 'precision', 'intellect', 'charisma' );
		if ( ! in_array( $attribute, $valid_attributes ) ) {
			return array(
				'error' => 'Invalid attribute',
			);
		}

		// Get attribute value
		$attribute_value = intval( get_post_meta( $character_id, '_rpg_' . $attribute, true ) );
		
		// Roll the dice
		$dice_roll = $this->roll_d7();
		
		// Calculate total
		$total = $dice_roll + $attribute_value;
		
		// Log the roll
		rpg_suite_log("Dice roll - Character: $character_id, Dice: $dice_roll, Attribute: $attribute = $attribute_value, Total: $total", 'DICE');
		
		return array(
			'dice_roll' => $dice_roll,
			'attribute' => $attribute,
			'attribute_value' => $attribute_value,
			'total' => $total,
			'success' => $total >= 8, // Success on 8 or higher
		);
	}

	/**
	 * Roll for damage based on character strength (fortitude)
	 *
	 * @param int $character_id Character post ID.
	 * @return int Damage amount.
	 */
	public function roll_damage( $character_id ) {
		$fortitude = intval( get_post_meta( $character_id, '_rpg_fortitude', true ) );
		$dice_roll = $this->roll_d7();
		
		// Damage formula: dice roll + fortitude
		$damage = $dice_roll + $fortitude;
		
		rpg_suite_log("Damage roll - Character: $character_id, Dice: $dice_roll, Fortitude: $fortitude, Total Damage: $damage", 'DICE');
		
		return $damage;
	}

	/**
	 * Roll for healing based on character intellect
	 *
	 * @param int $character_id Character post ID.
	 * @return int Healing amount.
	 */
	public function roll_healing( $character_id ) {
		$intellect = intval( get_post_meta( $character_id, '_rpg_intellect', true ) );
		$dice_roll = $this->roll_d7();
		
		// Healing formula: dice roll + intellect
		$healing = $dice_roll + $intellect;
		
		rpg_suite_log("Healing roll - Character: $character_id, Dice: $dice_roll, Intellect: $intellect, Total Healing: $healing", 'DICE');
		
		return $healing;
	}

	/**
	 * Perform a skill check
	 *
	 * @param int $character_id Character post ID.
	 * @param string $skill The skill/attribute to check.
	 * @param int $difficulty The difficulty class (DC).
	 * @return array Result of the skill check.
	 */
	public function skill_check( $character_id, $skill, $difficulty ) {
		$roll_result = $this->roll_with_attribute( $character_id, $skill );
		
		if ( isset( $roll_result['error'] ) ) {
			return $roll_result;
		}
		
		$success = $roll_result['total'] >= $difficulty;
		
		return array(
			'roll' => $roll_result,
			'difficulty' => $difficulty,
			'success' => $success,
			'margin' => $roll_result['total'] - $difficulty,
		);
	}

	/**
	 * Roll for initiative
	 *
	 * @param int $character_id Character post ID.
	 * @return array Initiative result.
	 */
	public function roll_initiative( $character_id ) {
		// Initiative uses precision
		return $this->roll_with_attribute( $character_id, 'precision' );
	}
}