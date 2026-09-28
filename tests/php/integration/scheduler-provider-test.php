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

	public function test_unschedule_all_for_hook_group_preserves_other_groups() {
		$hook    = 'tpw_core_scheduler_provider_test';
		$group   = 'tpw-core-scheduler-provider-test';
		$control = 'tpw-core-scheduler-provider-control';
		$when    = time() + HOUR_IN_SECONDS;

		$first_action_id  = as_schedule_single_action( $when, $hook, array( 'fixture' => 'first' ), $group, false );
		$second_action_id = as_schedule_single_action( $when, $hook, array( 'fixture' => 'second' ), $group, false );
		$control_action_id = as_schedule_single_action( $when, $hook, array( 'fixture' => 'control' ), $control, false );

		$this->assertGreaterThan( 0, $first_action_id );
		$this->assertGreaterThan( 0, $second_action_id );
		$this->assertGreaterThan( 0, $control_action_id );
		$this->assertSame( 2, TPW_Core_Scheduler::unschedule_all_for_hook_group( $hook, $group ) );
		$this->assertFalse( TPW_Core_Scheduler::has_scheduled( $hook, array( 'fixture' => 'first' ), $group ) );
		$this->assertFalse( TPW_Core_Scheduler::has_scheduled( $hook, array( 'fixture' => 'second' ), $group ) );
		$this->assertTrue( TPW_Core_Scheduler::has_scheduled( $hook, array( 'fixture' => 'control' ), $control ) );

		TPW_Core_Scheduler::unschedule( $hook, array( 'fixture' => 'control' ), $control );
	}
}
