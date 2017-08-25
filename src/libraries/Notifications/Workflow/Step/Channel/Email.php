<?php
/**
 * @package     PublishPress\Notifications
 * @author      PressShack <help@pressshack.com>
 * @copyright   Copyright (C) 2017 PressShack. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Channel;

class Email extends Base implements Channel_Interface {

	const META_KEY_EMAIL = '_psppno_chnemail';

	/**
	 * The constructor
	 */
	public function __construct() {
		$this->name  = 'email';
		$this->label = __( 'Email', 'publishpress-notifications' );
		$this->icon  = PUBLISHPRESS_URL . 'modules/improved-notifications/assets/img/icon-email.png';

		parent::__construct();
	}

	/**
	 * Returns a list of the receivers' emails
	 *
	 * @param array $receivers
	 * @return array
	 */
	protected function get_receivers_emails( $receivers ) {
		$emails = [];

		if ( ! empty( $receivers ) ) {
			foreach ( $receivers as $receiver ) {
				// Check if we have the user ID or an email address
				if ( is_numeric( $receiver ) ) {
					$data = $this->get_user_data( $receiver );
					$emails[] = $data->user_email;
					continue;
				}

				// Is it a valid email address?
				$emails[] = sanitize_email( $receiver );
			}
		}

		// Remove duplicated
		$emails = array_unique( $emails );

		return $emails;
	}

	/**
	 * Check if this channel is selected and triggers the notification.
	 *
	 * @param WP_Post $workflow_post
	 * @param array   $action_args
	 * @param array   $receivers
	 * @param array   $content
	 */
	public function action_notify( $workflow_post, $action_args, $receivers, $content ) {
		// Check if any of the receivers have Email configured as the channel
		$controller = $this->get_service( 'workflow_controller' );
		$filtered_receivers = $controller->get_receivers_by_channel( $workflow_post->ID, $receivers, 'email' );

		if ( ! empty( $filtered_receivers ) ) {
			// Send the emails
			$emails = $this->get_receivers_emails( $filtered_receivers );
			$action = 'transition_post_status' === $action_args['action'] ? 'status-change' : 'comment';

			// Call the legacy notification module
			$this->get_service( 'publishpress' )->notifications->send_email(
				$action,
				$action_args,
				$content['subject'],
				$content['body'],
				'',
				$emails
			);
		}
	}
}