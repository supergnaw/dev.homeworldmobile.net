<?php

declare(strict_types=1);

return "
<h3 style='text-align:center;'>Login</h3>
<p style='text-align:center;'>You must login to access the requested information.</p>
<form id='user-login' method='post' style='text-align:center;'>
    <div>
        <label for='username'>Username</label><br>
        <input type='text' name='username'>
    </div>
    <div>
        <label for='password'>Password</label><br>
        <input type='password' name='password'>
    </div>
    <input type='hidden' name='action' value='login'>
    <div class='hw-nav col7'>
        <div class='hide'></div>
        <div class='hide'></div>
        <div class='hide'></div>
        <a href='#' id='form-btn' onclick='submit_form(\"user-login\")'><div id='form-btn-txt' class='btn'>Login</div></a>
    </div>
</form>
<hr>
<h3 style='text-align:center;'>New User Registration</h3>
<form id='user-register' method='post' style='text-align:center;'>
    <div class='col7'>
        <label for='username-register'>Username</label><br>
        <input type='text' id='username-register' name='username'>
    </div>
    <div>
        <label for='email-register'>E-Mail</label><br>
        <input type='text' id='email-register' name='email'>
    </div>
    <div>
        <label for='password-register'>Password</label><br>
        <input type='password' id='password-register' name='password'>
    </div>
    <div>
        <label for='password_confirm-register'>Confirm Password</label><br>
        <input type='password' id='password_confirm-register' name='password_confirm'>
    </div>
    <input type='hidden' name='action' value='register'>
    <div class='hw-nav col7'>
        <div class='hide'></div>
        <div class='hide'></div>
        <div class='hide'></div>
        <a href='#' id='form-btn' onclick='submit_form(\"user-register\")'><div id='form-btn-txt' class='btn'>Register</div></a>
    </div>
</form>";
