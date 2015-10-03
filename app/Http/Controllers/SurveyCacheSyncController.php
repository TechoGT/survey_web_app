<?php namespace App\Http\Controllers;

class SurveyCacheSyncController extends SurveyHelper {


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
        $survey = file_get_contents('http://localhost:3000/api/construct/'.$id);

        $decodif = json_decode($survey,true);

        if(array_key_exists('status', $decodif){
        	return $decodif;
        }

        if($decodif === null){

            $coreResponse['message'] = 'Encueta no valida';
            $coreResponse['status'] = false;
            return $coreResponse;
        }
        return $decodif['content'];
	}

}
