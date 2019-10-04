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
  public const field_country = 'field_country';

  // Contact
  public const field_email = 'field_email';
  public const field_phone = 'field_phone';
  public const field_phone_2 = 'field_phone_2';
  public const field_mobile = 'field_mobile';

  // Additional DATA (as JSON)
  public const field_old_data = 'field_data';
  public const field_telemetry = 'field_smmg_telemetry';

  /* Drupal Taxonomy */
  public const term_origin = 'smmg_origin';
  public const term_gender = 'smmg_gender';
  public const term_country = 'smmg_country';
  public const term_type = 'smmg_member_type';
  public const term_subscriber_group = 'smmg_subscriber_group';

  private $telemetry;
  private $token;
  private $is_active;
  private $transfer_id;
  private $accept_newsletter;
  private $subscriber_group;
  private $origin;
  private $first_name;
  private $last_name;
  private $street_and_number;
  private $zip_code;
  private $city;
  private $country;
  private $birthday;
  private $member_type;
  private $telemetry_old;
  private $gender;
  /**
   * @var array
   */
  private $json_old;

  public function __construct($nid)
  {
    $this->id = 0;
    $this->title = '';
    $this->created = false;
    $this->changed = false;
    $this->done = false;
    $this->active = false;
    $this->data = [];

    if ($nid) {
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
        $this->is_active = (bool)Helper::getFieldValue(
          $node,
          self::field_is_active
        );
        $this->transfer_id = (int)Helper::getFieldValue(
          $node,
          self::field_transfer_id
        );
        $this->accept_newsletter = (bool)Helper::getFieldValue(
          $node,
          self::field_accept_newsletter
        );
        $this->fake = (bool)Helper::getFieldValue($node, self::field_fake);

        // Groups
        $this->subscriber_group = Helper::getFieldValue(
          $node,
          self::field_subscriber_group,
          self::term_subscriber_group,
          'full'
        );

        // Origin
        $this->origin = Helper::getFieldValue(
          $node,
          self::field_origin,
          self::term_origin,
          'full'
        );

        // Country
        $this->country = Helper::getFieldValue(
          $node,
          self::field_country,
          self::term_country,
          'full'
        );

        $this->member_type = Helper::getFieldValue(
          $node,
          self::field_member_type
        );

        // Personel
        // ------------------------------------------

        // Gender
        $this->gender = Helper::getFieldValue(
          $node,
          self::field_gender,
          self::term_gender,
          'full'
        );

        // First Name
        $this->first_name = Helper::getFieldValue(
          $node,
          self::field_first_name
        );

        // Last Name
        $this->last_name = Helper::getFieldValue($node, self::field_last_name);

        // Bithday
        $this->birthday = Helper::getFieldValue($node, self::field_birthday);

        // Addresss
        $this->street_and_number = Helper::getFieldValue(
          $node,
          self::field_street_and_number
        );
        $this->zip_code = Helper::getFieldValue($node, self::field_zip_code);
        $this->city = Helper::getFieldValue($node, self::field_city);

        // contact
        $this->email = (string)Helper::getFieldValue($node, self::field_email);
        $this->phone = (string)Helper::getFieldValue($node, self::field_phone);
        $this->phone_2 = (string)Helper::getFieldValue(
          $node,
          self::field_phone_2
        );
        $this->mobile = (string)Helper::getFieldValue(
          $node,
          self::field_mobile
        );

        // JSON Data
        $json_old = Helper::getFieldValue($node, self::field_old_data);
        $this->json_old= $this->_readJSONData($json_old);

        $json = Helper::getFieldValue($node, self::field_telemetry);
        $this->telemetry = $this->_readJSONData($json);

        // Batch ----------------------------------------

        // Convert old Data Format
        $convert_telemetry_old = false;
        if ($convert_telemetry_old) {


          if($this->json_old && empty($this->telemetry)){

              $newData = self::convertOldData($this->json_old);
              $this->telemetry = $newData;
              $node->set(self::field_telemetry, json_encode($newData));
              try {
                $node->save();
              } catch (EntityStorageException $e) {
              }
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
        // ---------------------------------------------- //
        // End Batch

        // Data and Structure for TWIG and JSON
        // ---------------------------------------------- //

        $personal = [
          'gender' => $this->gender,
          'first_name' => $this->first_name,
          'last_name' => $this->last_name,
          'birthday' => $this->birthday
        ];

        $address = [
          'street_and_number' => $this->street_and_number,
          'zip_code' => $this->zip_code,
          'city' => $this->city,
          'country' => $this->country
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
          'personal' => $personal,
          'contact' => $contact,
          'address' => $address,
          'token' => $this->token,
          'is_active' => $this->is_active,
          'transfer_id' => $this->transfer_id,
          'newsletter' => $this->accept_newsletter,
          'fake' => $this->fake,
          'groups' => $this->subscriber_group,
          'origin' => $this->origin,
          'telemetry' => $this->telemetry
        ];
      }
    }
  }

  /**
   * @param $telemetry
   * @return array|mixed
   *
   * prepare for computed
   */
  private function _readJSONData($telemetry)
  {
    $result = $telemetry;

    if (is_string($telemetry)) {
      $result = json_decode($telemetry, true);
      return $result;
    }
    return $result;
  }

  /**
   * @param $telemetry
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
  private static function convertOldData($telemetry): array
  {
    $telemtry_new = [];

    if ($telemetry && isset($telemetry['test'])) {
      $newArray = [];
      foreach ($telemetry as $section) {
        $newArray[] = $section[0];
      }
      $telemetry = $newArray;
    }

    foreach ($telemetry as $message) {
      if ($message) {
        // Message ID
        if (isset($message['message_id'])) {
          $messageId = (int)$message['message_id'];
        } else {
          $messageId = (int)$message['messageId'];
        }

        if (isset($message['unsubscribe'])) {
          $unsubscribe = (bool)$message['unsubscribe'];
        } else {
          $unsubscribe = false;
        }

        // Send Date
        if (isset($message['sendDate'])) {
          $sendTS = (int)$message['sendDate'];
          $send = true;
        } elseif (isset($message['send_date'])) {
          $sendTS = (int)$message['send_date'];
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
          $openTS = (int)$message['open_date'];
        } elseif (isset($message['openDate'])) {
          $openTS = (int)$message['openDate'];
        } else {
          $openTS = 0;
        }

        // Open / Read Message
        if (isset($message['open']) && is_array($message['open'])) {
          $open = (bool)$message['open'][0];
          $openTS = (int)$message['open'][1];
        } else {
          $open = (bool)$message['open'];
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

        $telemtry_new[] = $result;
      }
    }
    return $telemtry_new;
  }

  private static function sanitizedInput($input, string $mode)
  {
    if (!$input) {
      return false;
    }
    // recrusive
    if (is_array($input)) {
      $save_arr = [];
      foreach ($input as $item) {
        $save_item[] = self::sanitizedInput($item, $mode);
      }
      return $save_arr;
    }

    if (is_string($input)) {
      $input = trim($input);
    }

    // https://www.php.net/manual/de/filter.filters.sanitize.php
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
        $save = (bool)$input;
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
   * @param array $telemetry
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
  public static function buildTelemetry(
    $telemetry,
    int $message_id,
    bool $test = false
  ): array
  {
    if (!$telemetry) {
      $telemetry = array();
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
    $telemetry[] = $new_item;

    return $telemetry;
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
        'type' => self::type,
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
  public static function updateSubscriber($data): array
  {
    // only for Debug
    if (false) {
      $result = ['version' => 1, 'test' => 'test', 'input' => $data];
      return $result;
    }

    $id = 0;
    $output = [
      'name' => 'api/member/update',
      'version' => 'v1',
      'status' => false,
      'action' => '',
      'message' => '',
      'nid' => $data['id'],
      'data' => $data
    ];

    // check data
    if (!$data || empty($data)) {
      $output['message'] = t('No Data Input found.');
      $output['status'] = true;
      return $output;
    }

    // check id, create new Member if no id
    // define $action for Update/Create
    if (!$data['id'] || empty($data['id']) || $data['id'] === 0) {
      $action = 'create';
    } else {
      $id = (int)$data['id'];
      $action = 'update';
    }
    $output['action'] = $action;

    // Update
    // ---------------------------------------------- //
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

    // Create (new Member)
    // ---------------------------------------------- //
    if ($action === 'create') {
      try {
        $entity = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->create(['title' => 'Member', 'type' => self::type]);

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
        $personal = $data['personal'];
        $address = $data['address'];
        $contact = $data['contact'];

        // Default
        // ---------------------------------------------- //

        // Title : string
        if (isset($data['title'])) {
          $title = self::sanitizedInput($data['title'], 'string');
          $entity->set('title', $title);
          $debug['title'] = $title;
        }

        // $body : html
        if (isset($data['body'])) {
          $body = self::sanitizedInput($data['body'], 'html');
          $entity->set('body', $body);
          $debug['body'] = $body;
        }

        // Meta
        // ---------------------------------------------- //

        // fake :Boolean
        if (isset($data['fake'])) {
          $fake = self::sanitizedInput($data['fake'], 'boolean');
          $entity->set(self::field_fake, $fake);
          $debug['fake'] = $fake;
        }

        // active : Boolean
        if (isset($data['active'])) {
          $active = self::sanitizedInput($data['active'], 'boolean');
          $entity->set(self::field_is_active, $active);
          $debug['active'] = $active;
        }

        // token : string
        if (isset($data['token'])) {
          $token = self::sanitizedInput($data['token'], 'string');
          $entity->set(self::field_token, $token);
          $debug['token'] = $token;
        }

        // transfer_id : string
        if (isset($data['transfer_id'])) {
          $transfer_id = self::sanitizedInput($data['transfer_id'], 'string');
          $entity->set(self::field_transfer_id, $transfer_id);
          $debug['transfer_id'] = $transfer_id;
        }

        // Groups
        // ------------------------------------------
        // groups : array int -> Taxonomy Term
        if (isset($data['groups'])) {
          $groups = self::convertToTerms($data['groups']);
          $entity->set(self::field_subscriber_group, $groups);
          $debug['groups'] = $groups;
        }

        // Member Type : int -> entity id
        if (isset($data['typ'])) {
          $typ = self::sanitizedInput($data['typ'], 'int');
          $entity->set(self::field_member_type, $typ);
          $debug['typ'] = $typ;
        }

        // origin : array, int -> taxonomy term
        if (isset($data['origin'])) {
          $origin = self::convertToTerms($data['origin']);
          $entity->set(self::field_origin, $origin);
          $debug['origin'] = $origin;
        }

        // Personal
        // ------------------------------------------

        // gender :array int -> Taxonomy Term
        // TODO check input with Term IDs
        if (isset($personal['gender'])) {
          $gender = self::convertToTerms($personal['gender']);
          $entity->set(self::field_gender, $gender);
          $debug['gender'] = $gender;
        }

        // first_name : string
        if (isset($personal['first_name'])) {
          $first_name = self::sanitizedInput($personal['first_name'], 'string');
          $entity->set(self::field_first_name, $first_name);
          $debug['first_name'] = $first_name;
        }

        // last_name : string
        if (isset($personal['last_name'])) {
          $last_name = self::sanitizedInput($personal['last_name'], 'string');
          $entity->set(self::field_last_name, $last_name);
          $debug['last_name'] = $last_name;
        }

        // birthday: Date as Unix Timestamp // TODO date or ts
        if (isset($personal['birthday'])) {
          $birthday = self::sanitizedInput($personal['birthday'], 'string');
          $entity->set(self::field_birthday, $birthday);
          $debug['birthday'] = $birthday;
        }

        // newsletter : Boolean
        if (isset($personal['newsletter'])) {
          $newsletter = self::sanitizedInput(
            $personal['newsletter'],
            'boolean'
          );
          $entity->set(self::field_accept_newsletter, $newsletter);
          $debug['newsletter'] = $newsletter;
        }

        // Address
        // ---------------------------------------------- //

        // zip_code : string or number?
        if (isset($address['zip_code'])) {
          $zip_code = self::sanitizedInput($address['zip_code'], 'int');
          $entity->set(self::field_zip_code, $zip_code);
          $debug['zip_code'] = $zip_code;
        }

        // street_and_number : string
        if (isset($address['street_and_number'])) {
          $street_and_number = self::sanitizedInput(
            $address['street_and_number'],
            'string'
          );
          $entity->set(self::field_street_and_number, $street_and_number);
          $debug['street_and_number'] = $street_and_number;
        }

        // city : string
        if (isset($address['city'])) {
          $city = self::sanitizedInput($address['city'], 'string');
          $entity->set(self::field_city, $city);
          $debug['city'] = $city;
        }

        // city : string
        if (isset($address['country'])) {
          $country = self::convertToTerms($address['country']);
          $entity->set(self::field_city, $country);
          $debug['country'] = $country;
        }

        // Contact
        // ---------------------------------------------- //

        // email : email
        if (isset($contact['email'])) {
          $email = self::sanitizedInput($contact['email'], 'email');
          $entity->set(self::field_email, $email);
          $debug['email'] = $email;
        }

        // mobile : string
        if (isset($contact['mobile'])) {
          $mobile = self::sanitizedInput($contact['mobile'], 'string');
          $entity->set(self::field_mobile, $mobile);
          $debug['mobile'] = $mobile;
        }

        // phone : string
        if (isset($contact['phone'])) {
          $phone = self::sanitizedInput($contact['phone'], 'string');
          $entity->set(self::field_phone, $phone);
          $debug['phone'] = $phone;
        }

        // phone_2 : string
        if (isset($contact['phone_2'])) {
          $phone_2 = self::sanitizedInput($contact['phone_2'], 'string');
          $entity->set(self::field_phone_2, $phone_2);
          $debug['phone_2'] = $phone_2;
        }

        // Additional DATA (as JSON)
        if (isset($data['telemetry'])) {
          $telemetry = self::sanitizedInput($data['telemetry'], 'json');
          $entity->set(self::field_telemetry, $telemetry);
          $debug['telemetry'] = $telemetry;
        }
      } catch (InvalidPluginDefinitionException $e) {
        $output['message'] = t('Error on Node Fields.');
        $output['status'] = false;
      }

      try {
        $entity->save();
        $newId = $entity->id();
        $output['message'] = t('Information successfully saved.');
        $output['status'] = true;
        $output['nid'] = (int)$newId;
      } catch (EntityStorageException $e) {
        $output['message'] = t('Error on save Node.');
        $output['status'] = false;
      }
    }
    $output['debug'] = $debug;
    return $output;
  }

  /**
   * @param $data
   * @param bool $single
   * @return array | int
   */
  public static function convertToTerms($data, $single = false)
  {
    $new_data = [];

    if ($data && is_array($data)) {
      foreach ($data as $item) {
        if ($item['id']) {
          // secure input
          $id = filter_var($item['id'], FILTER_SANITIZE_NUMBER_INT);

          // multifield: add to array
          $new_data[] = (int)$id;

          // single field: just one int
          if ($single) {
            return (int)$id;
          }
        }
      }
    }
    return $new_data;
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
          ->loadByProperties([
            'type' => self::type,
            self::field_email => $email
          ]);
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

  /**
   * @param $id
   * @return bool|null
   */
  public static function delete($id): ?bool
  {
    $node = Node::load((int)$id);
    if (!empty($node)) {
      try {
        $node->delete();
        return true;
      } catch (EntityStorageException $e) {
        return false;
      }
    }
  }
}
