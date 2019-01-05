<?php

namespace Drupal\smmg_member\Utility;

use Drupal\small_messages\Utility\Email;
use Drupal\smmg_member\Controller\MemberController;

trait MemberTrait
{
    public static function getModuleName()
    {
        return 'smmg_member';
    }

    public static function sendNotivicationMail($nid, $token)
    {
        $module = self::getModuleName();
        $data = MemberController::memberVariables($nid, $token);
        $templates = MemberController::getTemplates();

        Email::sendNotificationMail($module, $data, $templates);
    }

    public static function sendmail($data)
    {
        Email::sendmail($data);
    }

    public static function generateMessageHtml($message)
    {
        return Email::generateMessageHtml($message);
    }

    public static function getEmailAddressesFromConfig()
    {
        $module = self::getModuleName();
        return Email::getEmailAddressesFromConfig($module);

    }
}