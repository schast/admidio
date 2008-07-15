<?php
/******************************************************************************
 * Diese Klasse dient dazu Systemmails zu verschicken
 *
 * Copyright    : (c) 2004 - 2008 The Admidio Team
 * Homepage     : http://www.admidio.org
 * Module-Owner : Markus Fassbender
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 * 
 * Neben den Methoden der Elternklasse Email, stehen noch zusaetzlich
 * folgende Methoden zur Verfuegung:
 *
 * getMailText($sysmail_id, &$user)
 *                  - diese Methode liest den Mailtext aus der DB und ersetzt 
 *                    vorkommende Platzhalter durch den gewuenschten Inhalt
 *
 * setVariable($number, $value)
 *                  - hier kann der Inhalt fuer zusaetzliche Variablen gesetzt werden
 *
 * sendSystemMail($sysmail_id, &$user)
 *                  - diese Methode sendet eine Systemmail nachdem der Mailtext 
 *                    ausgelesen und Platzhalter ersetzt wurden
 *
 *****************************************************************************/

require_once(SERVER_PATH. "/adm_program/system/classes/email.php");
require_once(SERVER_PATH. "/adm_program/system/classes/text.php");

class SystemMail extends Email
{
    var $textObject;
    var $db;
    var $mailText;
    var $mailHeader;
    var $variables = array();   // speichert zusaetzliche Variablen fuer den Mailtext

    // Konstruktor
    function SystemMail(&$db)
    {
        $this->textObject = new Text($db);
        $this->Email();
    }
    
    // diese Methode liest den Mailtext aus der DB und ersetzt vorkommende Platzhalter durch den gewuenschten Inhalt
    // sysmail_id : eindeutige Bezeichnung der entsprechenden Systemmail, entspricht adm_texts.txt_name
    // user       : Benutzerobjekt, zu dem die Daten dann ausgelesen und in die entsprechenden Platzhalter gesetzt werden
    function getMailText($sysmail_id, &$user)
    {
        global $g_current_organization, $g_preferences;
    
        if($this->textObject->getValue("txt_name") != $sysmail_id)
        {
            $this->textObject->getText($sysmail_id);
        }
        
        $mailSrcText = $this->textObject->getValue("txt_text");
        
        // jetzt alle Variablen ersetzen
        $mailSrcText = preg_replace ("/%user_first_name%/", $user->getValue("Vorname"),  $mailSrcText);
        $mailSrcText = preg_replace ("/%user_last_name%/",  $user->getValue("Nachname"), $mailSrcText);
        $mailSrcText = preg_replace ("/%user_login_name%/", $user->getValue("usr_login_name"), $mailSrcText);
        $mailSrcText = preg_replace ("/%user_email%/", $user->getValue("E-Mail"),   $mailSrcText);
        $mailSrcText = preg_replace ("/%webmaster_email%/", $g_preferences['email_administrator'],  $mailSrcText);
        $mailSrcText = preg_replace ("/%organization_short_name%/", $g_current_organization->getValue("org_shortname"), $mailSrcText);
        $mailSrcText = preg_replace ("/%organization_long_name%/",  $g_current_organization->getValue("org_longname"), $mailSrcText);
        $mailSrcText = preg_replace ("/%organization_homepage%/",   $g_current_organization->getValue("org_homepage"), $mailSrcText);
        
        // zusaetzliche Variablen ersetzen
        for($i = 1; $i <= count($this->variables); $i++)
        {
            $mailSrcText = preg_replace ("/%variable".$i."%/", $this->variables[$i],  $mailSrcText);
        }
        
        // Betreff und Inhalt anhand von Kennzeichnungen splitten oder ggf. Default-Inhalte nehmen
        if(strpos($mailSrcText, "#Betreff#") !== false)
        {
            $this->mailHeader = trim(substr($mailSrcText, strpos($mailSrcText, "#Betreff#") + 9, strpos($mailSrcText, "#Inhalt#") - 9));
        }
        else
        {
            $this->mailHeader = "Systemmail von ". $g_current_organization->getValue("org_homepage");
        }
        
        if(strpos($mailSrcText, "#Inhalt#") !== false)
        {
            $this->mailText   = trim(substr($mailSrcText, strpos($mailSrcText, "#Inhalt#") + 8));
        }
        else
        {
            $this->mailText   = $mailSrcText;
        }

        return $this->mailText;
    }
    
    // die Methode setzt den Inhalt fuer spezielle Variablen
    function setVariable($number, $value)
    {
        $this->variables[$number] = $value;
    }
    
    // diese Methode sendet eine Systemmail nachdem der Mailtext ausgelesen und Platzhalter ersetzt wurden
    // sysmail_id : eindeutige Bezeichnung der entsprechenden Systemmail, entspricht adm_texts.txt_name
    // user       : Benutzerobjekt, zu dem die Daten dann ausgelesen und in die entsprechenden Platzhalter gesetzt werden    
    function sendSystemMail($sysmail_id, &$user)
    {
        global $g_preferences;
        
        $this->getMailText($sysmail_id, $user);
        $this->setSender($g_preferences['email_administrator']);
        $this->setSubject($this->mailHeader);
        $this->setText($this->mailText);

        return $this->sendEmail();
    }
}
?>