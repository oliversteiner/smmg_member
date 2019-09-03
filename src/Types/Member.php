<?php
declare(strict_types=1);

namespace Drupal\smmg_member\Types;

use DateTime;
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
  private $json_data_old;

  public function __construct($nid)
  {
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
      $this->id = (int) $node->id();
      $this->title = $node->label();
      $this->created = (int) $node->getCreatedTime();
      $this->changed = (int) $node->getChangedTime();

      // Meta
      $this->token = Helper::getFieldValue($node, self::field_token);
      $this->is_active = (bool) Helper::getFieldValue(
        $node,
        self::field_is_active
      );
      $this->transfer_id = (int) Helper::getFieldValue(
        $node,
        self::field_transfer_id
      );
      $this->accept_newsletter = (bool) Helper::getFieldValue(
        $node,
        self::field_accept_newsletter
      );
      $this->fake = (bool) Helper::getFieldValue($node, self::field_fake);

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
      $this->origin = $origin;

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
      $this->email = (string) Helper::getFieldValue($node, self::field_email);
      $this->phone = (string) Helper::getFieldValue($node, self::field_phone);
      $this->phone_2 = (string) Helper::getFieldValue(
        $node,
        self::field_phone_2
      );
      $this->mobile = (string) Helper::getFieldValue($node, self::field_mobile);

      // JSON Data
      $json = Helper::getFieldValue($node, self::field_data);
      $this->json_data = $this->_readJSONData($json);

      // Batch ----------------------------------------

      // Convert old Data Format
      $convert_old_data = false;
      if ($convert_old_data) {
        if (is_string($json)) {
          $data = json_decode($json, true);
          $this->json_data_old = $data;
          $newData = self::convertOldData($data);
          $this->json_data = $newData;
          $node->set(self::field_data, json_encode($newData));
          try {
            $node->save();
          } catch (EntityStorageException $e) {
          }
        }
      }

      // Fake
      $set_fake_to_all = false;
      if ($set_fake_to_all && !$this->fake) {
        $node->set(self::field_fake, true);
        try {
          $node->save();
        } catch (EntityStorageException $e) {
        }
      }
      // End Batch ----------------------------------------

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
        'email' => $this->email,
      ];

      $this->data = [
        'id' => (int) $node->id(),
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
        //  'data_old' => $this->json_data_old,
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
      return $result;
    }
    return $result;
  }

  /**
   * @param $data
   * @return array
   *
   *
   *     messageId     - int
   *     send          - bool
   *     sendTS        - timestamp
   *     open          - bool
   *     openTS        - timestamp
   *     unsubscribe   - bool
   *     invalidEmail  - bool
   *     error         - string
   *     test          - bool
   *
   *
   */
  private static function convertOldData($data): array
  {
    $new_data = [];

    if ($data && isset($data['test'])) {
      $newArray = [];
      foreach ($data as $section) {
        $newArray[] = $section[0];
      }
      $data = $newArray;
    }

    foreach ($data as $message) {
      if ($message) {
        // Message ID
        if (isset($message['message_id'])) {
          $messageId = (int) $message['message_id'];
        } else {
          $messageId = (int) $message['messageId'];
        }

        if (isset($message['unsubscribe'])) {
          $unsubscribe = (bool) $message['unsubscribe'];
        } else {
          $unsubscribe = false;
        }

        // Send Date
        if (isset($message['sendDate'])) {
          $sendTS = (int) $message['sendDate'];
          $send = true;
        } elseif (isset($message['send_date'])) {
          $sendTS = (int) $message['send_date'];
          $send = true;
        } else {
          $sendTS = 0;
          $send = false;
        }

        if (isset($message['messageId'])) {
          $send = true;
        }

        // Open Date
        if (isset($message['open_date'])) {
          $openTS = (int) $message['open_date'];
        } elseif (isset($message['openDate'])) {
          $openTS = (int) $message['openDate'];
        } else {
          $openTS = 0;
        }

        // Open / Read Message
        if (isset($message['open']) && is_array($message['open'])) {
          $open = (bool) $message['open'][0];
          $openTS = (int) $message['open'][1];
        } else {
          $open = (bool) $message['open'];
        }

        if ($open && $openTS === 0) {
          $openTS = strtotime('+1 day', $sendTS);
        }

        // invalidEmail
        $message['invalidEmail']
          ? ($invalidEmail = $message['invalidEmail'])
          : ($invalidEmail = false);

        // error
        $message['error'] ? ($error = $message['error']) : ($error = '');

        // test
        $message['test'] ? ($test = $message['test']) : ($test = false);

        $result = [
          'messageId' => $messageId,
          'send' => $send,
          'sendTS' => $sendTS,
          'open' => $open,
          'openTS' => $openTS,
          'unsubscribe' => $unsubscribe,
          'invalidEmail' => $invalidEmail,
          'error' => $error,
          'test' => $test,
        ];

        $new_data[] = $result;
      }
    }
    return $new_data;
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

  /**
   * @param array $data
   * @param int $message_id
   * @param bool $test
   * @return array
   * @throws \Exception
   *
   *
   *     messageId     - int
   *     send          - bool
   *     sendTS        - timestamp
   *     open          - bool
   *     openTS        - timestamp
   *     unsubscribe   - bool
   *     invalidEmail  - bool
   *     error         - string
   *     test          - bool
   *
   *
   */
  public static function buildJsonData(
    $data,
    int $message_id,
    bool $test = false
  ): array {
    if (!$data) {
      $data = array();
    }

    // set send and send Timestamp
    $send = true;
    $sendTS = time();


    // Build new Message Entry
    $new_item = [
      'messageId' => $message_id,
      'send' => $send,
      'sendTS' => $sendTS,
      'open' => false,
      'openTS' => 0,
      'unsubscribe' => false,
      'invalidEmail' => false,
      'error' => '',
      'test' => $test,
    ];

    // add to Data
    $data[] = $new_item;

    return $data;
  }
}
