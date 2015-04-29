<?php namespace App\Http\Controllers\GroupOfQuestions;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class GroupOfQuestionsController extends Controller {

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
        define( 'LS_BASEURL', 'http://192.168.10.1/limesurvey/index.php');  // adjust this one to your actual LimeSurvey URL
        define( 'LS_USER', 'admin' );
        define( 'LS_PASSWORD', 'qwerty' );

        //$client =  new Client(LS_BASEURL);
        // the survey to process
        $survey_id=177998;

        // instanciate a new client
        $myJSONRPCClient = new \org\jsonrpcphp\JsonRPCClient( LS_BASEURL.'/admin/remotecontrol' );

        // receive session key
        $sessionKey= $myJSONRPCClient->get_session_key( LS_USER, LS_PASSWORD );

        $questionInfo = $myJSONRPCClient->list_groups($sessionKey,$id);

        return $questionInfo;
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		//
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		//
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}

}
