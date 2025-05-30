<?php
use Admidio\Infrastructure\Plugins\Overview;
use Admidio\Infrastructure\Utils\SecurityUtils;

/**
 ***********************************************************************************************
 * Who is online
 *
 * Plugin shows visitors and registered members of the homepage
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */
try {
    $rootPath = dirname(__DIR__, 2);
    $pluginFolder = basename(__DIR__);

    require_once($rootPath . '/system/common.php');

    // only include config file if it exists
    if (is_file(__DIR__ . '/config.php')) {
        require_once(__DIR__ . '/config.php');
    }

    $whoIsOnlinePlugin = new Overview($pluginFolder);

    // set default values if there has been no value stored in the config.php
    if (!isset($plg_time_online) || !is_numeric($plg_time_online)) {
        $plg_time_online = 10;
    }

    if (!isset($plg_show_visitors) || !is_numeric($plg_show_visitors)) {
        $plg_show_visitors = 1;
    }

    if (!isset($plg_show_members) || !is_numeric($plg_show_members)) {
        $plg_show_members = 2;
    }

    if (!isset($plg_show_self) || !is_numeric($plg_show_self)) {
        $plg_show_self = 1;
    }

    if (!isset($plg_show_users_side_by_side) || !is_numeric($plg_show_users_side_by_side)) {
        $plg_show_users_side_by_side = 0;
    }

    // Set reference time
    $now = new DateTime();
    $minutesOffset = new DateInterval('PT' . $plg_time_online . 'M');
    $refDate = $now->sub($minutesOffset)->format('Y-m-d H:i:s');

    // Find user IDs of all sessions that are in the specified current and reference time
    $sql = 'SELECT ses_usr_id, usr_uuid, usr_login_name
          FROM ' . TBL_SESSIONS . '
     LEFT JOIN ' . TBL_USERS . '
            ON usr_id = ses_usr_id
         WHERE ses_timestamp BETWEEN ? AND ? -- $refDate AND DATETIME_NOW
           AND ses_org_id = ? -- $gCurrentOrgId';
    $queryParams = array($refDate, DATETIME_NOW, $gCurrentOrgId);
    if (!$plg_show_visitors) {
        $sql .= '
        AND ses_usr_id IS NOT NULL';
    }
    if (!$plg_show_self && $gValidLogin) {
        $sql .= '
         AND ses_usr_id <> ? -- $gCurrentUserId';
        $queryParams[] = $gCurrentUserId;
    }
    $sql .= '
     ORDER BY ses_usr_id';
    $onlineUsersStatement = $gDb->queryPrepared($sql, $queryParams);

    if ($onlineUsersStatement->rowCount() > 0) {
        $usrIdMerker = 0;
        $countMembers = 0;
        $countVisitors = 0;
        $allVisibleOnlineUsers = array();
        $textOnlineVisitors = '';

        while ($row = $onlineUsersStatement->fetch()) {
            if ($row['ses_usr_id'] > 0) {
                if (((int)$row['ses_usr_id'] !== $usrIdMerker)
                    && ($plg_show_members == 1 || $gValidLogin)) {
                    $allVisibleOnlineUsers[] = '<strong><a title="' . $gL10n->get('SYS_SHOW_PROFILE') . '"
                    href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array('user_uuid' => $row['usr_uuid'])) . '">' . $row['usr_login_name'] . '</a></strong>';
                    $usrIdMerker = (int)$row['ses_usr_id'];
                }
                ++$countMembers;
            } else {
                ++$countVisitors;
            }
        }

        if (!$gValidLogin && $plg_show_members == 2 && $countMembers > 0) {
            if ($countMembers > 1) {
                $allVisibleOnlineUsers[] = $gL10n->get('PLG_ONLINE_VAR_NUM_MEMBERS', array($countMembers));
            } else {
                $allVisibleOnlineUsers[] = $gL10n->get('PLG_ONLINE_VAR_NUM_MEMBER', array($countMembers));
            }
        }

        if ($plg_show_visitors && $countVisitors > 0) {
            $allVisibleOnlineUsers[] = $gL10n->get('PLG_ONLINE_VAR_NUM_VISITORS', array($countVisitors));
        }

        if ($plg_show_users_side_by_side) {
            $textOnlineVisitors = implode(', ', $allVisibleOnlineUsers);
        } else {
            $textOnlineVisitors = '<br />' . implode('<br />', $allVisibleOnlineUsers);
        }

        if ($onlineUsersStatement->rowCount() === 1) {
            $whoIsOnlinePlugin->assignTemplateVariable('message', $gL10n->get('PLG_ONLINE_VAR_ONLINE_IS', array($textOnlineVisitors)));
        } else {
            $whoIsOnlinePlugin->assignTemplateVariable('message', $gL10n->get('PLG_ONLINE_VAR_ONLINE_ARE', array($textOnlineVisitors)));
        }
    } else {
        $whoIsOnlinePlugin->assignTemplateVariable('message', $gL10n->get('PLG_ONLINE_NO_VISITORS_ON_WEBSITE'));
    }

    if (isset($page)) {
        echo $whoIsOnlinePlugin->html('plugin.who-is-online.tpl');
    } else {
        $whoIsOnlinePlugin->showHtmlPage('plugin.who-is-online.tpl');
    }
} catch (Throwable $e) {
    echo $e->getMessage();
}
