<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */
/**
 * xhttperror module
 *
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         xhttperror
 * @since           1.00
 * @author          Xoops Development Team
 * @version         svn:$id$
 */

$currentFile = basename(__FILE__);
include_once __DIR__ . '/admin_header.php';

$op = XoopsRequest::getString('op', 'list_reports');
switch($op) {
    default:
    case 'list_reports':
        // render start here
        xoops_cp_header();
        // render submenu
        $modcreate_admin = new ModuleAdmin();
        echo $modcreate_admin->addNavigation($currentFile);
        //
        $reportCount = $xhttperror->getHandler('report')->getCount();
        if($reportCount > 0) {
            $start = XoopsRequest::getInt('start', 0);
            $reportCriteria = new CriteriaCompo();
            $reportCriteria->setSort('report_date');
            $reportCriteria->setOrder('ASC');
            $reportCriteria->setStart($start);
            $reportCriteria->setLimit($xhttperror->getConfig('reports_perpage'));
            $reports = $xhttperror->getHandler('report')->getObjects($reportCriteria, true, false); // as array
            foreach ($reports as $key => $report) {
                $report['report_owner_uname'] = XoopsUserUtility::getUnameFromId($report['report_uid']);
                $report['report_date_formatted'] = XoopsLocal::formatTimestamp($report['report_date'], 'l');
                $GLOBALS['xoopsTpl']->append('reports', $report);
            }
            $GLOBALS['xoopsTpl']->assign('token', $GLOBALS['xoopsSecurity']->getTokenHTML());
            include_once XOOPS_ROOT_PATH . '/class/pagenav.php';
            $pagenav = new XoopsPageNav($reportCount, $xhttperror->getConfig('reports_perpage'), $start, 'start');
            $GLOBALS['xoopsTpl']->assign('reports_pagenav', $pagenav->renderNav());
            //
            $GLOBALS['xoopsTpl']->display("db:{$xhttperror->getModule()->dirname()}_am_reports_list.tpl");
        } else {
            echo _CO_XHTTPERROR_WARNING_NOREPORTS;
        }
        include __DIR__ . '/admin_footer.php';
        break;

    case 'apply_actions':
        $action         = XoopsRequest::getString('actions_action');
        $report_ids     = XoopsRequest::getArray('report_ids', unserialize(XoopsRequest::getString('serialize_report_ids')));
        $reportCriteria = new Criteria('report_id', '(' . implode(',', $report_ids) . ')', 'IN');
        switch ($action) {
            case 'delete_reports':
                if (XoopsRequest::getBool('ok', false, 'POST') == true) {
                    // delete subscriber (subscr), subscriptions (catsubscrs) and mailinglist
                    if ($xhttperror->getHandler('report')->deleteAll($reportCriteria, true, true)) {
                        redirect_header($currentFile, 3, _CO_XHTTPERROR_ERRORS_DELETED);
                    } else {
                        // error
                        redirect_header($currentFile, 3, _CO_XHTTPERROR_ERROR_DELETEREPORTS);
                        exit();
                    }
                } else {
                    xoops_cp_header();
                    xoops_confirm(
                        array('ok' => true, 'op' => 'apply_actions', 'actions_action' => $action, 'serialize_report_ids' => serialize($report_ids)),
                        $_SERVER['REQUEST_URI'],
                        _CO_XHTTPERROR_ERRORS_DELETE_AREUSURE,
                        _DELETE
                    );
                    xoops_cp_footer();
                }
                break;
            default:
                // NOP
                break;
        }
        break;

    case 'delete_report' :
        $report_id = XoopsRequest::getInt('report_id', 0);
        $reportObj = $xhttperror->getHandler('report')->get($report_id);
        if (!$reportObj) {
            // ERROR
            redirect_header($currentFile, 3, _CO_XHTTPERROR_ERROR_NOREPORT);
            exit();
        }
        if (XoopsRequest::getBool('ok', false, 'POST') == true) {
            if (!$GLOBALS['xoopsSecurity']->check()) {
                redirect_header($currentFile, 3, implode(',', $GLOBALS['xoopsSecurity']->getErrors()));
            }
            if ($xhttperror->getHandler('report')->delete($reportObj)) {
                redirect_header($currentFile, 3, _CO_XHTTPERROR_REPORT_DELETED);
            } else {
                // ERROR
                xoops_cp_header();
                echo $reportObj->getHtmlErrors();
                xoops_cp_footer();
                exit();
            }
        } else {
            xoops_cp_header();
            xoops_confirm(
                array('ok' => true, 'op' => 'delete_report', 'report_id' => $report_id),
                $_SERVER['REQUEST_URI'],
                _CO_XHTTPERROR_REPORT_DELETE_AREUSURE,
                _DELETE
            );
            xoops_cp_footer();
        }
        break;
}
