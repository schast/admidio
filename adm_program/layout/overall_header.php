<?php
/******************************************************************************
 * Html-Kopf der in allen Admidio-Dateien integriert wird
 *
 * Copyright    : (c) 2004 - 2007 The Admidio Team
 * Homepage     : http://www.admidio.org
 * Module-Owner : Markus Fassbender
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!! W I C H T I G !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * Diese Datei bitte NICHT anpassen, da diese bei jedem Update ueberschrieben
 * werden sollte. Individuelle Anpassungen koennen in der header.php bzw. der 
 * body_top.php im Ordner adm_config gemacht werden.
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 *
 *****************************************************************************/

if(isset($g_layout['title']))
{
    $g_layout['title'] = strStripTags($g_layout['title']);
}
else
{
    $g_layout['title'] = "";
}

if(isset($g_layout['header']) == false)
{
    $g_layout['header'] = "";
}

if(isset($g_layout['onload']))
{
    $g_layout['onload'] = " onload=\"". $g_layout['onload']. "\"";
}
else
{
    $g_layout['onload'] = "";
}

if(isset($g_layout['includes']) == false)
{
    $g_layout['includes'] = true;
}
$orga_name = "";
if(isset($g_current_organization))
{
    $orga_name = $g_current_organization->getValue("org_longname");
}

echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="de" xml:lang="de">
<head>
    <!-- (c) 2004 - 2007 The Admidio Team - http://www.admidio.org -->
    
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    
    <title>'. $orga_name; 
    if(strlen($g_layout['title']) > 0)
    {
        echo " - ". $g_layout['title'];
    }
    echo '</title>    
    
    <link rel="stylesheet" type="text/css" href="'. $g_root_path. '/adm_program/layout/system.css" />';
    
    if(strlen($g_preferences['user_css']) > 0)
    {
        echo '    <link rel="stylesheet" type="text/css" href="'. $g_root_path. '/adm_config/'. $g_preferences['user_css']. '" />';
    }

    echo $g_layout['header']. '

    <!--[if lt IE 7]>
    <script type="text/javascript" src="'. $g_root_path. '/adm_program/system/correct_png.js"></script>
    <![endif]-->';

    if($g_layout['includes'])
    {
        require(SERVER_PATH. "/adm_config/header.php");
    }
    
echo "</head>
<body". $g_layout['onload']. ">";
    if($g_layout['includes'])
    {
        require(SERVER_PATH. "/adm_config/body_top.php");
    }

 ?>