<?php
declare(strict_types=1);

namespace Drupal\smmg_member\Types;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;
use Drupal\small_messages\Utility\Helper;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 *
 *
 *  -------------------------------------
 *    - field_smmg_accept_newsletter
 *    - field_birthday
 *    - body
 *    - field_city
 *    - field_company
 *    - field_country
 *    - field_data
 *    - field_smmg_donates_annually
 *    - field_email
 *    - field_smmg_fake
 *    - field_first_name
 *    - field_gender
 *    - field_id
 *    - field_image
 *    - field_smmg_is_active
 *    - field_member_is_public
 *    - field_last_name
 *    - field_member_type
 *    - field_mobile
 *    - field_smmg_origin
 *    - field_phone
 *    - field_phone_2
 *    - field_street_and_number
 *    - field_smmg_subscriber_group
 *    - field_smmg_token
 *    - field_smmg_user
 *    - field_zip_code
 *
 */
class Member
{
  private $data;
  private $title;
  private $node;
  private $id;
  private $created;
  private $changed;

  public const type = 'smmg_member';

  /* Drupal Fields */

  // Meta
  public const field_accept_newsletter = 'field_smmg_accept_newsletter';
  public const field_fake = 'field_smmg_fake';
  public const field_is_active = 'field_smmg_is_active';
  public const field_transfer_id = 'field_id';
  public const field_token = 'field_smmg_token';
  // Groups
  public const field_subscriber_group = 'field_smmg_subscriber_group';
  public const field_origin = 'field_smmg_origin';
  public const field_member_type = 'field_member_type';
  // Address
  public const field_first_name = 'field_first_name';
  public const field_last_name = 'field_last_name';
  public const field_street_and_number = 'field_street_and_number';
  public const field_zip_code = 'field_zip_code';
  public const field_city = 'field_city';
  public const field_birthday = 'field_birthday';
  // Contact
  public const field_email = 'field_email';
  public const field_phone = 'field_phone';
  public const field_phone_2 = 'field_phone_2';
  public const field_mobile = 'field_mobile';

  // JSON DATA
  public const field_data = 'field_data';
  /**
   * @var void
   */
  private $json_data;
  /**
   * @var string
   */
  private $token;
  /**
   * @var bool
   */
  private $is_active;
  /**
   * @var int
   */
  private $transfer_id;
  /**
   * @var bool
   */
  private $accept_newsletter;
  /**
   * @var array
   */
  private $subscriber_group;
  /**
   * @var array
   */
  private $origin;
  /**
   * @var string
   */
  private $first_name;
  /**
   * @var string
   */
  private $last_name;
  /**
   * @var string
   */
  private $street_and_number;
  /**
   * @var string
   */
  private $zip_code;
  /**
   * @var string
   */
  private $city;
  /**
   * @var string
   */
  private $birthday;
  /**
   * @var integer
   */
  private $member_type;

  public function __construct($nid)
  {
    $convert = true;

    $this->id = 0;
    $this->title = '';
    $this->created = false;
    $this->changed = false;
    $this->done = false;
    $this->active = false;
    $data_json = [];

    $node = Node::load($nid);

    if (!empty($node)) {


      $this->node = $node;

      // Default
      $this->id = (int)$node->id();
      $this->title = $node->label();
      $this->created = (int)$node->getCreatedTime();
      $this->changed = (int)$node->getChangedTime();

      // Meta
      $this->token = Helper::getFieldValue($node, self::field_token);
      $this->is_active = (boolean)Helper::getFieldValue($node, self::field_is_active);
      $this->transfer_id = (int)Helper::getFieldValue(
        $node,
        self::field_transfer_id
      );
      $this->accept_newsletter = (boolean)Helper::getFieldValue(
        $node,
        self::field_accept_newsletter
      );
      $this->fake = Helper::getFieldValue($node, self::field_fake);

      // Groups
      $this->subscriber_group = Helper::getFieldValue(
        $node,
        self::field_subscriber_group,
        'smmg_subscriber_group',
        'full'
      );
      $origin = Helper::getFieldValue(
        $node,
        self::field_origin,
        'smmg_origin',
        'full'
      );
      $this->origin = $origin[0];

      $this->member_type = Helper::getFieldValue(
        $node,
        self::field_member_type
      );

      // Address
      $this->first_name = Helper::getFieldValue($node, self::field_first_name);
      $this->last_name = Helper::getFieldValue($node, self::field_last_name);
      $this->street_and_number = Helper::getFieldValue(
        $node,
        self::field_street_and_number
      );
      $this->zip_code = Helper::getFieldValue($node, self::field_zip_code);
      $this->city = Helper::getFieldValue($node, self::field_city);
      $this->birthday = Helper::getFieldValue($node, self::field_birthday);

      // contact
      $this->email = (string)Helper::getFieldValue($node, self::field_email);
      $this->phone = (string)Helper::getFieldValue($node, self::field_phone);
      $this->phone_2 = (string)Helper::getFieldValue($node, self::field_phone_2);
      $this->mobile = (string)Helper::getFieldValue($node, self::field_mobile);

      // JSON Data
      $json = Helper::getFieldValue($node, self::field_data);
      $this->json_data = $this->_readJSONData($json);


      if ($convert) {
        if (is_string($json)) {
          $data = json_decode($json, true);
          $newData = self::convertOldData($data);
          $node->set(self::field_data, json_encode($newData));
          try {
            $node->save();
          } catch (EntityStorageException $e) {
          }
        }
      }


      $address = [
        'first_name' => $this->first_name,
        'last_name' => $this->last_name,
        'street_and_number' => $this->street_and_number,
        'zip_code' => $this->zip_code,
        'city' => $this->city,
        'birthday' => $this->birthday,
      ];

      $contact = [
        'phone' => $this->phone,
        'phone_2' => $this->phone_2,
        'mobile' => $this->mobile,
        'email' => $this->email
      ];

      $this->data = [
        'id' => (int)$node->id(),
        'name' => $node->label(),
        'created' => $this->created,
        'changed' => $this->changed,
        'address' => $address,
        'contact' => $contact,
        'token' => $this->token,
        'is_active' => $this->is_active,
        'transfer_id' => $this->transfer_id,
        'newsletter' => $this->accept_newsletter,
        'fake' => $this->fake,
        'groups' => $this->subscriber_group,
        'origin' => $this->origin,
        'data' => $this->json_data,
      ];
    }
  }

  /**
   * @param $data
   * @return array|mixed
   *
   * prepare for computed
   */
  private function _readJSONData($json_data)
  {
    $result = $json_data;

    if (is_string($json_data)) {
      $result = json_decode($json_data, true);

     // $result = self::convertOldData($result);
      return $result;
    }
    return $result;
  }

  private static function convertOldData($data)
  {
    if ($data && $data['test']) {
      $newArray = [];
      foreach ($data as $section) {
        $newArray[] = $section[0];
      }
      return $newArray;
    } else {
      return $data;
    }
  }

  /**
   * @return string|null
   */
  public function getTitle(): ?string
  {
    return $this->title;
  }

  /**
   * @return array
   */
  public function getData(): array
  {
    return $this->data;
  }

  /**
   * @return JsonResponse
   */
  public function getJson(): JsonResponse
  {
    return new JsonResponse($this->data);
  }

  /**
   * @return int
   */
  public function created(): int
  {
    return $this->created;
  }
}
