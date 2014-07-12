<?php namespace Core;
/**
 * CCView CCF template builder
 ** 
 *
 * @package		ClanCatsFramework
 * @author		Mario Döring <mario@clancats.com>
 * @version		2.0
 * @copyright 	2010 - 2014 ClanCats GmbH
 *
 */
class CCView_Builder_CCFTemplate implements CCView_Builder_Interface
{
	/**
	 * The view contents
	 *
	 * @var string
	 */
	protected $content = null;
	
	/**
	 * Starting commands
	 *
	 * @var array
	 */
	protected $bracket_starting_commands = array(
		'if', 
		'elseif',
		'for',
		'foreach',
		'each',
		'loop',
		'switch',
	);
	
	/**
	 * Ending commands
	 *
	 * @var array
	 */
	protected $bracket_ending_commands = array(
		'endif',
		'endfor',
		'endforeach',
		'endeach',
		'endloop',
		'break',
		'continue',
		'endswitch',
	);
	
	/**
	 * Continue commands
	 *
	 * @var array
	 */
	protected $bracket_continue_commands = array(
		'else',
	);
	
	/**
	 * View builder contructor
	 *
	 * @param string 		$file
	 * @return void
	 */
	public function __construct( $content )
	{
		$this->content = $content;
	}
	
	/**
	 * Compile method returns the compiled php view file
	 *
	 * @return string
	 */
	public function compile()
	{
		$this->transform( 'echos' );
		$this->transform( 'phptag' );
		
		//_dd( $this->content );
		
		return $this->content;
	}
	
	/**
	 * Execute a transformation
	 *
	 * @param string 		$function
	 * @return void
	 */
	private function transform( $function )
	{
		$this->content = call_user_func( array( $this, 'compile_'.$function ), $this->content );
	}
	
	/**
	 * Repair an expression
	 */
	public function repair_expression( $exp )
	{
		$commands = explode( ' ', $exp );		
		
		// filter empty ones
		$commands = array_filter( $commands, function( $value ) 
		{
			return !is_null( $value );
		});
		
		// bracket starting command
		if ( in_array( $commands[0], $this->bracket_starting_commands ) )
		{
			// each = foreach
			if ( $commands[0] == 'each' )
			{
				$commands[0] = 'foreach';
			}
			// loop special
			elseif ( $commands[0] == 'loop' )
			{
				$commands[0] = 'for';
				$commands[1] = '$i=0;$i<'.$commands[1].';$i++';
			}
			
			// remove the opening duble point
			end($commands); $key = key($commands);
			if ( substr( $commands[$key], -1 ) == ':' )
			{
				$commands[$key] = substr( $commands[$key], 0, -1 );
				
				// is it now empty?
				if ( $commands[$key] == ' ' || empty( $commands[$key] ) )
				{
					unset( $commands[$key] );
				}
			}
			
			// do we have brackets?
			if ( substr( $commands[1], 0, 1 ) != '(' )
			{
				// add starting bracket
				$commands[1] = '( '.$commands[1];
				
				// add ending bracket
				end($commands); $key = key($commands);
				$commands[$key] .= ' )';
			}
			
			$commands[] = ':';
		}
		// bracket ending command
		elseif ( count( $commands == 1 ) && in_array( $commands[0], $this->bracket_ending_commands ) )
		{
			// each = foreach
			if ( $commands[0] == 'endeach' )
			{
				$commands[0] = 'endforeach';
			}
			// loop special
			elseif ( $commands[0] == 'endloop' )
			{
				$commands[0] = 'endfor';
			}
			
			// check for semicolon
			if ( substr( $commands[0], 0, 1 ) != ';' )
			{
				$commands[0] .= ';';
			}
		}
		// bracket continue command
		elseif ( count( $commands == 1 ) && in_array( $commands[0], $this->bracket_continue_commands ) )
		{		
			// remove the opening duble point
			end($commands); $key = key($commands);
			if ( substr( $commands[$key], -1 ) == ':' )
			{
				$commands[$key] = substr( $commands[$key], 0, -1 );
				
				// is it now empty?
				if ( $commands[$key] == ' ' || empty( $commands[$key] ) )
				{
					unset( $commands[$key] );
				}
			}
			
			// add the double point
			$commands[] = ':';
		}
		
		return implode( ' ', $commands );
	}
	
	/**
	 * Search and replace the echo commands shortcuts
	 *
	 * @return void
	 */
	private function compile_echos( $view )
	{
		return preg_replace('/\{\{(.*?)\}\}/s', "<?php echo $1; ?>", $view );
	}
	
	/**
	 * Search and replace for shortcuts of the php tag
	 *
	 * @param string 	$view
	 * @return void
	 */
	private function compile_phptag( $view )
	{
		// I hate this workaround
		$that = $this;
		
		return preg_replace_callback('/\{\%(.*?)\%\}/s', function( $match ) use( $that )
		{ 
			$expression = trim( $match[1] );
			
			// repair it 
			$expression = $that->repair_expression( $expression );
			
			return '<?php '.$expression.' ?>'; 
		}, $view );
	}
}