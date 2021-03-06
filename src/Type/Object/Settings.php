<?php

namespace WPGraphQL\Type;

use GraphQL\Error\UserError;
use WPGraphQL\Data\DataSource;
use WPGraphQL\TypeRegistry;

$registered_settings = DataSource::get_allowed_settings();
$fields              = [];

if ( ! empty( $registered_settings ) && is_array( $registered_settings ) ) {

	/**
	 * Loop through the $settings_array and build the setting with
	 * proper fields
	 */
	foreach ( $registered_settings as $key => $setting_field ) {

		/**
		 * Determine if the individual setting already has a
		 * REST API name, if not use the option name.
		 * Then, sanitize the field name to be camelcase
		 */
		if ( ! empty( $setting_field['show_in_rest']['name'] ) ) {
			$field_key = $setting_field['show_in_rest']['name'];
		} else {
			$field_key = $key;
		}
		$field_key = lcfirst( $setting_field['group'] . 'Settings' . str_replace( '_', '', ucwords( $field_key, '_' ) ) );

		if ( ! empty( $key ) && ! empty( $field_key ) ) {

			/**
			 * Dynamically build the individual setting and it's fields
			 * then add it to $fields
			 */
			$fields[ $field_key ] = [
				'type'        => TypeRegistry::get_type( $setting_field['type'] ),
				'description' => $setting_field['description'],

				'resolve' => function( $root, $args, $context, $info ) use ( $setting_field, $field_key, $key ) {
					/**
					 * Check to see if the user querying the email field has the 'manage_options' capability
					 * All other options should be public by default
					 */
					if ( 'admin_email' === $key && ! current_user_can( 'manage_options' ) ) {
						throw new UserError( __( 'Sorry, you do not have permission to view this setting.', 'wp-graphql' ) );
					}

					$option = ! empty( $key ) ? get_option( $key ) : null;

					switch ( $setting_field['type'] ) {
						case 'integer':
							$option = absint( $option );
							break;
						case 'string':
							$option = (string) $option;
							break;
						case 'boolean':
							$option = (boolean) $option;
							break;
						case 'number':
							$option = (float) $option;
							break;
					}

					return $option;
				},
			];

		}

	}

}

register_graphql_object_type( 'Settings', [
	'description' => __( 'All of the registered settings', 'wp-graphql' ),
	'fields'      => $fields
] );
