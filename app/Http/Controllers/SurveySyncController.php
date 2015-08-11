<?php namespace App\Http\Controllers;

use \org\jsonrpcphp\JsonRPCClient;

class SurveySyncController extends SurveyHelper {


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
        //Start a JSON RPC Client for the requests
        $RPCClient = new JsonRPCClient( LS_BASEURL.'admin/remotecontrol' );

        //User private Token
        $sessionKey =  $this->authUser($RPCClient);

        $surveyStatus = $this->checkSurveyStatus($RPCClient,$sessionKey,$id);

				// If survey is active send response
        if($surveyStatus['status']){
            return $this->syncSurvey($RPCClient,$sessionKey,$id);
        }
				// If survey is inactive
        else{
            return $surveyStatus;
        }

	}

    private function syncSurvey($RPCClient,$sessionKey,$id){
        //Get all the Sections or Groups of questions using unique
        //Survey id from the Limesurvey core service
        $groups = $RPCClient->list_groups($sessionKey,$id);

        //Empty Sections array
        $sections = array();

        //Configure each group/section of questions to return a correct JSON
        foreach($groups as $group){
            //Get the group/section id
            $idG = $group['id']['gid'];

            //Get all the information of a given group/section
            $groupInfo = $this->getGroupProperties($RPCClient,$sessionKey,$idG);

            // Get all questions of a given Group, it is a list.
            $qOfGroup = $this->getQuestionsOfgroup($RPCClient,$sessionKey,$id, $idG);

            $groupInfo ['questions']= $qOfGroup;

            //Verify if first seccion is for Volunteers group_name
            $groupName = $groupInfo['group_name'];
            preg_match('/(##volunteers)/', $groupName, $match);
            if($match){
                $groupInfo['type'] = 'volunteers';
            }
            $sections[] = $groupInfo;

        }

        $suInfo = $this->getSurveyProperties($RPCClient,$sessionKey,$id);

				// Before inserting, order by group_order
				// First Extract groupOrder and then apply SORT
				foreach($sections as $key =>$row){
					$sectionsOrder[$key] = $row['group_order'];
				}
				array_multisort($sectionsOrder,SORT_ASC,$sections);

        $suInfo['sections'] = $sections;

        // release the session key
        $RPCClient->release_session_key($sessionKey);
        return $suInfo;

}

    /** Get the properties of a given survey
     * @param $RPCClient
     * @param $sessionKey
     * @param $idSu
     * @return array
     */
    private function getSurveyProperties($RPCClient,$sessionKey, $idSu){

        $surveyProp = $RPCClient->get_survey_properties($sessionKey,$idSu,array(
            'sid','active',	'autonumber_start',	'owner_id','admin','expires',
            'adminemail','startdate','format',
            'tokenlength','usetokens',
            'datecreated','showprogress','datestamp'
        ));

        // Adding extra data to the Survey using get_language_properties
        $moreSurveyProp = $RPCClient->get_language_properties($sessionKey,$idSu,array(
            'surveyls_title','surveyls_description','surveyls_welcometext',
            'surveyls_endtext'
        ),'es');
        // Insert extra data to array
        $united = array_merge($moreSurveyProp,$surveyProp);
        return $united;
    }

    /** Get the properties of a given question
     * @param $sessionKey
     * @param $id
     * @return mixed
     */
    private function getQuestionProperty($RPCClient, $sessionKey, $id){

        $qList = $RPCClient->get_question_properties($sessionKey,$id, array(
            'type',	'help', 'parent_qid','title','other','scale_id',
            'sid',	'question',	'mandatory'	,'same_default',
            'gid',	'preg',	'question_order',	'relevance',
            'subquestions',	'attributes','attributes_lang','answeroptions'
        ));
        $qList['id'] = $id;
				//Remove all html tags from limesurvey in 'question'
        $qList['question'] = strip_tags($qList['question']);
				//Remove all html tags from limesurvey in 'help'
				$qList['help'] = strip_tags($qList['help']);

				// Remove external parentheses in Conditional
				preg_match('/(\()(.*)(\))/', $qList['relevance'], $match);
				//dd($match);
				if($match){
					$qList['relevance'] = $match[2];
				}

				// Remove the phrase .NAOK of every Conditional
				$qList['relevance']= preg_replace('/(.NAOK)/','',$qList['relevance']);

				// Change ! for not in  every Conditional
				$qList['relevance']= preg_replace('/(!)/','not',$qList['relevance']);
				// Fix, because /(!\()/ does not work
				$qList['relevance']= preg_replace('/(not=)/','!=',$qList['relevance']);

				// Remove is_empty and replace it with undefined
				preg_match_all('/is_empty\((\w*)/',$qList['relevance'],$matches,PREG_SET_ORDER);
				// Get only full identifires, that have is_empty
				$matchCleared = array();
				$patterns = array();
				if($match){
					foreach($matches as $match){
						$matchCleared[] = '('.$match[1].' == "-1")';
						$patterns[] = '/is_empty\(\w*\)/';
					}
					$qList['relevance'] = preg_replace($patterns,$matchCleared,$qList['relevance']);
				}

        //Add of "checked" to any subquestion for ease of render in app
        // Only if the array has subquestions

        if(is_array($qList['subquestions'])){
            $subQuestions = array();
            foreach($qList['subquestions'] as $key => $subQuestion){
                $subQuestion['checked'] = false;
                //Add 'answer' empty field to store answer in phone
								// Works with 'scale_id' 1 or 0
                $subQuestion['answer'] = "";

                //Search for a type in Question, using the ##type
                $questionText = $subQuestion['question'];
                preg_match('/(##)(.)/', $questionText, $match);
                if($match){
                    //Set type found. second position is the character
                    $subQuestion['type'] = strtoupper($match[2]);
                    //Remove specified ##type
                    $subQuestion['question'] = preg_replace('/(##)(.)/','',$questionText);
                }
                //Add modified question to question
                $subQuestions[$key] = $subQuestion;
            }
            $qList['subquestions'] = $subQuestions;
        }

				// If the answeroptions has a ##type converto to type
				if(is_array($qList['answeroptions'])){
            $answerOptions = array();
            foreach($qList['answeroptions'] as $key => $answerOption){
                //Search for a type in Question, using the ##type
                $answerText = $answerOption['answer'];
                preg_match('/(##)(.)/', $answerText, $match);
                if($match){
                    //Set type found. second position is the character
                    $answerOption['type'] = strtoupper($match[2]);
                    //Remove specified ##type
                    $answerOption['answer'] = preg_replace('/(##)(.)/','',$answerText);
                }
                //Add modified answer to subanswer
                $answerOptions[$key] = $answerOption;
            }
            $qList['answeroptions'] = $answerOptions;
        }
				$questionText = $qList['question'];
				//Check if the question belogs to a Sub-section
				preg_match('/##(.*)##QG(\d+)/', $questionText, $match);
				if($match){
					$qList['subSectionId'] = $match[2];
					$qList['SubSectionName'] = $match[1];
					//Remove the specification of the subSection
					$qList['question'] = preg_replace('/(##.*##QG\d+)/','',$questionText);
				}

				// EXCLUDE_ALL_OTHERS change ';' for space
				if(is_array($qList['attributes'])){
					if(array_key_exists('exclude_all_others',$qList['attributes'])){
						$attribute = $qList['attributes']['exclude_all_others'];
						$qList['attributes']['exclude_all_others'] = preg_replace('/;/',' ',$attribute);
					}
				}

        return $qList;
    }

    /** Get a list of questions of a given group/section. It contains properties
     * of every question
     * @param $RPCClient
     * @param $sessionKey
     * @param $surveyId
     * @param $groupId
     * @return array|null
     */
    private function getQuestionsOfgroup($RPCClient,$sessionKey,$surveyId,$groupId){

        $questionsList = $RPCClient->list_questions($sessionKey,$surveyId,$groupId);

        $qWithProps = null;
        foreach($questionsList as $question){

            $qId = $question['id']['qid'];

            //Question with property
            $qWithProperty = $this->getQuestionProperty($RPCClient,$sessionKey,$qId);
            //Quitar preguntas que son subpreguntas
            if($qWithProperty['parent_qid'] == 0 ){
                $qWithProps [] = $this->getQuestionProperty($RPCClient,$sessionKey,$qId);
            }
        }
				// Before returning, order by question_order
				// First Extract questionOrder and then apply SORT
				foreach($qWithProps as $key =>$row){
					$questionOrder[$key] = $row['question_order'];
				}
				array_multisort($questionOrder,SORT_ASC,$qWithProps);
        return $qWithProps;
    }

    /** Get all the information of a given Group/Section of
     *  Questions.
     * @param $RPCClient
     * @param $sessionKey
     * @param $gId
     * @return mixed
     */
    private function getGroupProperties($RPCClient,$sessionKey, $gId){

        $groupInfo = $RPCClient->get_group_properties($sessionKey,$gId,array(
            'gid','group_order','description','sid','group_name',

        ));

				//Remove all html tags from limesurvey in 'description'
				$groupInfo['description'] = strip_tags($groupInfo['description']);
        return $groupInfo;
    }


}
