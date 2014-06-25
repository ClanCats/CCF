<?php
/*
 *---------------------------------------------------------------
 * Mail configuration
 *---------------------------------------------------------------
 */
return array(

	/*
	 * Define the transporters
 	 */
	'transporter' => array(

		// This is the default mail transporter
		'main' => array(

			// default is diver is unsing sendmail
			'driver' => 'array',
		),
		
		'alias' => 'main',
	),

	/*
	 * You can disable the entire mailing.
	 * This can be very useful for testing purposes.
	 */
	'disabled' => false,

	/*
	 * You can add a layout view, your html mail message will be available
	 * as $content in the layout file.
	 */
	'layout'	 => null,

	/*
	 * catch all emails and send them to another address.
	 */
	'catch_all' => array(

		// enable disable this feature
		'enabled' => false,

		// to what addresses should the mail be send instead.
		'addresses'	=> array(),

		// with what transporter should the mails 
		'transporter' => null,
	),

	/*
	 * every mail will be blind copied to these addresses.
	 */
	'bcc' => array(
	),

	/*
	 * mail defaults
	 */ 
	'defaults' => array(
		'from_mail' 		=> 'info@example.com',
		'from_name' 		=> ClanCats::runtime( 'name' ),
	),
);