<?php
/**
 *@copyright : ToXSL Technologies Pvt. Ltd. < www.toxsl.com >
 *@author	 : Shiv Charan Panjeta < shiv@toxsl.com >
 */
namespace app\controllers;

use Mpdf\Mpdf;
use app\components\TActiveForm;
use app\components\TController;
use app\models\File;
use app\models\Notification;
use app\models\QuestionBank;
use app\models\ResearchProgram;
use app\models\ShareSarvey;
use app\models\SurveyAnswer;
use app\models\SurveyQuestionDay;
use app\models\Surveyquestion;
use app\models\Surveyquestionoption;
use app\models\User;
use app\models\Userprogram;
use app\models\search\ResearchProgram as ResearchProgramSearch;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\AccessRule;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use app\models\SurveyAnswerOption;

/**
 * ResearchProgramController implements the CRUD actions for ResearchProgram model.
 */
class ResearchProgramController extends TController
{

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'ruleConfig' => [
                    'class' => AccessRule::className()
                ],
                'rules' => [
                    [
                        'actions' => [
                            'index',
                            'view',
                            'update',
                            'delete',
                            'ajax'
                        ],
                        'allow' => true,
                        'matchCallback' => function () {
                            return User::isAdmin();
                        }
                    ],
                    [
                        'actions' => [

                            'research-list',
                            'add',
                            'update',
                            'delete',
                            'add-question',
                            'create',
                            'view-research',
                            'update-research',
                            'participants',
                            'add-participation',
                            'remove-participation',
                            'update-research',
                            'clone-research',
                            'save-clone',
                            'research-share',
                            'share-program',
                            'remove-user',
                            'shared-research-list',
                            'get-question-details',
                            'current-survey-answer',
                             'survey-answers',
                            'research-history',
                            'view-research-history',
                            'download-data'
                        ],
                        'allow' => true,
                        'matchCallback' => function () {
                            return User::isManager();
                        }
                    ],

                     
                    [
                        'actions' => [
                            'research-list',
                            'add',
                            'create-by-participant',
                            'view-research',
                            'update-research',
                            'view-survey',
                            'current-survey',
                            'current-survey-answer',
                          
                            'view-current-survey',
                            'notification',
                            'get-question-details',
                            'gallery-images',
                            'delete-gallery-image',
                            'save-answer'
                        ],
                        'allow' => true,
                        'matchCallback' => function () {
                            return User::isUser();
                        }
                    ]
                ]
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'delete' => [
                        'post',
                        'get'
                    ]
                ]
            ]
        ];
    }

    /**
     * Lists all ResearchProgram models.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ResearchProgramSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $this->updateMenuItems();
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionResearchList()
    {
        $model = new ResearchProgram();

         
        $searchModel = new ResearchProgramSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $dataProvider->query->andWhere([
            'created_by_id' => \Yii::$app->user->id
        ]);
        return $this->render('programlist', [
            'model' => $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionSharedResearchList()
    {
        $model = new ResearchProgram();
        $searchModel = new ResearchProgramSearch();
        $query = ShareSarvey::find()->select('research_id')
            ->distinct()
            ->where([
            'share_with_id' => \Yii::$app->user->id
        ]);

        $researchQuery = ResearchProgram::find()->where([
            'IN',
            'id',
            $query
        ]);

        $dataProvider = new ActiveDataProvider([
            'query' => $researchQuery,
            'pagination' => [
                'pageSize' => 10
            ],
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC
                ]
            ]
        ]);
        return $this->render('programlist', [
            'model' => $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionParticipants($id,$research_id=null)
    {
        // echo "Hello";die();
        $model = $this->findModel($id);
        if (! empty($model)) {
            $participantsQuery = Userprogram::find()->where([
                'research_id' => $model->id,
                'state_id' => Userprogram::STATE_ACTIVE
            ]);

            $participantModel = new Userprogram();
            $participantsDataProvider = new ActiveDataProvider([
                'query' => $participantsQuery,
                'pagination' => [
                    'pageSize' => 10
                ],
                'sort' => [
                    'defaultOrder' => [
                        'id' => SORT_DESC
                    ]
                ]
            ]);

            return $this->render('participants', [
                'model' => $model,
                'participantModel' => $participantModel,
                'participantsDataProvider' => $participantsDataProvider
            ]);
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }

    public function actionResearchShare($id)
    {
        $research = $this->findModel($id);
        $sharedUsers = ShareSarvey::find()->select('share_with_id')
            ->where([
            'created_by_id' => \Yii::$app->user->id,
            'research_id' => $id
        ])
            ->all();
        $allUsers = [];
        if (! empty($sharedUsers)) {
            foreach ($sharedUsers as $user) {
                $allUsers[] = $user->share_with_id;
            }
        }
        $administrator = User::find()->where([
            'AND',
            [
                'role_id' => User::ROLE_MANAGER
            ],
            [
                '!=',
                'state_id',
                User::STATE_INACTIVE
            ],
            [
                '!=',
                'id',
                \Yii::$app->user->id
            ]
        ]);
        $participantModel = new Userprogram();
        $administratorDataProvider = new ActiveDataProvider([
            'query' => $administrator,
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC
                ]
            ]
        ]);

        return $this->render('research-share', [
            'research' => $research,
            'participantModel' => $participantModel,
            'sharedUsers' => $allUsers,
            'participantsDataProvider' => $administratorDataProvider
        ]);
    }

    public function actionShareProgram()
    {
        $model = new ShareSarvey();
        $post = \Yii::$app->request->bodyParams;
        if (! empty($post)) {
            if ($post['user_id'] && $post['research_id']) {
                $model->created_by_id = \Yii::$app->user->id;
                $model->state_id = ShareSarvey::STATE_ACTIVE;
                $model->research_id = $post['research_id'];
                $model->share_with_id = $post['user_id'];
                $exist = ShareSarvey::find()->where([
                    'research_id' => $post['research_id'],
                    'share_with_id' => $post['user_id'],
                    'state_id' => ShareSarvey::STATE_ACTIVE
                ])->one();
                if (! empty($exist)) {
                    $data = ShareSarvey::STATUS_OK;
                } else {
                    if ($model->save()) {
                        $data = ShareSarvey::STATUS_OK;
                    }
                }
            } else {
                $data = "No data posted.";
            }
        } else {
            $data = "No data posted.";
        }
        return $data;
    }

    public function actionRemoveUser()
    {
        $post = \Yii::$app->request->bodyParams;
        if (! empty($post)) {
            if ($post['user_id'] && $post['research_id']) {
                $share = ShareSarvey::find()->where([
                    'share_with_id' => $post['user_id'],
                    'research_id' => $post['research_id']
                ])->one();
            } else {
                $share = ShareSarvey::find()->where([
                    'share_with_id' => \Yii::$app->user->id,
                    'research_id' => $post['research_id']
                ])->one();
            }
            if (! empty($share)) {
                $share->delete();
                $data = ShareSarvey::STATUS_OK;
            } else {
                $data = ShareSarvey::STATUS_NOTOK;
            }
        } else {
            $data = "No data posted.";
        }
        return $data;
    }

    public function actionViewResearch($id)
    {
        $model = $this->findModel($id);
        $questionQuery = Surveyquestion::find()->where([
            'research_id' => $model->id
        ]);
        $participantsQuery = Userprogram::find()->where([
            'research_id' => $model->id
        ]);

        $participantModel = new Userprogram();
        $questionDataProvider = new ActiveDataProvider([
            'query' => $questionQuery,
            'pagination' => [
                'pageSize' => 10
            ]
        ]);
        $participantsDataProvider = new ActiveDataProvider([
            'query' => $participantsQuery,
            'pagination' => [
                'pageSize' => 10
            ],
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC
                ]
            ]
        ]);

        return $this->render('view-research', [
            'model' => $model,
            'participantModel' => $participantModel,
            'questionDataProvider' => $questionDataProvider,
            'participantsDataProvider' => $participantsDataProvider
        ]);
    }

    public function actionViewResearchHistory($id)
    {
        $research = $this->findModel($id);
        if (! empty($research)) {
            $questions = Surveyquestion::find()->where([
                'research_id' => $id
            ])->all();
        }
        return $this->render('view-research-history', [
            'model' => $research,
            'questions' => $questions
        ]);
    }

    public function actionDownloadData($id)
    {
       
        $mpdf = new Mpdf();
        $research = ResearchProgram::find()->where([
            'id' => $id
        ])->one();
        if (! empty($research)) {
            $questions = Surveyquestion::find()->where([
                'research_id' => $id
            ])
                ->limit(3000)
                ->all();
            if (! empty($questions)) {
                $question = [];
                foreach ($questions as $ques) {
                    $question[] = $ques->asCustomJson(true);
                }
                $output = $this->renderPartial('download-data', [
                    'questions' => $question,
                    'research' => $research
                ]);

                $mpdf->WriteHTML($output);
                $mpdf->output();
                // $mpdf->Output('myPdf.pdf', 'D');
            }
        }
    }

    public function actionResearchHistory()
    {
        $query = ResearchProgram::find()->where([
            'created_by_id' => \Yii::$app->user->id
        ])->andwhere([
            '<',
            'end_date',
            date('Y-m-d')
        ]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10
            ],
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC
                ]
            ]
        ]);

        return $this->render('historyprogramlist', [
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionRemoveParticipation($id)
    {
        if (! empty($id)) {
            $model = Userprogram::findOne($id);
            if (! empty($model)) {
                $model->state_id = Userprogram::STATE_DELETED;
                if ($model->save()) {
                    \Yii::$app->getSession()->setFlash('success', \Yii::t('app', 'Participant deleted succesfully.'));
                    return $this->redirect(\Yii::$app->request->referrer);
                } else {
                    \Yii::$app->getSession()->setFlash('error', "Error !!" . $model->getErrorsString());
                    return $this->redirect(\Yii::$app->request->referrer);
                }
            } else {
                \Yii::$app->getSession()->setFlash('success', \Yii::t('app', 'Record not found.'));
                return $this->redirect(\Yii::$app->request->referrer);
            }
        } else {
            \Yii::$app->getSession()->setFlash('success', \Yii::t('app', 'No Data Posting.'));
        }
        return $this->redirect(\Yii::$app->request->referrer);
    }

    public function actionAddParticipation()
    {
        $model = new Userprogram();
        $model->scenario = 'add';
        $post = \yii::$app->request->post();
        if (\yii::$app->request->isAjax && $model->load($post)) {
            \yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return TActiveForm::validate($model);
        }

        if ($model->load($post)) {
            $exists = Userprogram::find()->where([
                'created_by_id' => $model->created_by_id,
                'state_id' => Userprogram::STATE_ACTIVE
            ])->exists();
            if (! $exists) {
                $is_exist = Userprogram::find()->where([
                    'created_by_id' => $model->created_by_id,
                    'state_id' => Userprogram::STATE_DELETED,
                    'research_id' => $model->research_id
                ])->one();
                if (! empty($is_exist)) {
                    $is_exist->state_id = Userprogram::STATE_ACTIVE;
                    if (! $is_exist->save()) {
                        \Yii::$app->getSession()->setFlash('error', "Error !!" . $is_exist->getErrorsString());
                    }
                    \Yii::$app->getSession()->setFlash('success', \Yii::t('app', 'Participant added successfully.'));
                    return $this->redirect(\Yii::$app->request->referrer);
                } else {
                    $model->state_id = Userprogram::STATE_ACTIVE;
                    if ($model->save()) {
                        \Yii::$app->getSession()->setFlash('success', \Yii::t('app', 'Participant added successfully.'));
                        return $this->redirect(\Yii::$app->request->referrer);
                    } else {

                        \Yii::$app->getSession()->setFlash('error', "Error !!" . $model->getErrorsString());
                        return $this->redirect(\Yii::$app->request->referrer);
                    }
                }
            } else {
                \Yii::$app->getSession()->setFlash('success', \Yii::t('app', 'Participant already exist in some other research.'));
                return $this->redirect(\Yii::$app->request->referrer);
            }
        } else {
            \Yii::$app->getSession()->setFlash('success', \Yii::t('app', 'No data posted.'));
            return $this->redirect(\Yii::$app->request->referrer);
        }
        return $this->redirect(\Yii::$app->request->referrer);
    }

    /**
     * Displays a single ResearchProgram model.
     *
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $this->updateMenuItems($model);
        return $this->render('view', [
            'model' => $model
        ]);
    }

    /**
     * Creates a new ResearchProgram model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @return mixed
     */
    public function actionAdd()
    {
        if (User::isUser()) {
            $research = ResearchProgram::findActive()->where([
                'created_by_id' => \Yii::$app->user->id
            ])
                ->andwhere([
                '>',
                'end_date',
                date('Y-m-d')
            ])
                ->one();
            if (! empty($research)) {
                \Yii::$app->session->setFlash('error', \Yii::t('app', 'You already have an active research in your list.'));
                return $this->redirect([
                    'research-list'
                ]);
            }
        }
        $model = new ResearchProgram();

          
        $searchModel = new ResearchProgramSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->andWhere([
            'created_by_id' => \Yii::$app->user->id
        ]);
        $model->loadDefaultValues();
        $model->state_id = ResearchProgram::STATE_ACTIVE;
        $type = ResearchProgram::TYPE_ADMIN;
        if (\Yii::$app->user->identity->role_id == User::ROLE_MANAGER) {
            $type = ResearchProgram::TYPE_ADMIN;
        } elseif (\Yii::$app->user->identity->role_id == User::ROLE_USER) {
            $type = ResearchProgram::TYPE_PARTICIPANT;
        }
        $model->type_id = $type;
        $post = \yii::$app->request->post();
  
            
        if (\yii::$app->request->isAjax && $model->load($post)) {
            \yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
             return $this->refresh();
            return TActiveForm::validate($model);
        }
       // $assign_code = $post['ResearchProgram']['assign_code'];
      //   die();
        //      if (ResearchProgram::find()->where(['assign_code'=>$assign_code])->exists()) {
        //     \Yii::$app->session->setFlash('error', \Yii::t('app', 'Assign Code has already been taken.'));
        //     return $this->redirect([
        //         'research-list'
        //     ]);
        // }
        if ($model->load($post)) {
            $earlier = strtotime($model->start_date);
            $later = strtotime($model->end_date);
            $diff = ($later - $earlier) / (60 * 24 * 60);
            $model->submission_timeline = (string) $diff;
            if ($model->save()) {
                if (User::isAdmin()) {
                    return $this->render([
                        'view',
                        'id' => $model->id
                    ]);
                } elseif (User::isUser()) {
                    return $this->redirect([
                        'create-by-participant',
                        'id' => $model->id
                    ]);
                } else {
                    return $this->redirect([
                        'create',
                        'id' => $model->id
                    ]);
                }
            } else {
                print_r($model->getErrorsString());
                exit();
            }
        }
        $this->updateMenuItems();
        if (User::isAdmin()) {
            return $this->render('add', [
                'model' => $model
            ]);
        } else {
            return $this->render('programlist', [
                'model' => $model,
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider
            ]);
        }
    }

    public function actionAddQuestion()
    {
        $model = new ResearchProgram();
        $model->loadDefaultValues();
        $model->state_id = ResearchProgram::STATE_ACTIVE;
        $post = \yii::$app->request->post();
        if (\yii::$app->request->isAjax && $model->load($post)) {
            \yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return TActiveForm::validate($model);
        }
        if ($model->load($post)) {
            $earlier = strtotime($model->start_date);
            $later = strtotime($model->end_date);
            $diff = ($later - $earlier) / (60 * 24 * 60);
            $model->submission_timeline = (string) $diff;
            if ($model->save()) {
                if (User::isAdmin()) {
                    return $this->render([
                        'view',
                        'id' => $model->id
                    ]);
                } else {
                    return $this->redirect([
                        'create',
                        'id' => $model->id
                    ]);
                }
            }
        }
        $this->updateMenuItems();
        if (User::isAdmin()) {
            return $this->red('add', [
                'model' => $model
            ]);
        } else {
            return $this->redirect([
                'create',
                'id' => $model->id
            ]);
        }
    }

    public function actionSaveClone($id = null)
    {
        $research = $this->findModel($id);
        $researchQuestion = $research->questions;
        $modelPerson = new ResearchProgram();
        $modelsHouse = [
            new Surveyquestion()
        ];
        $modelsRoom = [
            [
                new Surveyquestionoption()
            ]
        ];
        $post = Yii::$app->request->post();
        $Days = [];

        if (! empty($researchQuestion)) {
            foreach ($researchQuestion as $indexQuestion => $modelQuestion) {
                $Days = $modelQuestion->day;
                $Days = explode(',', $Days);

                $options = $modelQuestion->options;
                $researchDays = $modelQuestion->surveyQuestionDay;
                $researchOptions[$indexQuestion] = $options;
                if (empty($options)) {
                    $researchOptions[$indexQuestion] = [
                        new Surveyquestionoption()
                    ];
                }
            }
        }

        if ($modelPerson->load(Yii::$app->request->post())) {

            $modelsHouse = Surveyquestion::createMultiple(Surveyquestion::classname());
            Model::loadMultiple($modelsHouse, Yii::$app->request->post());

            // validate person and houses models
            $valid = $modelPerson->validate();
            $valid = Model::validateMultiple($modelsHouse) && $valid;
            if (isset($_POST['Surveyquestionoption'][0][0])) {
                foreach ($_POST['Surveyquestionoption'] as $indexHouse => $rooms) {
                    foreach ($rooms as $indexRoom => $room) {
                        $data['Surveyquestionoption'] = $room;
                        $modelRoom = new Surveyquestionoption();
                        $modelRoom->load($data);
                        $modelsRoom[$indexHouse][$indexRoom] = $modelRoom;
                        $valid = $modelRoom->validate();
                    }
                }
            }
            if (! empty($post)) {
                $valid = true;
            }

            if ($valid) {

                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $earlier = strtotime($modelPerson->start_date);
                    $later = strtotime($modelPerson->end_date);
                    $diff = ($later - $earlier) / (60 * 24 * 60);
                    $modelPerson->submission_timeline = (string) $diff;
                    $modelPerson->state_id = ResearchProgram::STATE_ACTIVE;

                    if ($modelPerson->save()) {
                        $flag = true;
                    } else {
                        $flag = false;
                    }
                    if ($flag) {

                        foreach ($modelsHouse as $indexHouse => $modelHouse) {

                            if ($flag === false) {
                                break;
                            }
                            $questionDays = implode(',', $modelHouse->day);
                            $modelHouse->day = $questionDays;
                            $modelHouse->research_id = $modelPerson->id;
                            $modelHouse->state_id = Surveyquestion::STATE_ACTIVE;
                            if (! ($flag = $modelHouse->save(false))) {
                                break;
                            }

                            $questionDays = explode(',', $modelHouse->day);
                            foreach ($questionDays as $questionDay) {
                                $questionDayModel = new SurveyQuestionDay();
                                $questionDayModel->day = date('Y-m-d', strtotime($modelPerson->start_date . ' +' . ($questionDay - 1) . ' day'));
                                $questionDayModel->state_id = SurveyQuestionDay::STATE_ACTIVE;
                                $questionDayModel->research_id = $modelPerson->id;
                                $questionDayModel->survey_question_id = $modelHouse->id;
                                $questionDayModel->created_on = date('Y-m-d');
                                $questionDayModel->updated_on = date('Y-m-d');
                                $questionDayModel->created_by_id = \Yii::$app->user->id;
                                if (! ($flag = $questionDayModel->save(false))) {
                                    break;
                                }
                            }

                            if (isset($modelsRoom[$indexHouse]) && is_array($modelsRoom[$indexHouse])) {
                                foreach ($modelsRoom[$indexHouse] as $indexRoom => $modelRoom) {
                                    if (! empty($modelRoom->option)) {
                                        $modelRoom->question_id = $modelHouse->id;
                                        $modelRoom->state_id = Surveyquestionoption::STATE_ACTIVE;
                                        $modelRoom->type_id = $modelHouse->type_id;
                                        if (! ($flag = $modelRoom->save(false))) {
                                            break;
                                        }
                                    } else {
                                        $flag = true;
                                    }
                                }
                            }
                        }
                    }

                    if ($flag) {

                        $transaction->commit();
                        return $this->redirect([
                            'view-research',
                            'id' => $modelPerson->id
                        ]);
                    } else {
                        $transaction->rollBack();
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                }
            }
        }
        return $this->render('clone-question', [
            'id' => $research->id,
            'Days' => $Days,
            'research' => $research,
            'researchQuestion' => (empty($researchQuestion)) ? [
                new Surveyquestion()
            ] : $researchQuestion,
            'researchOptions' => (empty($researchOptions)) ? [
                [
                    new Surveyquestionoption()
                ]
            ] : $researchOptions
        ]);
    }

    public function actionCreate($id = null)
    {
        if (! empty($id)) {
            $research = ResearchProgram::findOne($id);
            $id = isset($research->id) ? $research->id : '';
        }
        $modelPerson = new ResearchProgram();
        $modelsHouse = [
            new Surveyquestion([
                'scenario' => 'add-question'
            ])
        ];

        $modelsRoom = [
            [
                new Surveyquestionoption()
            ]
        ];
        $post = Yii::$app->request->post();

             // echo "<pre>";print_r($post);die();
        
        if ($modelPerson->load(Yii::$app->request->post())) {
            
            $modelsHouse = Surveyquestion::createMultiple(Surveyquestion::classname());
            Model::loadMultiple($modelsHouse, Yii::$app->request->post());
            
            // validate person and houses models
            $valid = $modelPerson->validate();
            $valid = Model::validateMultiple($modelsHouse) && $valid;
            if (isset($_POST['Surveyquestionoption'][0][0])) {
                foreach ($_POST['Surveyquestionoption'] as $indexHouse => $rooms) {
                    foreach ($rooms as $indexRoom => $room) {
                        $data['Surveyquestionoption'] = $room;
                        $modelRoom = new Surveyquestionoption();
                        $modelRoom->load($data);
                        $modelsRoom[$indexHouse][$indexRoom] = $modelRoom;
                        $valid = $modelRoom->validate();
                    }
                }
            }
            $post = Yii::$app->request->post();
              // echo "<pre>";print_r($post);die();
            if (! empty($post)) {
                $valid = true;
            }
            
            if ($valid) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $modelPerson = ResearchProgram::find()->where([
                        'id' => $post['ResearchProgram']['id']
                    ])->one();
                    if (! empty($modelPerson)) {
                        $flag = true;
                    } else {
                        $flag = false;
                    }
                    if ($flag) {
                        
                        foreach ($modelsHouse as $indexHouse => $modelHouse) {
                          // echo "<pre>";print_r($modelHouse);die();
                            if ($flag === false) {
                                break;
                            }
                            $questionDays = implode(',', $modelHouse->day);
                            $modelHouse->day = $questionDays;
                            $modelHouse->research_id = $modelPerson->id;
                            $modelHouse->state_id = Surveyquestion::STATE_ACTIVE;
                         

                            
                            if (! ($flag = $modelHouse->save(false))) {
                                break;
                            }
                            $string = strpos($modelHouse->day, ",");
                            if (! empty($string)) {
                                $questionDays = explode(',', $modelHouse->day);
                                foreach ($questionDays as $questionDay) {
                                    
                                    $questionDayModel = new SurveyQuestionDay();
                                    $questionDayModel->day = date('Y-m-d', strtotime($modelPerson->start_date . ' +' . ($questionDay - 1) . ' day'));
                                    $questionDayModel->state_id = SurveyQuestionDay::STATE_ACTIVE;
                                    $questionDayModel->research_id = $modelPerson->id;
                                    $questionDayModel->survey_question_id = $modelHouse->id;
                                    $questionDayModel->created_on = date('Y-m-d');
                                    $questionDayModel->updated_on = date('Y-m-d');
                                    $questionDayModel->created_by_id = \Yii::$app->user->id;
                                    if (! ($flag = $questionDayModel->save(false))) {
                                        break;
                                    }
                                }
                            }
                            
                            if (isset($modelsRoom[$indexHouse]) && is_array($modelsRoom[$indexHouse])) {
                                foreach ($modelsRoom[$indexHouse] as $indexRoom => $modelRoom) {
                                    if (! empty($modelRoom->option)) {
                                        $modelRoom->question_id = $modelHouse->id;
                                        $modelRoom->state_id = Surveyquestionoption::STATE_ACTIVE;
                                        $modelRoom->type_id = $modelHouse->type_id;
                                        if (! ($flag = $modelRoom->save(false))) {
                                            break;
                                        }
                                    } else {
                                        $flag = true;
                                    }
                                }
                            }
                        }
                    }
                    
                    if ($flag) {
                        
                        $transaction->commit();
                        return $this->redirect([
                            'view-research',
                            'id' => $modelPerson->id
                        ]);
                    } else {
                        $transaction->rollBack();
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                }
            }
        }
        
        return $this->render('add-question', [
            'modelPerson' => $modelPerson,
            'id' => $id,
            'modelsHouse' => (empty($modelsHouse)) ? [
                new Surveyquestion()
            ] : $modelsHouse,
            'modelsRoom' => (empty($modelsRoom)) ? [
                [
                    new Surveyquestionoption()
                ]
            ] : $modelsRoom
        ]);
    }

    /**
     * calling this function when participant/user
     * add survey
     *
     * @param
     *            $id
     * @return \yii\web\Response|string
     */
    public function actionCreateByParticipant($id = null)
    {
        if (! empty($id)) {
            $research = ResearchProgram::findOne($id);
            $id = isset($research->id) ? $research->id : '';
        }
        $modelPerson = new ResearchProgram();
        $modelsHouse = [
            new Surveyquestion([
                'scenario' => 'add-question'
            ])
        ];
        $modelsRoom = [
            [
                new Surveyquestionoption()
            ]
        ];
        $post = Yii::$app->request->post();

        if ($modelPerson->load(Yii::$app->request->post())) {

            $modelsHouse = Surveyquestion::createMultiple(Surveyquestion::classname());
            Model::loadMultiple($modelsHouse, Yii::$app->request->post());

            // validate person and houses models
            $valid = $modelPerson->validate();
            $valid = Model::validateMultiple($modelsHouse) && $valid;
            if (isset($_POST['Surveyquestionoption'][0][0])) {
                foreach ($_POST['Surveyquestionoption'] as $indexHouse => $rooms) {
                    foreach ($rooms as $indexRoom => $room) {
                        $data['Surveyquestionoption'] = $room;
                        $modelRoom = new Surveyquestionoption();
                        $modelRoom->load($data);
                        $modelsRoom[$indexHouse][$indexRoom] = $modelRoom;
                        $valid = $modelRoom->validate();
                    }
                }
            }
            $post = Yii::$app->request->post();
            if (! empty($post)) {
                $valid = true;
            }

            if ($valid) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $modelPerson = ResearchProgram::find()->where([
                        'id' => $post['ResearchProgram']['id']
                    ])->one();
                    if (! empty($modelPerson)) {
                        $flag = true;
                    } else {
                        $flag = false;
                    }
                    if ($flag) {

                        foreach ($modelsHouse as $indexHouse => $modelHouse) {

                            if ($flag === false) {
                                break;
                            }
                            $questionDays = implode(',', $modelHouse->day);
                            $modelHouse->day = $questionDays;
                            $modelHouse->research_id = $modelPerson->id;
                            $modelHouse->state_id = Surveyquestion::STATE_ACTIVE;
                            if (! ($flag = $modelHouse->save(false))) {
                                break;
                            }
                            $string = strpos($modelHouse->day, ",");
                            if (! empty($string)) {
                                $questionDays = explode(',', $modelHouse->day);
                                foreach ($questionDays as $questionDay) {

                                    $questionDayModel = new SurveyQuestionDay();
                                    $questionDayModel->day = date('Y-m-d', strtotime($modelPerson->start_date . ' +' . ($questionDay - 1) . ' day'));
                                    $questionDayModel->state_id = SurveyQuestionDay::STATE_ACTIVE;
                                    $questionDayModel->research_id = $modelPerson->id;
                                    $questionDayModel->survey_question_id = $modelHouse->id;
                                    $questionDayModel->created_on = date('Y-m-d');
                                    $questionDayModel->updated_on = date('Y-m-d');
                                    $questionDayModel->created_by_id = \Yii::$app->user->id;
                                    if (! ($flag = $questionDayModel->save(false))) {
                                        break;
                                    }
                                }
                            }

                            if (isset($modelsRoom[$indexHouse]) && is_array($modelsRoom[$indexHouse])) {
                                foreach ($modelsRoom[$indexHouse] as $indexRoom => $modelRoom) {
                                    if (! empty($modelRoom->option)) {
                                        $modelRoom->question_id = $modelHouse->id;
                                        $modelRoom->state_id = Surveyquestionoption::STATE_ACTIVE;
                                        $modelRoom->type_id = $modelHouse->type_id;
                                        if (! ($flag = $modelRoom->save(false))) {
                                            break;
                                        }
                                    } else {
                                        $flag = true;
                                    }
                                }
                            }
                        }
                    }

                    if ($flag) {

                        $transaction->commit();
                        return $this->redirect([
                            'view-research',
                            'id' => $modelPerson->id
                        ]);
                    } else {
                        $transaction->rollBack();
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                }
            }
        }

        return $this->render('add-question-participant', [
            'modelPerson' => $modelPerson,
            'id' => $id,
            'modelsHouse' => (empty($modelsHouse)) ? [
                new Surveyquestion()
            ] : $modelsHouse,
            'modelsRoom' => (empty($modelsRoom)) ? [
                [
                    new Surveyquestionoption()
                ]
            ] : $modelsRoom
        ]);
    }

    public function actionGalleryImages()
    {
        $query = File::find()->where([
            'created_by_id' => \Yii::$app->user->id,
            'state_id' => File::STATE_ACTIVE
        ]);
        if (! empty($query)) {
            $dataProvider = new ActiveDataProvider([
                'query' => $query
            ]);
            return $this->render('gallery', [
                'dataProvider' => $dataProvider
            ]);
        }
    }

    public function actionUpdateResearch($id)
    {
        $research = $this->findModel($id);
        $researchQuestion = $research->questions;
        $researchOptions = [];
        $oldOptions = [];
        $oldDays = [];
        $Days = [];
        if (! empty($researchQuestion)) {
            foreach ($researchQuestion as $indexQuestion => $modelQuestion) {
                $Days = $modelQuestion->day;
                $Days = explode(',', $Days);
                $options = $modelQuestion->options;
                $researchDays = $modelQuestion->surveyQuestionDay;
                $researchOptions[$indexQuestion] = $options;
                if (empty($options)) {
                    $researchOptions[$indexQuestion] = [
                        new Surveyquestionoption()
                    ];
                }
                $oldOptions = ArrayHelper::merge(ArrayHelper::index($options, 'id'), $oldOptions);
                $oldDays = ArrayHelper::merge(ArrayHelper::index($researchDays, 'id'), $oldDays);
            }
        }

        if ($research->load(Yii::$app->request->post())) {

            // reset
            $researchOptions = [];

            $oldHouseIDs = ArrayHelper::map($researchQuestion, 'id', 'id');
            $researchQuestion = Surveyquestion::createMultiple(Surveyquestion::classname(), $researchQuestion);
            Model::loadMultiple($researchQuestion, Yii::$app->request->post());
            $deletedHouseIDs = array_diff($oldHouseIDs, array_filter(ArrayHelper::map($researchQuestion, 'id', 'id')));

            // validate person and houses models
            $valid = $research->validate();

            $valid = Model::validateMultiple($researchQuestion) && $valid;
            $optionsIDs = [];

            if (isset($_POST['Surveyquestionoption'][0][0])) {
                foreach ($_POST['Surveyquestionoption'] as $indexQuestion => $options) {
                    $optionsIDs = ArrayHelper::merge($optionsIDs, array_filter(ArrayHelper::getColumn($options, 'id')));
                    foreach ($options as $indexRoom => $room) {
                        $data['Surveyquestionoption'] = $room;
                        $modelRoom = (isset($room['id']) && isset($oldOptions[$room['id']])) ? $oldOptions[$room['id']] : new Surveyquestionoption();
                        $modelRoom->load($data);
                        $researchOptions[$indexQuestion][$indexRoom] = $modelRoom;
                        $valid = $modelRoom->validate();
                    }
                }
            }
            $oldOptionsIDs = ArrayHelper::getColumn($oldOptions, 'id');
            $deletedRoomsIDs = array_diff($oldOptionsIDs, $optionsIDs);
            $deletedDaysIDs = array_diff($oldDays, $oldDays);
            $post = Yii::$app->request->post();
            if (! empty($post)) {
                $valid = true;
            }

            if ($valid) {
                $transaction = Yii::$app->db->beginTransaction();
                if ($flag = $research->save(false)) {

                    if (! empty($deletedRoomsIDs)) {
                        Surveyquestionoption::deleteAll([
                            'id' => $deletedRoomsIDs
                        ]);
                    }

                    if (! empty($deletedHouseIDs)) {
                        SurveyQuestionDay::deleteAll([
                            'survey_question_id' => $deletedHouseIDs
                        ]);
                        Surveyquestion::deleteAll([
                            'id' => $deletedHouseIDs
                        ]);
                    }

                    if (! empty($deletedDaysIDs)) {
                        SurveyQuestionDay::deleteAll([
                            'id' => $deletedDaysIDs
                        ]);
                    }

                    foreach ($researchQuestion as $indexQuestion => $modelQuestion) {

                        if ($flag === false) {
                            break;
                        }

                        $questionDays = implode(',', $modelQuestion->day);
                        $modelQuestion->day = $questionDays;
                        $modelQuestion->research_id = $research->id;
                        $modelQuestion->state_id = Surveyquestion::STATE_ACTIVE;
                        if (! ($flag = $modelQuestion->save(false))) {
                            break;
                        }

                        $questionDays = explode(',', $modelQuestion->day);
                        foreach ($questionDays as $questionDay) {
                            $questionDayModel = new SurveyQuestionDay();
                            $questionDayModel->day = date('Y-m-d', strtotime($research->start_date . ' +' . ($questionDay - 1) . ' day'));
                            $questionDayModel->state_id = SurveyQuestionDay::STATE_ACTIVE;
                            $questionDayModel->research_id = $research->id;
                            $questionDayModel->survey_question_id = $modelQuestion->id;
                            $questionDayModel->created_on = date('Y-m-d');
                            $questionDayModel->updated_on = date('Y-m-d');
                            $questionDayModel->created_by_id = \Yii::$app->user->id;
                            if (! ($flag = $questionDayModel->save(false))) {

                                break;
                            }
                        }

                        if (isset($researchOptions[$indexQuestion]) && is_array($researchOptions[$indexQuestion])) {
                            foreach ($researchOptions[$indexQuestion] as $indexRoom => $modelRoom) {
                                if (! empty($modelRoom->option)) {
                                    $modelRoom->question_id = $modelQuestion->id;
                                    $modelRoom->state_id = Surveyquestionoption::STATE_ACTIVE;
                                    $modelRoom->type_id = $modelQuestion->type_id;
                                    if (! ($flag = $modelRoom->save(false))) {
                                        break;
                                    }
                                } else {
                                    $flag = true;
                                }
                            }
                        }
                    }
                }

                if ($flag) {
                    $transaction->commit();
                    return $this->redirect([
                        'view-research',
                        'id' => $research->id
                    ]);
                } else {
                    $transaction->rollBack();
                }
            }
        }
        if (User::isUser()) {
            return $this->render('update-participant-question', [
                'id' => $research->id,
                'Days' => ! empty($Days) ? $Days : [],
                'research' => $research,
                'researchQuestion' => (empty($researchQuestion)) ? [
                    new Surveyquestion()
                ] : $researchQuestion,
                'researchOptions' => (empty($researchOptions)) ? [
                    [
                        new Surveyquestionoption()
                    ]
                ] : $researchOptions
            ]);
        }
        return $this->render('update-question', [
            'id' => $research->id,
            'Days' => ! empty($Days) ? $Days : [],
            'research' => $research,
            'researchQuestion' => (empty($researchQuestion)) ? [
                new Surveyquestion()
            ] : $researchQuestion,
            'researchOptions' => (empty($researchOptions)) ? [
                [
                    new Surveyquestionoption()
                ]
            ] : $researchOptions
        ]);
    }

    public function actionCloneResearch($id = null)
    {
        $research = $this->findModel($id);
        // echo "<pre>"; print_r($research);die();
        $researchQuestion = $research->questions;
        $researchOptions = [];
        $oldOptions = [];
        $oldDays = [];
        $Days = [];
        if (! empty($researchQuestion)) {
            foreach ($researchQuestion as $indexQuestion => $modelQuestion) {
                $Days = $modelQuestion->day;
                // echo $Days;die();
                $Days = explode(',', $Days);

                $options = $modelQuestion->options;
                $researchDays = $modelQuestion->surveyQuestionDay;
                $researchOptions[$indexQuestion] = $options;
                if (empty($options)) {
                    $researchOptions[$indexQuestion] = [
                        new Surveyquestionoption()
                    ];
                }
                $oldOptions = ArrayHelper::merge(ArrayHelper::index($options, 'id'), $oldOptions);
                $oldDays = ArrayHelper::merge(ArrayHelper::index($researchDays, 'id'), $oldDays);
            }
        }

        if ($research->load(Yii::$app->request->post())) {
            // reset
            $researchOptions = [];

            $oldHouseIDs = ArrayHelper::map($researchQuestion, 'id', 'id');
            $researchQuestion = Surveyquestion::createMultiple(Surveyquestion::classname(), $researchQuestion);
            Model::loadMultiple($researchQuestion, Yii::$app->request->post());
            $deletedHouseIDs = array_diff($oldHouseIDs, array_filter(ArrayHelper::map($researchQuestion, 'id', 'id')));

            // validate person and houses models
            $valid = $research->validate();

            $valid = Model::validateMultiple($researchQuestion) && $valid;
            $optionsIDs = [];

            if (isset($_POST['Surveyquestionoption'][0][0])) {
                foreach ($_POST['Surveyquestionoption'] as $indexQuestion => $options) {
                    $optionsIDs = ArrayHelper::merge($optionsIDs, array_filter(ArrayHelper::getColumn($options, 'id')));
                    foreach ($options as $indexRoom => $room) {
                        $data['Surveyquestionoption'] = $room;
                        $modelRoom = (isset($room['id']) && isset($oldOptions[$room['id']])) ? $oldOptions[$room['id']] : new Surveyquestionoption();
                        $modelRoom->load($data);
                        $researchOptions[$indexQuestion][$indexRoom] = $modelRoom;
                        $valid = $modelRoom->validate();
                    }
                }
            }
            $oldOptionsIDs = ArrayHelper::getColumn($oldOptions, 'id');
            $deletedRoomsIDs = array_diff($oldOptionsIDs, $optionsIDs);
            $deletedDaysIDs = array_diff($oldDays, $oldDays);
            $post = Yii::$app->request->post();
            if (! empty($post)) {
                $valid = true;
            }

            if ($valid) {
                $transaction = Yii::$app->db->beginTransaction();
                if ($flag = $research->save(false)) {

                    if (! empty($deletedRoomsIDs)) {
                        Surveyquestionoption::deleteAll([
                            'id' => $deletedRoomsIDs
                        ]);
                    }

                    if (! empty($deletedHouseIDs)) {
                        SurveyQuestionDay::deleteAll([
                            'survey_question_id' => $deletedHouseIDs
                        ]);
                        Surveyquestion::deleteAll([
                            'id' => $deletedHouseIDs
                        ]);
                    }

                    if (! empty($deletedDaysIDs)) {
                        SurveyQuestionDay::deleteAll([
                            'id' => $deletedDaysIDs
                        ]);
                    }

                    foreach ($researchQuestion as $indexQuestion => $modelQuestion) {

                        if ($flag === false) {
                            break;
                        }

                        $questionDays = implode(',', $modelQuestion->day);
                        $modelQuestion->day = $questionDays;
                        $modelQuestion->research_id = $research->id;
                        $modelQuestion->state_id = Surveyquestion::STATE_ACTIVE;
                        if (! ($flag = $modelQuestion->save(false))) {
                            break;
                        }

                        $questionDays = explode(',', $modelQuestion->day);
                        foreach ($questionDays as $questionDay) {
                            $questionDayModel = new SurveyQuestionDay();
                            $questionDayModel->day = date('Y-m-d', strtotime($research->start_date . ' +' . ($questionDay - 1) . ' day'));
                            $questionDayModel->state_id = SurveyQuestionDay::STATE_ACTIVE;
                            $questionDayModel->research_id = $research->id;
                            $questionDayModel->survey_question_id = $modelQuestion->id;
                            $questionDayModel->created_on = date('Y-m-d');
                            $questionDayModel->updated_on = date('Y-m-d');
                            $questionDayModel->created_by_id = \Yii::$app->user->id;
                            if (! ($flag = $questionDayModel->save(false))) {

                                break;
                            }
                        }

                        if (isset($researchOptions[$indexQuestion]) && is_array($researchOptions[$indexQuestion])) {
                            foreach ($researchOptions[$indexQuestion] as $indexRoom => $modelRoom) {
                                if (! empty($modelRoom->option)) {
                                    $modelRoom->question_id = $modelQuestion->id;
                                    $modelRoom->state_id = Surveyquestionoption::STATE_ACTIVE;
                                    $modelRoom->type_id = $modelQuestion->type_id;
                                    if (! ($flag = $modelRoom->save(false))) {
                                        break;
                                    }
                                } else {
                                    $flag = true;
                                }
                            }
                        }
                    }
                }

                if ($flag) {
                    $transaction->commit();
                    return $this->redirect([
                        'view-research',
                        'id' => $research->id
                    ]);
                } else {
                    $transaction->rollBack();
                }
            }
        }

        return $this->render('clone-question', [
            'id' => $research->id,
            'Days' => $Days,
            'research' => $research,
            'researchQuestion' => (empty($researchQuestion)) ? [
                new Surveyquestion()
            ] : $researchQuestion,
            'researchOptions' => (empty($researchOptions)) ? [
                [
                    new Surveyquestionoption()
                ]
            ] : $researchOptions
        ]);
    }

    /**
     * for participant
     * view survey by
     * assign code
     *
     * @return string
     */
    public function actionViewSurvey()
    {
        $model = new ResearchProgram();
        $post = \Yii::$app->request->bodyParams;
        if (! empty($post)) {

            $assign_code = $post['ResearchProgram']['assign_code'];
            $research = ResearchProgram::find()->where([
                'assign_code' => $assign_code
            ])->one();
            if (empty($research)) {
                \Yii::$app->session->setFlash('error', \Yii::t('app', 'No results found.'));
                return $this->render('viewsurveyform', [
                    'model' => $model
                ]);
            } else {

                $model = ResearchProgram::find()->where([
                    'id' => $research->id
                ])->one();

                $questionQuery = Surveyquestion::find()->where([
                    'research_id' => $model->id
                ]);

                $questionDataProvider = new ActiveDataProvider([
                    'query' => $questionQuery,
                    'pagination' => [
                        'pageSize' => 10
                    ]
                ]);

                return $this->render('view-participant-research', [
                    'model' => $model,
                    'questionDataProvider' => $questionDataProvider
                ]);
            }
        }
        return $this->render('viewsurveyform', [
            'model' => $model
        ]);
    }

    public function actionSaveAnswer()
      {
        $post = \Yii::$app->request->post();
         // echo "<pre>";print_r($post);die();
        $flag = false;
        if (! empty($post)) {
// print_r($post['SurveyAnswer']['question_id']);

            foreach ($post['SurveyAnswer']['question_id'] as $ques) {
                // $sum = array_sum($post['SurveyAnswerOption']['option_id']);
                // if ($sum == 0) {
                //     \Yii::$app->session->setFlash('success', \Yii::t('app', 'You need to select one option in every question.'));
                //     return $this->redirect([
                //         'current-survey'
                //     ]);
                // } 

         // echo "<pre>";print_r($ques);die();

                $surveyAnswer = new SurveyAnswer();
                $surveyAnswer->research_id = $post['SurveyAnswer']['research_id'];
                $surveyAnswer->question_id = $ques;
               // print_r($surveyAnswer->question_id);
                $questions = Surveyquestion::find()->where([
                    'id' => $ques
                ])->one();
                // echo "<pre>";print_r($questions);die();
                if (! empty($questions)) {
                    $surveyAnswer->type_id = $questions->type_id;
                     if( $surveyAnswer->type_id ==3) {
                $surveyAnswer->answer = $post['SurveyAnswer']['answer'];
                }
                    $surveyAnswer->state_id = SurveyAnswer::STATE_ACTIVE;
                    $surveyAnswer->created_by_id = \Yii::$app->user->id;
                  
                    if ($surveyAnswer->save()) {
                        $last_id = $surveyAnswer->id;
                         foreach ($post['SurveyAnswerOption']['option_id'] as $options) {
                          foreach ($options as $key => $value){
                            if ($options != 0) {
                                $surveyAnswerOption = new SurveyAnswerOption();
                                $surveyAnswerOption->answer_id = $last_id;
                                $surveyAnswerOption->option_id = $value;
                               
                                $surveyAnswerOption->type_id = $questions->type_id;
                                $surveyAnswerOption->state_id = SurveyAnswerOption::STATE_ACTIVE;
      
                                if ($surveyAnswerOption->save()) {
                                    $flag = true;
                                    \Yii::$app->session->setFlash('success', \Yii::t('app', 'Your answers saved successfully.'));
                                }
                            }
                        }
                    }
                 }
                }
            }
        }
        if ($flag == true) {
            return $this->redirect([
                'current-survey-answer'
            ]);
        }
    }

