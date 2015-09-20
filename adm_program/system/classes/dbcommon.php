<?php
/******************************************************************************
 * Common database interface
 *
 * Copyright    : (c) 2004 - 2015 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 https://www.gnu.org/licenses/gpl-2.0.html
 *
 *****************************************************************************/

class DBCommon
{
    public $dbType;
    public $user;
    public $password;
    public $dbName;
    public $server;

    protected $name;        // Name of database system like "MySQL"
    protected $version;
    protected $minVersion;
    protected $connectId;
    protected $queryResult;
    protected $sql;
    protected $transactions = 0;
    protected $dbStructure; // array with arrays of every table with their structure

    /** Display the error code and error message to the user if a database error occurred.
     *  The error must be read by the child method. This method will call a backtrace so
     *  you see the script and specific line in which the error occurred.
     *  @param $code    The database error code that will be displayed.
     *  @param $message The database error message that will be displayed.
     *  @return Will exit the script and returns a html output with the error informations.
     */
    public function db_error($code = 0, $message = '')
    {
        global $g_root_path, $gMessage, $gPreferences, $gCurrentOrganization, $gDebug, $gL10n;

        $htmlOutput = '';
        $backtrace  = $this->getBacktrace();

        // Rollback on open transaction
        if($this->transactions > 0)
        {
            $this->rollback();
        }

        if(!headers_sent() && isset($gPreferences) && defined('THEME_SERVER_PATH'))
        {
            // create html page object
            $page = new HtmlPage($gL10n->get('SYS_DATABASE_ERROR'));
        }

        // transform the database error to html
        $error_string = '<div style="font-family: monospace;">
                         <p><b>S Q L - E R R O R</b></p>
                         <p><b>CODE:</b> '.$code.'</p>
                         '.$message.'<br /><br />
                         <b>B A C K T R A C E</b><br />
                         '.$backtrace.'
                         </div>';
        $htmlOutput = $error_string;

        // in debug mode show error in log file
        if($gDebug === 1)
        {
            error_log($code. ': '. $message);
        }

        // display database error to user
        if(!headers_sent() && isset($gPreferences) && defined('THEME_SERVER_PATH'))
        {
            $page->addHtml($htmlOutput);
            $page->show();
        }
        else
        {
            echo $htmlOutput;
        }

        exit();
    }

    /** The method will commit an open transaction to the database. If the
     *  transaction counter is greater 1 than only the counter will be
     *  decreased and no commit will performed.
     */
    public function endTransaction()
    {
        // If there was a previously opened transaction we do not commit yet...
        // but count back the number of inner transactions
        if ($this->transactions > 1)
        {
            $this->transactions--;
            return true;
        }

        $result = $this->query('COMMIT');

        if (!$result)
        {
            $this->db_error();
        }

        $this->transactions = 0;
        return $result;
    }

    // Teile dieser Funktion sind von get_backtrace aus phpBB3
    // Return a nicely formatted backtrace (parts from the php manual by diz at ysagoon dot com)
    protected function getBacktrace()
    {
        $output = '<div style="font-family: monospace;">';
        $backtrace = debug_backtrace();
        $path = SERVER_PATH;

        foreach ($backtrace as $number => $trace)
        {
            // We skip the first one, because it only shows this file/function
            if ($number == 0)
            {
                continue;
            }

            // Strip the current directory from path
            if (empty($trace['file']))
            {
                $trace['file'] = '';
            }
            else
            {
                $trace['file'] = str_replace(array($path, '\\'), array('', '/'), $trace['file']);
                $trace['file'] = substr($trace['file'], 1);
            }
            $args = array();

            // If include/require/include_once is not called, do not show arguments - they may contain sensible information
            if (!in_array($trace['function'], array('include', 'require', 'include_once'), true))
            {
                unset($trace['args']);
            }
            else
            {
                // Path...
                if (!empty($trace['args'][0]))
                {
                    $argument = htmlentities($trace['args'][0]);
                    $argument = str_replace(array($path, '\\'), array('', '/'), $argument);
                    $argument = substr($argument, 1);
                    $args[] = "'{$argument}'";
                }
            }

            $trace['class'] = (!isset($trace['class'])) ? '' : $trace['class'];
            $trace['type'] = (!isset($trace['type'])) ? '' : $trace['type'];

            $output .= '<br />';
            $output .= '<b>FILE:</b> ' . htmlentities($trace['file']) . '<br />';
            $output .= '<b>LINE:</b> ' . ((!empty($trace['line'])) ? $trace['line'] : '') . '<br />';

            $output .= '<b>CALL:</b> ' . htmlentities($trace['class'] . $trace['type'] . $trace['function']) . '(' . ((count($args)) ? implode(', ', $args) : '') . ')<br />';
        }
        $output .= '</div>';
        return $output;
    }

    // returns the minimum required version of the database
    public function getName()
    {
        if($this->name === '')
        {
            $xmlDatabases = new SimpleXMLElement(SERVER_PATH.'/adm_program/system/databases.xml', 0, true);
            $node = $xmlDatabases->xpath("/databases/database[@id='".$this->dbType."']/name");
            $this->name = (string)$node[0]; // explicit typcasting because of problem with simplexml and sessions
        }
        return $this->name;
    }

    // returns the minimum required version of the database
    public function getMinVersion()
    {
        if($this->minVersion === '')
        {
            $xmlDatabases = new SimpleXMLElement(SERVER_PATH.'/adm_program/system/databases.xml', 0, true);
            $node = $xmlDatabases->xpath("/databases/database[@id='".$this->dbType."']/minversion");
            $this->minVersion = (string)$node[0]; // explicit typcasting because of problem with simplexml and sessions
        }
        return $this->minVersion;
    }

    // returns the version of the database
    public function getVersion()
    {
        if($this->version === '')
        {
            $this->version = $this->server_info();
        }
        return $this->version;
    }

    /** If there is a open transaction than this method sends a rollback to the database
     *  and will set the transaction counter to zero.
     */
    public function rollback()
    {
        if($this->transactions > 0)
        {
            $result = $this->query('ROLLBACK');

            if (!$result)
            {
                $this->db_error();
            }

            $this->transactions = 0;
            return true;
        }
        return false;
    }

    /** Checks if an open transaction exists. If there is no open transaction than
     *  start one otherwise increase the internal transaction counter.
     */
    public function startTransaction()
    {
        // If we are within a transaction we will not open another one,
        // but enclose the current one to not loose data (prevening auto commit)
        if ($this->transactions > 0)
        {
            $this->transactions++;
            return true;
        }

        $result = $this->query('START TRANSACTION');

        if (!$result)
        {
            $this->db_error();
        }

        $this->transactions = 1;
        return $result;
    }
}

?>
