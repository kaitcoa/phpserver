<?php

namespace Kait\Server\PHPServer;
use Kait\Server\PHPServer\Exception;
use Kait\Server\PHPServer\Request;
class Server 
{

	protected $host = null;
	protected $port = null;
	protected $socket = null;
	
	public function __construct( $host, $port )
	{
		$this->host = $host;
		$this->port = (int) $port;
		$this->createSocket();
		$this->bind();
	}

	protected function createSocket()
	{
		$this->socket = socket_create( AF_INET, SOCK_STREAM, 0 );
	}
	
	protected function bind()
	{
		if ( !socket_bind( $this->socket, $this->host, $this->port ) )
		{
			throw new Exception( 'Cannot bind: '.$this->host.':'.$this->port.' - '.socket_strerror( socket_last_error() ) );
		}
	}
	
	public function listen( $callback )
	{
		if ( !is_callable( $callback ) )
		{
			throw new Exception( 'Argument is not callable.' );
		}
		
		while ( true ) 
		{
			socket_listen( $this->socket );
			
			if ( !$client = socket_accept( $this->socket ) ) 
			{
				socket_close( $client ); continue;
			}
			
			$request = Request::headerToString( socket_read( $client, 1024 ) );
			
			$response = call_user_func( $callback, $request );
			
			if ( !$response || !$response instanceof Response )
			{
				$response = Response::error( 404 );
			}
			
			$response = (string) $response;
			
			socket_write( $client, $response, strlen( $response ) );
			
			socket_close( $client );
		}
	}
}