<?php

namespace Framework;

/**
 * Class View
 * @package Framework
 * @SuppressWarnings(PHPMD.Superglobals)
 */
class View
{
    /**
     * Det the page and make the data available
     *
     * @param $thePage
     * @param array $data
     * @return false|string
     */
    public function getPage($thePage, $data = [])
    {
        extract($data);
        $theTemplate = $this->getTemplateFile($thePage);
        ob_start();
        include $theTemplate;
        $content = ob_get_contents();
        ob_clean();
        return $content;
    }

    /**
     * Sanitize the output to the browser. This should protect against XSS
     *
     * @param $text
     * @return mixed
     */
    public function outputSanitize($text)
    {
        $text = strip_tags($text);
        $text = urldecode($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $text = preg_replace_callback('/(&#|\\\)[x]([0-9a-f]+);?/iu', function ($m) {
            return chr(hexdec($m[2]));
        }, $text);
        $text = mb_convert_encoding($text, 'UTF-8');

        if ($text != $text) {
            $text = $this->outputSanitize($text);
        }

        $text = str_replace("\0", "", $text);
        $text = preg_replace('/((java|vb|live)script|mocha|feed|data|ftp|news|nntp|telnet|gopher|ws|wss|xmpp):(\w)*/iUu', '', $text);
        return $text;
    }

    /**
     * Get the template see if we have a compiled once else compile it
     *
     * @param $templateFile
     * @return string
     */
    public function getTemplateFile($templateFile)
    {
        $filePath = dirname(dirname(__FILE__))."/resources/views/";
        $filePath .= str_replace(".", "/", $templateFile);
        $filePath .= ".tpl.php";

        $fileHash = md5_file($filePath);
        $compiledTemplate = dirname(dirname(__FILE__))."/resources/compiled/".$fileHash.".tpl.php";
        if (!file_exists($compiledTemplate)) {
            $content = file_get_contents($filePath);
            $content = $this->compileTemplate($content);
            $fileHandle = fopen($compiledTemplate, "w+");
            fputs($fileHandle, $content);
        }
        return $compiledTemplate;
    }

    /**
     * Compile the template in a usable form
     *
     * @param $pageContent
     * @return string|string[]|null
     */
    public function compileTemplate($pageContent)
    {
        $pageContent = preg_replace('/{{(.*)}}/U', '<?php echo $this->outputSanitize(\1); ?>', $pageContent);
        $pageContent = preg_replace('/@include\((.*)\)/m', '<?php include($this->getTemplateFile(\1)) ?>', $pageContent);
        $pageContent = preg_replace('/@foreach\((.*)\)/m', '<?php foreach(\1) : ?>', $pageContent);
        $pageContent = preg_replace('/@endforeach/m', '<?php endforeach; ?>', $pageContent);
        $pageContent = preg_replace('/@if\((.*)\)/m', '<?php if(\1) : ?>', $pageContent);
        $pageContent = preg_replace('/@else/m', '<?php else : ?>', $pageContent);
        $pageContent = preg_replace('/@endif/m', '<?php endif; ?>', $pageContent);
        $pageContent = preg_replace('/@endforeach/m', '<?php endif; ?>', $pageContent);
        $pageContent = preg_replace('/@messagebag\((.*)\)/m', '<?php foreach($this->getFlashMessages(\1) AS $message)  : ?>', $pageContent);
        $pageContent = preg_replace('/@endmessagebag/m', '<?php endforeach; ?>', $pageContent);
        return $pageContent;
    }

    /**
     * Issue redirect
     *
     * @param $endpoint
     */
    public function redirect($endpoint)
    {
        header('Location: '.$endpoint);
        die();
    }

    /**
     * Set a message in the session cache to be read at the next possible point and then discarded
     *
     * @param $type
     * @param $message
     */
    public function setFlashMessage($type, $message)
    {
        if (is_array($message)) {
            array_map(function ($items) use ($type) {
                if (!is_array($items)) {
                    $_SESSION['message'][$type][] = $items;
                    return;
                }
                foreach ($items as $item) {
                    $_SESSION['message'][$type][] = $item;
                }
            }, $message);
            return;
        }
        $_SESSION['message'][$type][] = $message;
    }

    /**
     * Return and remove the flash messages
     *
     * @param $type
     * @return mixed
     */
    public function getFlashMessages($type)
    {
        $messages = [];
        if (isset($_SESSION['message'][$type])) {
            $messages = $_SESSION['message'][$type];
            $_SESSION['message'][$type] = [];
        }
        return $messages;
    }

    /**
     * Check if the user has been signed in
     *
     * @return bool|array
     */
    public function isSignedIn()
    {
        if (isset($_SESSION['login_details'])) {
            return $_SESSION['login_details'];
        }
        return false;
    }

    /**
     * Check if the user has been signed in
     *
     * @param $userDetails
     */
    public function setSignIn($userDetails)
    {
        if ($userDetails === false) {
            unset($_SESSION['login_details']);
            return;
        }
        $_SESSION['login_details'] = $userDetails;
    }
}
