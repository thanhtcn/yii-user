

# User data #
@param user id not required (default return current user)<br>
@return user object or false<br>
<pre><code>$userObject = Yii::app()-&gt;getModule('user')-&gt;user(); // Public Properties: id, username, email, createtime, lastvisit and profile relation<br>
$profileObject = $userObject-&gt;profile;               // Your custom fields (Default: firstname, lastname, about)<br>
</code></pre>

<h1>Is admin</h1>
@return true or false<br>
<pre><code>Yii::app()-&gt;getModule('user')-&gt;isAdmin()<br>
</code></pre>

<h1>Access control</h1>
@return array superusers names<br>
<pre><code>Yii::app()-&gt;getModule('user')-&gt;getAdmins()<br>
</code></pre>

<h2>Example controller</h2>
<pre><code>	public function accessRules()<br>
	{<br>
		return array(<br>
			array('allow',  // allow all users to perform 'index' and 'view' actions<br>
				'actions'=&gt;array('index','view'),<br>
				'users'=&gt;array('*'),<br>
			),<br>
			array('allow', // allow authenticated user to perform 'create' and 'update' actions<br>
				'actions'=&gt;array('create','update'),<br>
				'users'=&gt;array('@'),<br>
			),<br>
			array('allow', // allow admin user to perform 'admin' and 'delete' actions<br>
				'actions'=&gt;array('admin','delete'),<br>
				'users'=&gt;Yii::app()-&gt;getModule('user')-&gt;getAdmins(),<br>
			),<br>
			array('deny',  // deny all users<br>
				'users'=&gt;array('*'),<br>
			),<br>
		);<br>
	}<br>
</code></pre>


See <a href='FieldWidget.md'>FieldWidget</a>