<?php namespace Core;
/**
 * Console Controller 
 * run a application script
 *
 ** 
 *
 * @package		ClanCatsFramework
 * @author		Mario Döring <mario@clancats.com>
 * @version		2.0
 * @copyright 	2010 - 2014 ClanCats GmbH
 *
 */
class CCConsoleController 
{	
	/**
	 * Parse an command and execute console style
	 *
	 * @param string		$cmd
	 */
	public static function parse( $cmd ) 
	{	
		// if we dont have an command
		if ( empty( $cmd ) ) 
		{
			return false;
		}
		
		$params = array();
		
		// are we in a string
		$in_string = false;
		$len = strlen( $cmd );
		$crr_prm = "";
		$command_str = $cmd;
		$cmd = array();
		
		// loop trough
		for( $i=0;$len>$i;$i++ ) 
		{
			$char = $command_str[$i];
			switch( $char ) {
				case ' ':
					if ( !$in_string ) 
					{
						$cmd[] = $crr_prm;
						$crr_prm = '';
					} else {
						$crr_prm .= $char;
					}
				break;
				case '"':
					if( $i > 0 && $command_str[$c-1] != '\\' ) 
					{ 
						$in_string = !$in_string; 
					}
				break;
				default:
					$crr_prm .= $char;
				break;
			}
		}
		
		$cmd[] = $crr_prm;
		
		// get the controller
		$controller = array_shift( $cmd );
		
		// get the action
		$action = null;
		
		// check we got an action in our controller
		if ( strpos( $controller, '::' ) !== false ) 
		{
			$controller = explode( '::', $controller );
			$action = $controller[1];
			$controller = $controller[0];
		}
		
		// skipper if we got an named param skip the next
		$skip = false;
		
		// get the params
		foreach( $cmd as $key => $value ) 
		{
			if ( $skip ) 
			{
				$skip = false; continue;
			}
 			
			// named param?
			if ( substr( $value, 0, 1 ) == '-' ) 
			{
				if ( array_key_exists( $key+1, $cmd ) ) 
				{
					$next_value = $cmd[$key+1];
					if ( substr( $next_value, 0, 1 ) == '-' ) 
					{
						$params[substr( $value, 1 )] = true;
					} else {
						$params[substr( $value, 1 )] = $next_value; $skip = true;
					}	
				} else {
					$params[substr( $value, 1 )] = true;
				}
			} else {
				$params[] = $value;
			}
		}
		
		return static::run( trim( $controller ), trim( $action ), $params );
	}
	
	/**
	 * Run a console script
	 *
	 * @param string		$controller
	 * @param string		$action	
	 * @param array 		$params
	 */
	public static function run( $controller, $action = null, $params = array() ) 
	{
		// always enable the file infos
		// this allows CCFile to print an info when a file gets created or deleted.
		CCFile::enable_infos();

		// execute by default the help action
		if ( empty( $action ) ) 
		{
			$action = 'default';
		} 
		
		$path = CCPath::get( $controller, CCDIR_CONSOLE, EXT );
		
		// check if the file exists, if not try with core path
		if ( !file_exists( $path ) ) 
		{
			if ( !CCPath::contains_namespace( $controller ) ) 
			{
				$path = CCPath::get( CCCORE_NAMESPACE.'::'.$controller, CCDIR_CONSOLE, EXT );
			}
		}
		
		// still nothing?
		if ( !file_exists( $path ) ) 
		{
			CCCli::line( "Could not find controller {$controller}.", 'red' );
			return false;
		}
		
		// all console classes should be on the CCConsole namespace
		// this way you can easly overwrite a console script
		$class = 'CCConsole\\'.$controller;
		
		// add the class to the autoloader 
		\CCFinder::bind( $class, $path );
		
		// create an instance
		$class = new $class( $action, $params );
		
		// run wake function
		if ( method_exists( $class, 'wake' ) ) {
			call_user_func( array( $class, 'wake' ), $params );
		}
		
		// run the execution
		call_user_func( array( $class, '_execute' ), $action, $params );
		
		// run sleep
		if ( method_exists( $class, 'sleep' ) ) 
		{
			call_user_func( array( $class, 'sleep' ), $params );
		}
	}
	
	/**
	 * Call magic
	 * Try to execute a CCCli function.
	 *
	 * @param string		$method
	 * @param array 		$args
	 * @return mixed
	 */
	public function __call( $method, $args ) 
	{
		if ( method_exists( '\CCCli', $method ) )
		{
			return call_user_func_array( '\CCCli::'.$method, $args );
		}
		
		throw new \BadMethodCallException( "CCConsoleController - cannot find method '".$method."'." );
	}
	
	/**
	 * execute the controller
	 *
	 * @param string 	$action
	 * @param array 		$params
	 * @return void
	 */
	public function _execute( $action, $params = array() ) 
	{
		if ( method_exists( $this, 'action_'.$action ) ) 
		{
			call_user_func( array( $this, 'action_'.$action ), $params );
		} 
		else 
		{
			CCCli::line( "There is no action {$action}.", 'red' );
		}
	}
	
	/**
	 * default action
	 *
	 * @param array 		$params
	 * @return void
	 */
	public function action_default( $params ) {
		return $this->action_help( $params );
	}
	
	/**
	 * default help action
	 *
	 * @param array 		$params
	 * @return void
	 */
	public function action_help( $params ) {
		if ( method_exists( $this, 'help' ) ) 
		{
			 CCCli::line( $this->help_formatter( $this->help() ) ); return;
 		}
		CCCli::line( 'This console controller does not implement an help function or action.' , 'cyan' );
	}
	
	/**
	 * default help formatter
	 *
	 * @param array 		$params
	 * @return void
	 */
	protected function help_formatter( $help = null ) {
		if ( is_null( $help ) ) 
		{
			CCCli::line( 'Invalid data passed to help formatter.' , 'red' ); return;
		}
	
		$output = array();
		
		/*
		 * Format the name
		 */
		if ( array_key_exists( 'name', $help ) ) 
		{	
			$output[] = CCCli::color( '/**', 'white' );
			$output[] = CCCli::color( ' * ', 'white' ).CCCli::color( $help['name'] );
			$output[] = CCCli::color( ' * ', 'white' ).str_repeat( '-', strlen( $help['name'] ) );	
			
			/*
			 * add the description
			 */
			if ( array_key_exists( 'desc', $help ) ) 
			{
				$output[] = CCCli::color( ' * ', 'white' ).wordwrap( str_replace( "\n", "\n".' * ', $help['desc'] ), 60 );	
			}
			$output[] = CCCli::color( ' */', 'white' );
		}
		
		/*
		 * are there actions aviable
		 */
		if ( array_key_exists( 'actions', $help ) ) 
		{	
			$output[] = CCCli::color( 'Actions:', 'purple' );	
			foreach( $help['actions'] as $action => $desc )
			{
				$output[] = CCCli::color( ' - '.$action, 'cyan' );
				$output[] = CCCli::color( '   '.$desc );	
			}
		}
		
		return $output;
	}
}