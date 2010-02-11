<?php

class UserController extends Controller
{
	const PAGE_SIZE=10;

	/**
	 * @var CActiveRecord the currently loaded data model instance.
	 */
	private $_model;

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
		);
	}
	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('index','view','registration','captcha','login', 'recovery', 'activation'),
				'users'=>array('*'),
			),
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('profile', 'edit', 'logout', 'changepassword'),
				'users'=>array('@'),
			),
			array('allow', // allow admin user to perform 'admin' and 'delete' actions
				'actions'=>array('admin','delete','create','update'),
				'users'=>User::getAdmins(),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}


	/**
	 * Declares class-based actions.
	 */
	public function actions()
	{
		return array(
			'captcha'=>array(
				'class'=>'CCaptchaAction',
				'backColor'=>0xFFFFFF,
			),
		);
	}
	

	
	
	/**
	 * Registration user
	 */
	public function actionRegistration() {
            $model = new RegistrationForm;
            $profile=new Profile;
		    if ($uid = Yii::app()->user->id) {
		    	$this->redirect(Yii::app()->homeUrl);
		    } else {
		    	if(isset($_POST['RegistrationForm'])) {
					$model->attributes=$_POST['RegistrationForm'];
					$profile->attributes=$_POST['Profile'];
					if($model->validate()&&$profile->validate())
					{
						$soucePassword = $model->password;
						$model->password=Yii::app()->User->encrypting($model->password);
						$model->verifyPassword=Yii::app()->User->encrypting($model->verifyPassword);
						$model->activkey=Yii::app()->User->encrypting(microtime().$model->password);
						$model->createtime=time();
						$model->lastvisit=((Yii::app()->User->autoLogin&&Yii::app()->User->loginNotActiv)?time():0);
						$model->superuser=0;
						$model->status=0;
						
						if ($model->save()) {
							//$model->save();
							$profile->user_id=$model->id;
							$profile->save();
							$headers="From: ".Yii::app()->params['adminEmail']."\r\nReply-To: ".Yii::app()->params['adminEmail'];
							$activation_url = 'http://' . $_SERVER['HTTP_HOST'].$this->createUrl('user/activation',array("activkey" => $model->activkey, "email" => $model->email));
							mail($model->email,"You registered from ".Yii::app()->name,"Please activate you account go to $activation_url.",$headers);
							if (Yii::app()->User->loginNotActiv) {
								if (Yii::app()->User->autoLogin) {
									$identity=new UserIdentity($model->username,$soucePassword);
									$identity->authenticate();
									Yii::app()->user->login($identity,0);
									$this->redirect(Yii::app()->User->returnUrl);
								} else {
									Yii::app()->user->setFlash('registration',Yii::t("user", "Thank you for your registration. Please check your email or login."));
									$this->refresh();
								}
							} else {
								Yii::app()->user->setFlash('registration',Yii::t("user", "Thank you for your registration. Please check your email."));
								$this->refresh();
							}
						}
					}
				}
			    $this->render('registration',array('form'=>$model,'profile'=>$profile));
		    }
	}
	

	/**
	 * Displays the login page
	 */
	public function actionLogin()
	{
		$form=new UserLogin;
		// collect user input data
		if(isset($_POST['UserLogin']))
		{
			$form->attributes=$_POST['UserLogin'];
			// validate user input and redirect to previous page if valid
			if($form->validate()) {
				$lastVisit = User::model()->findByPk(Yii::app()->user->id);
				$lastVisit->lastvisit = time();
				$lastVisit->save();
				$this->redirect(Yii::app()->User->returnUrl);
			}
		}
		// display the login form
		$this->render('login',array('form'=>$form));
	}

	/**
	 * Logout the current user and redirect to returnLogoutUrl.
	 */
	public function actionLogout()
	{
		Yii::app()->user->logout();
		$this->redirect(Yii::app()->User->returnLogoutUrl);
	}
	
	/**
	 * Activation user account
	 */
	public function actionActivation () {
		$email = $_GET['email'];
		$activkey = $_GET['activkey'];
		if ($email&&$activkey) {
			$find = User::model()->findByAttributes(array('email'=>$email));
			if ($find->status) {
			    $this->render('message',array('title'=>Yii::t("user", "User activation"),'content'=>Yii::t("user", "You account is active.")));
			} elseif($find->activkey==$activkey) {
				$find->activkey = Yii::app()->User->encrypting(microtime());
				$find->status = 1;
				$find->save();
			    $this->render('message',array('title'=>Yii::t("user", "User activation"),'content'=>Yii::t("user", "You account is activated.")));
			} else {
			    $this->render('message',array('title'=>Yii::t("user", "User activation"),'content'=>Yii::t("user", "Incorrect activation URL.")));
			}
		} else {
			$this->render('message',array('title'=>Yii::t("user", "User activation"),'content'=>Yii::t("user", "Incorrect activation URL.")));
		}
	}
	
	/**
	 * Change password
	 */
	public function actionChangepassword() {
		$form = new UserChangePassword;
		if ($uid = Yii::app()->user->id) {
			if(isset($_POST['UserChangePassword'])) {
					$form->attributes=$_POST['UserChangePassword'];
					if($form->validate()) {
						$new_password = User::model()->findByPk(Yii::app()->user->id);
						$new_password->password = Yii::app()->User->encrypting($form->password);
						$new_password->save();
						Yii::app()->user->setFlash('profileMessage',Yii::t("user", "New password is saved."));
						$this->redirect(array("user/profile"));
					}
			} 
			$this->render('changepassword',array('form'=>$form));
	    }
	}
	
	
	/**
	 * Recovery password
	 */
	public function actionRecovery () {
		$form = new UserRecoveryForm;
		if ($uid = Yii::app()->user->id) {
		    	$this->redirect(Yii::app()->User->returnUrl);
		    } else {
				$email = $_GET['email'];
				$activkey = $_GET['activkey'];
				if ($email&&$activkey) {
					$form2 = new UserChangePassword;
		    		$find = User::model()->findByAttributes(array('email'=>$email));
		    		if($find->activkey==$activkey) {
			    		if(isset($_POST['UserChangePassword'])) {
							$form2->attributes=$_POST['UserChangePassword'];
							if($form2->validate()) {
								$find->password = Yii::app()->User->encrypting($form2->password);
								$find->activkey=Yii::app()->User->encrypting(microtime().$form2->password);
								$find->save();
								Yii::app()->user->setFlash('loginMessage',Yii::t("user", "New password is saved."));
								$this->redirect(array("user/login"));
							}
						} 
						$this->render('changepassword',array('form'=>$form2));
		    		} else {
		    			Yii::app()->user->setFlash('recoveryMessage',Yii::t("user", "Incorrect recovery link."));
						$this->redirect('http://' . $_SERVER['HTTP_HOST'].$this->createUrl('user/recovery'));
		    		}
		    	} else {
			    	if(isset($_POST['UserRecoveryForm'])) {
			    		$form->attributes=$_POST['UserRecoveryForm'];
			    		if($form->validate()) {
			    			$user = User::model()->findbyPk($form->user_id);
			    			$headers="From: ".Yii::app()->params['adminEmail']."\r\nReply-To: ".Yii::app()->params['adminEmail'];
							$activation_url = 'http://' . $_SERVER['HTTP_HOST'].$this->createUrl('user/recovery',array("activkey" => $user->activkey, "email" => $user->email));
							mail($user->email,"You have requested the password recovery site ".Yii::app()->name,"You have requested the password recovery site ".Yii::app()->name.". To receive a new password, go to $activation_url.",$headers);
			    			Yii::app()->user->setFlash('recoveryMessage',Yii::t("user", "Please check your email. An instructions was sent to your email address."));
			    			$this->refresh();
			    		}
			    	}
		    		$this->render('recovery',array('form'=>$form));
		    	}
		    }
	}

	/**
	 * Shows a particular model.
	 */
	public function actionProfile()
	{
		if (Yii::app()->user->id) {
			$model = $this->loadUser($uid = Yii::app()->user->id);
		    $this->render('profile',array(
		    	'model'=>$model,
				'profile'=>$model->profile,
		    ));
		}
		
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionEdit()
	{
		$model=User::model()->findByPk(Yii::app()->user->id);
		$profile=$model->profile;
		if(isset($_POST['User']))
		{
			$model->attributes=$_POST['User'];
			$profile->attributes=$_POST['Profile'];
			
			if($model->validate()&&$profile->validate()) {
				$model->save();
				$profile->save();
				Yii::app()->user->setFlash('profileMessage',Yii::t("user", "Changes is saved."));
				$this->redirect(array('profile','id'=>$model->id));
			}
		}

		$this->render('profile-edit',array(
			'model'=>$model,
			'profile'=>$profile,
		));
	}
	
	/**
	 * Displays a particular model.
	 */
	public function actionView()
	{
		$model = $this->loadModel();
		$this->render('view',array(
			'model'=>$model,
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$model=new User;
		$profile=new Profile;
		if(isset($_POST['User']))
		{
			$model->attributes=$_POST['User'];
			$model->activkey=Yii::app()->User->encrypting(microtime().$model->password);
			$model->createtime=time();
			$model->lastvisit=time();
			$profile->attributes=$_POST['Profile'];
			$profile->user_id=0;
			if($model->validate()&&$profile->validate()) {
				$model->password=Yii::app()->User->encrypting($model->password);
				if($model->save()) {
					$profile->user_id=$model->id;
					$profile->save();
				}
				$this->redirect(array('view','id'=>$model->id));
			}
		}

		$this->render('create',array(
			'model'=>$model,
			'profile'=>$profile,
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionUpdate()
	{
		$model=$this->loadModel();
		$profile=$model->profile;
		if(isset($_POST['User']))
		{
			$old_password = User::model()->findByPk($model->id);
			$model->attributes=$_POST['User'];
			
			if ($old_password->password!=$model->password)
				$model->password=Yii::app()->User->encrypting($model->password);
			
			$profile->attributes=$_POST['Profile'];
			
			if($model->validate()&&$profile->validate()) {
				$model->save();
				$profile->save();
				$this->redirect(array('view','id'=>$model->id));
			}
		}

		$this->render('update',array(
			'model'=>$model,
			'profile'=>$profile,
		));
	}


	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 */
	public function actionDelete()
	{
		if(Yii::app()->request->isPostRequest)
		{
			// we only allow deletion via POST request
			$model = $this->loadModel();
			$profile = Profile::model()->findByPk($model->id);
			$profile->delete();
			$model->delete();
			// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
			if(!isset($_POST['ajax']))
				$this->redirect(array('index'));
		}
		else
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex()
	{
		$dataProvider=new CActiveDataProvider('User', array(
			'pagination'=>array(
				'pageSize'=>self::PAGE_SIZE,
			),
		));

		$this->render('index',array(
			'dataProvider'=>$dataProvider,
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$dataProvider=new CActiveDataProvider('User', array(
			'pagination'=>array(
				'pageSize'=>self::PAGE_SIZE,
			),
		));

		$this->render('admin',array(
			'dataProvider'=>$dataProvider,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 */
	public function loadModel()
	{
		if($this->_model===null)
		{
			if(isset($_GET['id']))
				$this->_model=User::model()->findbyPk($_GET['id']);
			if($this->_model===null)
				throw new CHttpException(404,'The requested page does not exist.');
		}
		return $this->_model;
	}


	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer the primary key value. Defaults to null, meaning using the 'id' GET variable
	 */
	public function loadUser($id=null)
	{
		if($this->_model===null)
		{
			if($id!==null || isset($_GET['id']))
				$this->_model=User::model()->findbyPk($id!==null ? $id : $_GET['id']);
			if($this->_model===null)
				throw new CHttpException(404,'The requested page does not exist.');
		}
		return $this->_model;
	}
}
