<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use \org\jsonrpcphp\JsonRPCClient;


class ResponseSyncController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		//
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		//
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		//
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
        // Global variables tu access Limesurvey core

        //URL of the Limesurvey RemoteControll based in JSON-RPC
        define( 'LS_BASEURL', $_ENV['LS_BASEURL']);
        //Administrator User in Limesurvey
        define( 'LS_USER', $_ENV['LS_USER'] );
        //Administrator User passwrod in Limesurvey
        define( 'LS_PASSWORD', $_ENV['LS_PASSWORD'] );

        //Start a JSON RPC Client for the requests
        $RPCClient = new JsonRPCClient( LS_BASEURL.'/admin/remotecontrol' );

        //User private Token
        $sessionKey =  $this->authUser($RPCClient);

        $response = $this->addResponse($RPCClient,$sessionKey,$id);
        return $response;
	}

    private function addResponse($RPCClient, $sessionKey, $suId){

        $data = $_REQUEST['POST'];

        return $RPCClient->add_response($sessionKey,$suId,$data);

    }

    /** For every request, a token is necessary
     * @param $JSONRPCClient
     * @return User private token
     */
    private function authUser($JSONRPCClient){

        // receive session key
        $sessionKey= $JSONRPCClient->get_session_key( LS_USER, LS_PASSWORD );

        return $sessionKey;
    }
}
