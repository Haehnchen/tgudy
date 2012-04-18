<?php
class user_userFunc{
    function addSpan($content,$conf) {
        $class = $conf['class'];
        
        if (preg_match('/class\="(.*'. $class .'.*)"/i', $content, $res)) {
          $content = preg_replace('@>(.*)</a>@i', '><span>$1</span></a>', $content);
        }

        return $content;
    }
} 