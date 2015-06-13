<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use \org\jsonrpcphp\JsonRPCClient;

use Input;
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

        // Global variables tu access Limesurvey core

        //URL of the Limesurvey RemoteControll based in JSON-RPC
        define('LS_BASEURL', $_ENV['LS_BASEURL']);
        //Administrator User in Limesurvey
        define('LS_USER', $_ENV['LS_USER']);
        //Administrator User passwrod in Limesurvey
        define('LS_PASSWORD', $_ENV['LS_PASSWORD']);

        //Start a JSON RPC Client for the requests
        $RPCClient = new JsonRPCClient(LS_BASEURL . 'admin/remotecontrol');

        //User private Token
        $sessionKey = $this->authUser($RPCClient);

        // Gettin the json input from the user, when sync is triggered
        $postData = Input::json()->all();

        $responses = $postData['answers'];
        $idSurvey = array_keys($responses)[0];

        // If survey is active send response
        if($this->checkSurveyStatus($RPCClient,$sessionKey,$idSurvey)){
            $this->addResponse($RPCClient,$sessionKey,$idSurvey,$responses);
        }
        else{
            return array(
                "status" =>false,
                "message" => 'Survey not available'
            );
        }

    }
	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
	}

    /**
     * This function sends response to a valid and active survey
     * @param $RPCClient
     * @param $sessionKey
     * @param $idSurvey
     * @param $responses
     * @return array
     */
    private function addResponse($RPCClient, $sessionKey,$idSurvey, $responses){
        // Number of responses entered to the core
        $coreQuantityInserted = 0;

        foreach($responses as $response){
            $coreResponse = $RPCClient->add_response($sessionKey,$idSurvey,$response);
            if(is_numeric($coreResponse)){
                $coreQuantityInserted++;
            }
            //Error in insertion
            else{
                $coreResponse['message'] = $coreResponse['status'];
                $coreResponse['status'] = false;

                return $coreResponse;
            }
        }

        return array(
            "status" => true,
            "quantity" => $coreQuantityInserted
        );

    }

    /**
     * Checks if the survey is active, to send response
     * @param $RPCClient
     * @param $sessionKey
     * @param $suId
     */
    private function checkSurveyStatus($RPCClient, $sessionKey,$suId){

        $surveyStatus= $RPCClient->get_survey_properties($sessionKey,$suId,array(
            'sid','active'));
        
        if(array_key_exists('active',$surveyStatus) & $surveyStatus['active'] == 'Y')
        {
            return true;
        }
        return false;
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
