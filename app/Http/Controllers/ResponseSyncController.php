<?php namespace App\Http\Controllers;


use Carbon\Carbon;
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

        // Getting the json input from the user, when sync is triggered
        $postData = Input::json()->all();

        //First check if post data is valid
        if(count($postData) == 0){
            return array(
                'status' => false,
                'message' => 'Error en el JSON, revisar sintaxis'
            );
        }

        $responses = $postData['answers'];
        $idSurvey = $postData['sid'];

        // If survey is active send response
        $surveyStatus = $this->checkSurveyStatus($RPCClient,$sessionKey,$idSurvey);

        if($surveyStatus['status']){

            return $this->addResponse($RPCClient,$sessionKey,$idSurvey,$responses);
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
            $date = new \DateTime('now');
            $response['submitdate'] = $date->format('Y-m-d H:i:s');
            //dd($response);
            $coreResponse = $RPCClient->add_response($sessionKey,$idSurvey,$response);

            if(is_numeric($coreResponse)){
                $coreQuantityInserted++;
            }
            //Error in insertion
            else{
                $coreResponse['message'] = $coreResponse['status'];
                $coreResponse['status'] = false;
            }
        }

        return array(
            "status" => true,
            "inserted" => $coreQuantityInserted
        );

    }



}