// for current survey answer

public function actionCurrentSurveyAnswer(){
	 	
	 	
              $id=\Yii::$app->user->id;
          $query = SurveyAnswerOption::find()->select('option_id')
            ->distinct()
            ->where([
            'created_by_id' =>$id
        ])->all();

             // echo "<pre>";print_r($query);die();
           $surveyAnswer=SurveyAnswer::find()->where(['created_by_id'=>$id])->one();
           $research_id=$surveyAnswer->research_id;
           $research = ResearchProgram::find()->where([
                'id' =>$research_id
            ])->one();
        if (! empty($research)) {
            $answers = SurveyAnswer::find()->where([
                  'research_id' =>$research_id,
                  'created_by_id' => \Yii::$app->user->id,
            
                    ])->all();
             // echo "<pre>";print_r($answers);die();
        if (! empty($answers)) {
                    $ans = [];
                    foreach ($answers as $answer) {
                    $ans[] = $answer->asJson(true);
                   // echo "<pre>";  print_r($ans);die();
                    }    
                  return $this->render('current-survey-answer',[
                  				'answer'=>$ans,
                  				'model'=>$research,
                  			
                      ]);
                } else {
                    \Yii::$app->session->setFlash('error', \Yii::t('app', 'No Answer Submitted yet.'));
                }
            } else {
                \Yii::$app->session->setFlash('error', \Yii::t('app', 'No research found.'));
            }
        
    }

