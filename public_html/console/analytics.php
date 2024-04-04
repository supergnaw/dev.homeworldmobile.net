<?php

$html .= "
    <script src='https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.min.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof marked === 'undefined') {
                document.write('<script src=\"js/chart.min.js\">\/script>');
            };          
        }, false);
    </script>";