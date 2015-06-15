<?php namespace App\Http\Controllers;


use \org\jsonrpcphp\JsonRPCClient;
use Input;
class ResponseSyncController extends SurveyHelper {

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
        //Start a JSON RPC Client for the requests
        $RPCClient = new JsonRPCClient(LS_BASEURL . 'admin/remotecontrol');

        //User private Token
        $sessionKey = $this->authUser($RPCClient);

        // Gettin the json input from the user, when sync is triggered
        $postData = Input::json()->all();

        $responses = $postData['answers'];
        $idSurvey = array_keys($responses)[0];

        // If survey is active send response
        $surveyStatus = $this->checkSurveyStatus($RPCClient,$sessionKey,$idSurvey);

        if($surveyStatus['status']){
            $this->addResponse($RPCClient,$sessionKey,$idSurvey,$responses);
        }
        else{
            return $surveyStatus;
        }

    }
	/**
	 * Display the specified resource.	 *
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
            dd($response);
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



}
