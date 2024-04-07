<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

return "
<form id='user-logout' method='post' style='text-align:center;' action='/console/'>
    <input type='hidden' name='action' value='logout'>
    <div class='hw-nav col7'>
        <div class='hide'></div>
        <div class='hide'></div>
        <div class='hide'></div>
        <a href='#' id='form-btn' onclick='submit_form(\"user-logout\")'>
            <div id='form-submit-btn-text' class='btn'>Log Out</div>
        </a>
    </div>
</form>
";