public function actionSurveyAnswers($id=null){

           $surveyAnswer=SurveyAnswer::find()->where(['created_by_id'=>$id])->one();
           $research_id=$surveyAnswer->research_id;
           $research = ResearchProgram::find()->where([
                'id' =>$research_id
            ])->one();
          
        if (! empty($research)) {
            $answers = SurveyAnswer::find()->where([
                  'research_id' =>$research_id,
                  'created_by_id' => $id
                    ])->all();
        if (! empty($answers)) {
                    $ans = [];
                    foreach ($answers as $answer) {
                    $ans[] = $answer->asJson(true);
                    }
                   
                  return $this->render('current-survey-answer',[
                  				'answer'=>$ans,
                  				'model'=>$research
                      ]);
                } else {
                    \Yii::$app->session->setFlash('error', \Yii::t('app', 'No Answer Submitted yet.'));
                }
            } else {
                \Yii::$app->session->setFlash('error', \Yii::t('app', 'No research found.'));
            }
        
    
}

    /**
     * participants current survey
     * if not added to any survey
     * participant can add survey by using assign code
     *
     * @return string|\yii\web\Response
     */
    public function actionCurrentSurvey()
    {
        $userProgram = Userprogram::find()->where([
            'state_id' => Userprogram::STATE_ACTIVE,
            'created_by_id' => \Yii::$app->user->id
        ])->one();
        if (! empty($userProgram)) {
            $research_id = $userProgram->research_id;
            $model = ResearchProgram::find()->where([
                'id' => $research_id,
                'state_id' => ResearchProgram::STATE_ACTIVE
            ])->one();
           // echo "<pre>"; print_r($model);die();
            return $this->render('participant-current-survey', [
                'model' => $model
            ]);
        } else {
            $post = \yii::$app->request->post();
            if (! empty($post)) {
                $assign_code = $post['ResearchProgram']['assign_code'];
                $research = ResearchProgram::find()->where([
                    'assign_code' => $assign_code,
                    'state_id' => ResearchProgram::STATE_ACTIVE
                ])
                    ->andwhere([
                    '>',
                    'end_date',
                    date('Y-m-d')
                ])
                    ->one();
                if (! empty($research)) {

                    $newModel = new Userprogram();
                    $newModel->research_id = $research->id;
                    $newModel->state_id = Userprogram::STATE_ACTIVE;
                    $newModel->created_by_id = \Yii::$app->user->id;

                    if ($newModel->save()) {
                        \Yii::$app->session->setFlash('success', \Yii::t('app', 'Survey Added to your list successfully.'));
                    }
                    return $this->redirect([
                        'current-survey'
                    ]);
                } else {
                    $model = new ResearchProgram();
                    \Yii::$app->session->setFlash('error', \Yii::t('app', 'No results found. You may entered an invalid assign code.'));
                    return $this->redirect([
                        'current-survey'
                    ]);
                }
            } else {
                $model = new ResearchProgram();
                return $this->render('currentsurvey', [
                    'model' => $model
                ]);
            }
        }
    }

    /**
     * $id = research_id
     * view survey related questions to participated
     * show qustion todays date questions only to participant
     */
    public function actionViewCurrentSurvey($id)
    {
      // echo $id;die();
        $answers = Surveyquestion::getAnswerByResaerch($id);

        if ($answers == false) {
            \Yii::$app->session->setFlash('error', \Yii::t('app', 'You have already submitted todays questions.'));
            return $this->redirect([
                'current-survey'
            ]);
        }
        $surveyAnswer = new SurveyAnswer();
        $surveyAnswerOption = new SurveyAnswerOption();
        $model = ResearchProgram::find()->where([
            'id' => $id
        ])->one();

        $questions = Surveyquestion::find()->alias('sq')
            ->where([
            'sq.research_id' => $id
        ])
        //     // ->joinWith('surveyQuestionDay as sQd')
        //     ->andWhere([
        //     'sQd.day' => date('Y-m-d')
        // ])
            ->all();
     // echo "<pre>"; print_r($questions);die();

        return $this->render('view-current-survey', [
            'model' => $model,
            'surveyAnswer' => $surveyAnswer,
            'questions' => $questions,
            'answerOption' => $surveyAnswerOption
        ]);
    }

    /**
     * notification shown to user
     * as it added in notification table
     */
    public function actionNotification()
    {
        $model = Notification::find()->where([
            'to_user_id' => \Yii::$app->user->id
        ])->orderBy('id desc');
        if (! empty($model)) {
            $dataProvider = new ActiveDataProvider([
                'query' => $model,
                'pagination' => ([
                    'pageSize' => 10
                ])
            ]);
            return $this->render('notification', [
                'dataProvider' => $dataProvider
            ]);
        }
        return $this->render('notification');
    }

    /**
     * Get all the details of a question
     *
     * @return json encoded response of question model
     */
    public function actionGetQuestionDetails($id)
    {
        $data = [];
        $data['status'] = false;
        if ($id) {
            $question = QuestionBank::findOne($id);
            if (! empty($question)) {
                $data['status'] = true;
                $data['question'] = $question->asJson(true);
            } else {
                $data['error'] = "Question not found";
            }
        } else {
            $data['error'] = "Invalid data";
        }
        echo json_encode($data);
    }

    public function actionDeleteGalleryImage()
    {
        $get = \Yii::$app->request->get();
        if (isset($get['id'])) {
            $id = $get['id'];
        }
        if (! empty($id)) {
            $file = File::find()->where([
                'id' => $id,
                'created_by_id' => \Yii::$app->user->id
            ])->all();
        }
        if (! empty($file)) {
            foreach ($file as $files) {
                if ($files->state_id == File::STATE_ACTIVE) {
                    $files->state_id = File::STATE_DELETED;
                    if (! $files->save(false)) {
                        \Yii::$app->getSession()->setFlash('success', \Yii::t('app', 'Image deleted succesfully.'));
                    }
                }
            }
        }
        return $this->redirect([
            'gallery-images'
        ]);
    }

    /**
     * Updates an existing ResearchProgram model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $searchModel = new ResearchProgramSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->andWhere([
            'created_by_id' => \Yii::$app->user->id
        ]);
        $post = \yii::$app->request->post();
        if (\yii::$app->request->isAjax && $model->load($post)) {
            \yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return TActiveForm::validate($model);
        }
        if ($model->load($post) && $model->save()) {
            return $this->redirect([
                'view',
                'id' => $model->id
            ]);
        }
        if (User::isAdmin()) {
            $this->updateMenuItems($model);
            return $this->render('update', [
                'model' => $model
            ]);
        } else {
            return $this->render('programlist', [
                'model' => $model,
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
                '#' => 'updateModal'
            ]);
        }
    }

    /**
     * Deletes an existing ResearchProgram model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        $model->delete();
        if (User::isAdmin()) {
            return $this->redirect([
                'index'
            ]);
        } else {
            return $this->redirect([
                'research-list'
            ]);
        }
    }

    /**
     * Finds the ResearchProgram model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param integer $id
     * @return ResearchProgram the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id, $accessCheck = true)
    {
        if (($model = ResearchProgram::findOne($id)) !== null) {

            if ($accessCheck && ! ($model->isAllowed()))
                throw new HttpException(403, Yii::t('app', 'You are not allowed to access this page.'));

            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    protected function updateMenuItems($model = null)
    {
        switch (\Yii::$app->controller->action->id) {

            case 'add':
                {
                    $this->menu['manage'] = array(
                        'label' => '<span class="glyphicon glyphicon-list"></span>',
                        'title' => Yii::t('app', 'Manage'),
                        'url' => [
                            'index'
                        ]
                        // 'visible' => User::isAdmin ()
                    );
                }
                break;
            case 'index':
                {
                    /*
                     * $this->menu ['add'] = array (
                     * 'label' => '<span class="glyphicon glyphicon-plus"></span>',
                     * 'title' => Yii::t ( 'app', 'Add' ),
                     * 'url' => [
                     * 'add'
                     * ],
                     * // 'visible' => User::isAdmin ()
                     * );
                     */
                }
                break;
            case 'update':
                {
                    $this->menu['manage'] = array(
                        'label' => '<span class="glyphicon glyphicon-list"></span>',
                        'title' => Yii::t('app', 'Manage'),
                        'url' => [
                            'index'
                        ]
                        // 'visible' => User::isAdmin ()
                    );
                }
                break;
            default:
            case 'view':
                {
                    $this->menu['manage'] = array(
                        'label' => '<span class="glyphicon glyphicon-list"></span>',
                        'title' => Yii::t('app', 'Manage'),
                        'url' => [
                            'index'
                        ]
                        // 'visible' => User::isAdmin ()
                    );
                    if ($model != null) {
                        $this->menu['update'] = array(
                            'label' => '<span class="glyphicon glyphicon-pencil"></span>',
                            'title' => Yii::t('app', 'Update'),
                            'url' => [
                                'update',
                                'id' => $model->id
                            ]
                            // 'visible' => User::isAdmin ()
                        );
                        $this->menu['delete'] = array(
                            'label' => '<span class="glyphicon glyphicon-trash"></span>',
                            'title' => Yii::t('app', 'Delete'),
                            'url' => [
                                'delete',
                                'id' => $model->id
                            ]
                            // 'visible' => User::isAdmin ()
                        );
                    }
                }
        }
    }
}
