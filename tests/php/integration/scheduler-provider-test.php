<?php
/**
 * Action Scheduler provider bootstrap contract coverage.
 */

if ( ! class_exists( 'WP_UnitTestCase' ) ) {
	return;
}

class TPW_Core_Scheduler_Provider_Test extends WP_UnitTestCase {
	public function test_registered_provider_remains_available_after_bootstrap() {
		$this->assertTrue( class_exists( 'TPW_Core_Scheduler' ) );
		$this->assertTrue( TPW_Core_Scheduler::init_if_needed() );
		$this->assertTrue( function_exists( 'as_schedule_single_action' ) );
		$this->assertTrue( function_exists( 'as_unschedule_all_actions' ) );
		$this->assertTrue( class_exists( 'ActionScheduler', false ) );
		$this->assertGreaterThan( 0, did_action( 'action_scheduler_init' ) );
		$this->assertTrue( TPW_Core_Scheduler::is_available() );
		$this->assertTrue( TPW_Core_Scheduler::is_ready() );
	}
}
