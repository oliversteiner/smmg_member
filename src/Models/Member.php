<?php
declare(strict_types=1);

namespace Drupal\smmg_member\Models;

use DateTime;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
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
  public const field_fake = 'field_smmg_fake';
  public const field_is_active = 'field_smmg_is_active';
  public const field_transfer_id = 'field_id';
  public const field_token = 'field_smmg_token';

  // Groups
  public const field_subscriber_group = 'field_smmg_subscriber_group';
  public const field_origin = 'field_smmg_origin';
  public const field_member_type = 'field_member_type';

  // Personal
  public const field_gender = 'field_gender';
  public const field_first_name = 'field_first_name';
  public const field_last_name = 'field_last_name';
  public const field_accept_newsletter = 'field_smmg_accept_newsletter';
  public const field_birthday = 'field_birthday';

  // Address
  public const field_street_and_number = 'field_street_and_number';
  public const field_zip_code = 'field_zip_code';
  public const field_city = 'field_city';

  // Contact
  public const field_email = 'field_email';
  public const field_phone = 'field_phone';
  public const field_phone_2 = 'field_phone_2';
  public const field_mobile = 'field_mobile';

  // Additional DATA (as JSON)
  public const field_data = 'field_data';

  private $additional_data;

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

  private $subscriber_group;

  private $origin;

  private $first_name;

  private $last_name;

  private $street_and_number;

  private $zip_code;

  private $city;

  private $birthday;

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
    $this->data = [];
    $data_json = [];

    if ($nid) {
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
        $this->first_name = Helper::getFieldValue(
          $node,
          self::field_first_name
        );
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
        $this->mobile = (string) Helper::getFieldValue(
          $node,
          self::field_mobile
        );

        // JSON Data
        $json = Helper::getFieldValue($node, self::field_data);
        $this->additional_data = $this->_readJSONData($json);

        // Batch ----------------------------------------

        // Convert old Data Format
        $convert_old_data = false;
        if ($convert_old_data) {
          if (is_string($json)) {
            $data = json_decode($json, true);
            $this->json_data_old = $data;
            $newData = self::convertOldData($data);
            $this->additional_data = $newData;
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
          'birthday' => $this->birthday
        ];

        $contact = [
          'phone' => $this->phone,
          'phone_2' => $this->phone_2,
          'mobile' => $this->mobile,
          'email' => $this->email
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
          'data' => $this->additional_data
        ];
      }
    }
  }

  /**
   * @param $json_data
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
          'test' => $test
        ];

        $new_data[] = $result;
      }
    }
    return $new_data;
  }

  private static function sanitizedInput($input, string $mode)
  {
    // recrusive
    if (is_array($input)) {
      $save_arr = [];
      foreach ($input as $item) {
        $save_item[] = self::sanitizedInput($item, $mode);
      }
      return $save_arr;
    }

    // https://www.php.net/manual/de/filter.filters.sanitize.php
    $input = trim($input);
    switch ($mode) {
      case 'json':
      case 'string':
        $save = filter_var(
          $input,
          FILTER_SANITIZE_STRING,
          FILTER_FLAG_STRIP_LOW
        );
        break;

      case 'int':
        $save = filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        break;

      case 'float':
        $save = filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT);
        break;

      case 'html':
        $save = filter_var($input, FILTER_SANITIZE_FULL_SPECIAL_CHARS, 0);
        break;

      case 'email':
        $save = filter_var($input, FILTER_SANITIZE_EMAIL);
        break;

      case 'url':
        $save = filter_var($input, FILTER_SANITIZE_URL);
        break;

      case 'boolean':
        $save = (bool) $input;
        break;

      default:
        $save = filter_var(
          $input,
          FILTER_SANITIZE_STRING,
          FILTER_FLAG_STRIP_LOW
        );
    }

    return $save;
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
      'test' => $test
    ];

    // add to Data
    $data[] = $new_item;

    return $data;
  }

  /**
   * @param $data
   * @return array
   */
  public static function newSubscriber($data): array
  {
    // Token
    $token = $data['token'];

    $output = [
      'status' => false,
      'mode' => 'new',
      'nid' => false,
      'message' => '',
      'token' => $token
    ];

    // Member
    $subscribe = $data['subscribe'];

    // Fieldset address
    $email = $data['email'];
    $gender = $data['gender'];
    $first_name = $data['first_name'];
    $last_name = $data['last_name'];
    $street_and_number = $data['street_and_number'];
    $zip_code = $data['zip_code'];
    $city = $data['city'];
    $phone = $data['phone'];

    $member_nid = self::isEmailInUse($email);

    if ($first_name && $last_name) {
      $title = $first_name . ' ' . $last_name;
    } else {
      $title = $email;
    }

    try {
      // Load List for origin
      $vid = 'smmg_origin';
      $origin_list = Helper::getTermsByName($vid);

      $storage = \Drupal::entityTypeManager()->getStorage('node');
      $new_member = $storage->create([
        'type' => 'member',
        'title' => $title,
        'field_gender' => $gender,
        'field_first_name' => $first_name,
        'field_last_name' => $last_name,
        'field_phone' => $phone,
        'field_street_and_number' => $street_and_number,
        'field_zip_code' => $zip_code,
        'field_city' => $city,
        'field_email' => $email,
        'field_smmg_token' => $token,
        'field_smmg_origin' => $origin_list['member'],

        // Member
        'field_smmg_accept_member' => $subscribe
      ]);

      // Save
      try {
        $new_member->save();
      } catch (EntityStorageException $e) {
      }

      $new_member_nid = $new_member->id();

      // if OK
      if ($new_member_nid) {
        $nid = $new_member_nid;

        $message = t('Information successfully saved.');
        $output['message'] = $message;
        $output['status'] = true;
        $output['nid'] = $nid;
        $output['token'] = $token;
        $output['duplicated_email'] = $member_nid;
      }
    } catch (InvalidPluginDefinitionException $e) {
    } catch (PluginNotFoundException $e) {
    }

    return $output;
  }

  /**
   * @param $id
   * @param $data
   * @return array
   * @throws EntityStorageException
   * @throws PluginNotFoundException
   */
  public static function updateSubscriber($id, $data): array
  {
    $output = [
      'name' => 'api/member/update',
      'version' => 'v1',
      'status' => false,
      'action' => '',
      'message' => '',
      'nid' => $id,
    ];

    if (!$id || empty($id)) {
      $action = 'create';
    } else {
      $action = 'update';
    }
    $output['action'] = $action;

    if (!$data || empty($data)) {
      $output['message'] = t('No Data Input found.');
      $output['status'] = true;
      return $output;
    }
    // Update
    if ($action === 'update') {
      try {
        $entity = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->load($id);

        if ($entity === null) {
          $output['message'] = t(
            'Node with ID ' . $id . ' not found, or empty'
          );
          $output['status'] = true;
          return $output;
        }
      } catch (InvalidPluginDefinitionException $e) {
      } catch (PluginNotFoundException $e) {
      }
    }

    // New / Create
    if ($action === 'create') {
      try {
        $entity = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->create();

        if ($entity === null) {
          $output['message'] = t('failed to create new Node');
          $output['status'] = true;
          return $output;
        }
      } catch (InvalidPluginDefinitionException $e) {
      } catch (PluginNotFoundException $e) {
      }
    }

    if (!empty($entity)) {
      try {
        // Default
        // ------------------------------------------

        // Title : string
        if ($data['title']) {
          $title = self::sanitizedInput($data['title'], 'string');
          $entity->set('title', $title);
        }

        // $body : html
        if ($data['body']) {
          $body = self::sanitizedInput($data['body'], 'html');
          $entity->set('body', $body);
        }

        // Meta
        // ------------------------------------------

        // fake :Boolean
        if ($data['fake']) {
          $d = self::sanitizedInput($data['fake'], 'boolean');
          $entity->set(self::field_fake, $d);
        }

        // active : Boolean
        if ($data['active']) {
          $d = self::sanitizedInput($data['active'], 'boolean');
          $entity->set(self::field_is_active, $d);
        }

        // token : string
        if ($data['token']) {
          $d = self::sanitizedInput($data['token'], 'string');
          $entity->set(self::field_token, $d);
        }

        // transfer_id : string
        if ($data['transfer_id']) {
          $d = self::sanitizedInput($data['transfer_id'], 'string');
          $entity->set(self::field_transfer_id, $d);
        }

        // Groups
        // ------------------------------------------
        // groups : array int -> Taxonomy Term
        if ($data['groups']) {
          $d = self::sanitizedInput($data['typ'], 'int');
          $entity->set(self::field_subscriber_group, $d);
        }

        // Member Type : int -> entity id
        if ($data['typ']) {
          $d = self::sanitizedInput($data['typ'], 'int');
          $entity->set(self::field_member_type, $d);
        }

        // origin : array, int -> taxonomy term
        if ($data['origin']) {
          $d = self::sanitizedInput($data['origin'], 'int');
          $entity->set(self::field_origin, $d);
        }

        // Personal
        // ------------------------------------------

        // first_name : string
        if ($data['first_name']) {
          $d = self::sanitizedInput($data['first_name'], 'string');
          $entity->set(self::field_first_name, $d);
        }

        // last_name : string
        if ($data['last_name']) {
          $d = self::sanitizedInput($data['last_name'], 'string');
          $entity->set(self::field_last_name, $d);
        }

        // gender :array int -> Taxonomy Term
        if ($data['gender']) {
          $d = self::sanitizedInput($data['gender'], 'int');
          $entity->set(self::field_gender, $d);
        }

        // birthday: Date as Unix Timestamp // TODO date or ts
        if ($data['birthday']) {
          $d = self::sanitizedInput($data['birthday'], 'string');
          $entity->set(self::field_birthday, $d);
        }

        // newsletter : Boolean
        if ($data['newsletter']) {
          $d = self::sanitizedInput($data['newsletter'], 'boolean');
          $entity->set(self::field_accept_newsletter, $d);
        }

        // Address
        // ------------------------------------------

        // zip_code : string or number?
        if ($data['zip_code']) {
          $d = self::sanitizedInput($data['zip_code'], 'int');
          $entity->set(self::field_zip_code, $d);
        }

        // street_and_number : string
        if ($data['street_and_number']) {
          $d = self::sanitizedInput($data['street_and_number'], 'string');
          $entity->set(self::field_street_and_number, $d);
        }

        // city : string
        if ($data['city']) {
          $d = self::sanitizedInput($data['city'], 'string');
          $entity->set(self::field_city, $d);
        }

        // Contact
        // ------------------------------------------

        // email : email
        if ($data['email']) {
          $d = self::sanitizedInput($data['email'], 'email');
          $entity->set(self::field_email, $d);
        }

        // mobile : string
        if ($data['mobile']) {
          $d = self::sanitizedInput($data['mobile'], 'string');
          $entity->set(self::field_mobile, $d);
        }

        // phone : string
        if ($data['phone']) {
          $d = self::sanitizedInput($data['phone'], 'string');
          $entity->set(self::field_phone, $d);
        }

        // phone_2 : string
        if ($data['phone_2']) {
          $d = self::sanitizedInput($data['phone_2'], 'string');
          $entity->set(self::field_phone_2, $d);
        }

        // Additional DATA (as JSON)
        if ($data['data']) {
          $d = self::sanitizedInput($data['data'], 'json');
          $entity->set(self::field_data, $d);
        }
      } catch (InvalidPluginDefinitionException $e) {
        $output['message'] = t('Error on Node Fields.');
        $output['status'] = false;
      }

      try {
        $entity->save();
        $output['message'] = t('Information successfully saved.');
        $output['status'] = true;
      } catch (EntityStorageException $e) {
        $output['message'] = t('Error on save Node.');
        $output['status'] = false;
      }
    }
    return $output;
  }

  /**
   * @param $email
   *
   * @return false or nid
   */
  static function isEmailInUse($email)
  {
    $result = false;

    if (!empty($email)) {
      try {
        $nodes = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->loadByProperties(['type' => 'member', 'field_email' => $email]);
      } catch (InvalidPluginDefinitionException $e) {
      } catch (PluginNotFoundException $e) {
      }

      if ($node = reset($nodes)) {
        // found $node that matches the title
        $result = $node->id();
      }
    }
    return $result;
  }
}
