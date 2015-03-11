<link rel='shortcut icon' href='favicon.ico' />
<link href='style/base/home.css' rel='stylesheet' type='text/css' />

<!--Page by What.CD-->
<div id='head'>
</div>
<table class='layout' id='maincontent' style='border-top: solid 1px #3399FF; border-bottom: solid 1px #3399FF;'>
<tr>
    <td align='center' valign='middle'>
    <div id='logo'>
       <ul>
	  <li><a href='account.php'>Signup</a></li>
	  <li><a href='recover.php'>Recover</a></li>
        </ul>
     </div>

<form method='post' action='login.php?returnto={$returno}'>
<table class='layout'>
<tr>
    <td>Username&nbsp;</td>
    <td colspan='2'>
       <input type='text' name='uid' id='uid' value='{$user}' required='required' size='40' maxlength='40' pattern='[A-Za-z0-9_?]{1,20}' autofocus='autofocus' placeholder='Username' />
    </td>
</tr>
<tr>
    <td>Password&nbsp;</td>
    <td colspan='2'>
       <input type='password' name='pwd' id='pwd' required='required' size='40' maxlength='100' pattern='.{6,100}' placeholder='Password' />
    </td>
</tr>
<tr>
    <td></td>
    <td>
       <input type='checkbox' id='keeplogged' name='keeplogged' value='1' />
       <label for='keeplogged'>Remember me</label>
    </td>
    <td><input type='submit' name='login' value='Log in' class='submit' /></td>
</tr>
</table>
</form>

    </td>
</tr>
</table>

<div id='foot'>
    <span><a href='http://www.btiteam.org' target='_blank'>BtiTeam.org</a> | <a href='https://github.com/Yupy/BtiTracker-1.5.0' target='_blank'>GitHub.com</a> | <a href='#'>BtiTracker v1.5.0 by Yupy &amp; Btiteam</a></span>
</div>
