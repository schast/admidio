<?php
/**
 ***********************************************************************************************
 * Overview and maintenance of all profile fields
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 *  Parameters:
 *
 *  mode : list     - (default) Show page with a list of all profile fields
 *         edit     - Show form to create or edit a profile field
 *         save     - Save the data of the form
 *         delete   - Delete a profile field
 *         sequence - Change sequence for a profile field
 * uuid  : UUID of the profile field that should be edited
 * direction : Direction to change the sequence of the profile field
 ***********************************************************************************************
 */
use Admidio\Exception;
use Admidio\UserInterface\Form;

try {
    require_once(__DIR__ . '/../system/common.php');
    require(__DIR__ . '/../system/login_valid.php');

    // Initialize and check the parameters
    $getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'list', 'validValues' => array('list', 'edit', 'save', 'delete', 'sequence')));
    $getProfileFieldUUID = admFuncVariableIsValid($_GET, 'uuid', 'uuid');

    // only authorized users can edit the profile fields
    if (!$gCurrentUser->isAdministrator()) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    switch ($getMode) {
        case 'list':
            $headline = $gL10n->get('ORG_PROFILE_FIELDS');
            $gNavigation->addUrl(CURRENT_URL, $headline);
            $profileFields = new \Admidio\UserInterface\ProfileFields('adm_profile_fields', $headline);
            $profileFields->createList();
            $profileFields->show();
            break;

        case 'edit':
            // set headline of the script
            if ($getProfileFieldUUID !== '') {
                $headline = $gL10n->get('ORG_EDIT_PROFILE_FIELD');
            } else {
                $headline = $gL10n->get('ORG_CREATE_PROFILE_FIELD');
            }

            $gNavigation->addUrl(CURRENT_URL, $headline);
            $profileFields = new \Admidio\UserInterface\ProfileFields('adm_profile_fields_edit');
            $profileFields->createEditForm($getProfileFieldUUID);
            $profileFields->show();
            break;

        case 'save':
            $profileFieldsModule = new \Admidio\Modules\ProfileFields($gDb, $getProfileFieldUUID);
            $profileFieldsModule->save();

            $gNavigation->deleteLastUrl();
            echo json_encode(array('status' => 'success', 'url' => $gNavigation->getUrl()));
            break;

        case 'delete':
            // check the CSRF token of the form against the session token
            SecurityUtils::validateCsrfToken($_POST['adm_csrf_token']);

            $profileFields = new TableUserField($gDb);
            $profileFields->readDataByUuid($getProfileFieldUUID);
            $profileFields->delete();
            echo json_encode(array('status' => 'success'));
            break;

        case 'sequence':
            // Update menu entry sequence
            $postDirection = admFuncVariableIsValid($_POST, 'direction', 'string', array('validValues' => array(TableMenu::MOVE_UP, TableMenu::MOVE_DOWN)));
            $getOrder      = admFuncVariableIsValid($_GET, 'order', 'array');

            // check the CSRF token of the form against the session token
            SecurityUtils::validateCsrfToken($_POST['adm_csrf_token']);

            $profileFields = new TableUserField($gDb);
            $profileFields->readDataByUuid($getProfileFieldUUID);
            if (!empty($getOrder)) {
                // set new order (drag and drop)
                $profileFields->setSequence(explode(',', $getOrder));
            } else {
                $profileFields->moveSequence($postDirection);
            }
            echo json_encode(array('status' => 'success'));
            break;
    }
} catch (Throwable $e) {
    if (in_array($getMode, array('save', 'delete'))) {
        echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
    } else {
        $gMessage->show($e->getMessage());
    }
}
