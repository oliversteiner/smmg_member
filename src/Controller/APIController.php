<?php

namespace Drupal\smmg_member\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\smmg_member\Models\Member;
use Drupal\smmg_member\Utility\MemberTrait;
use Symfony\Component\HttpFoundation\JsonResponse;

class APIController extends ControllerBase
{
  use MemberTrait;

  /**
   * @param $id
   * @return JsonResponse
   * @Method("DELETE")
   * @Route(smmg_member.api.member.delete)
   */
  public static function delete($id): JsonResponse
  {
    $name = 'Delete Member';
    $action = 'delete';
    $path = '';
    $base = 'smmg/api/member/';
    $version = '1.0.0';
    $members = [];

    // Delete
    $result = Member::delete($id);

    // Result
    if ($result) {
      $members[] = $id;
      $message = 'Member successfully deleted.';
    }else{
      $message = 'Member could not be deleted';
    }

    $response = [
      'name' => $name,
      'path' => $base . $path,
      'version' => $version,
      'action' => $action,
      'members' => $members,
      'message' => $message,
    ];

    return new JsonResponse($response);
  }

  /**
   * @return JsonResponse
   * @Method("PUT")
   * @Route(smmg_member.api.member.create)
   */
  public static function new(): JsonResponse
  {
    $name = 'Create New Member';
    $action = 'create';
    $path = '';
    $base = 'smmg/api/member/';
    $version = '1.0.0';

    $items = ['NEU'];

    $response = [
      'name' => $base . $name,
      'path' => $base . $path,
      'version' => $version,
      'action' => $action,
      'items' => $items
    ];

    return new JsonResponse($response);
  }
}
